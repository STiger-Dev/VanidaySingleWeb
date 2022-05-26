<?php

namespace BookneticSaaS\Providers\Core;

use BookneticSaaS\Models\TenantBilling;
use BookneticApp\Providers\Helpers\Date;
use BookneticSaaS\Models\Tenant;
use BookneticSaaS\Integrations\PaymentGateways\Paypal;
use BookneticSaaS\Integrations\PaymentGateways\Stripe;
use BookneticApp\Providers\DB\DB;
use BookneticSaaS\Models\TenantFormInputChoice;
use BookneticSaaS\Providers\Helpers\Helper;

class Frontend
{

	const FRONT_DIR		= __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Frontend' . DIRECTORY_SEPARATOR;
	const VIEW_DIR		= __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Frontend' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;

	public static function init()
	{
		do_action( 'bkntcsaas_frontend' );

		self::initAjaxRequests();

		add_filter( 'the_posts', [ static::class, 'tenantBookingPage' ], 10, 1 );

		if( !(defined('DOING_AJAX') && DOING_AJAX) )
		{
			self::addShortcodes();
		}

		self::checkSaaSActions();
	}

	public static function initAjaxRequests( $class = false )
	{
		$controllerClass = $class !== false ? $class : \BookneticSaaS\Frontend\Controller\Ajax::class;
		$methods = get_class_methods( $controllerClass );
		$actionPrefix = (is_user_logged_in() ? 'wp_ajax_' : 'wp_ajax_nopriv_') . 'bkntcsaas_';
		$controllerClass = new $controllerClass();

		foreach( $methods AS $method )
		{
			// break helper methods
			if( strpos( $method, '_' ) === 0 )
				continue;

			add_action( $actionPrefix . $method, function () use ( $controllerClass, $method )
			{
                /*doit add_action()*/
				do_action( "bkntcsaas_before_" . $method );

				$result = call_user_func( [ $controllerClass, $method ] );

				if( is_array( $result ) )
				{
					echo json_encode( $result );
				}
				else
				{
					echo $result;
				}

				exit();
			});
		}
	}

	private static function checkSaaSActions()
	{
		$booknetic_saas_action = Helper::_get( 'booknetic_saas_action', '', 'string' );

		if( $booknetic_saas_action == 'paypal_confirm' )
		{
			$token      = Helper::_get( 'token', '', 'string' );
			$billing_id = Helper::_get( 'billing_id', 0, 'int' );

			if( !empty( $token ) && $billing_id > 0 )
			{
				$payment = new Paypal();
				$result = $payment->setId( $billing_id );
				$result = $payment->executeAgreement( $token );

				if( $result['status'] === true )
				{
					Tenant::billingStatusUpdate( $billing_id, $result['id'] );

					Helper::redirect( admin_url( 'admin.php?page=' . \BookneticApp\Providers\Helpers\Helper::getSlugName() . '&module=billing&payment_status=success') );
				}
				else
				{
					TenantBilling::noTenant()->where( 'id', $billing_id )->update([
						'status'    => 'canceled',
						'error'     => $result['message']
					]);

					Helper::redirect( admin_url( 'admin.php?page=' . \BookneticApp\Providers\Helpers\Helper::getSlugName() . '&module=billing&payment_status=cancel' ) );
				}
			}
			else
			{
				Helper::redirect( admin_url( 'admin.php?page=' . \BookneticApp\Providers\Helpers\Helper::getSlugName() . '&module=billing&payment_status=cancel' ) );
			}
		}
		else if( $booknetic_saas_action == 'paypal_webhook' )
		{
			$paypal = new Paypal( true );
			$paypal->webhook();
			exit();
		}
		else if( $booknetic_saas_action == 'stripe_confirm' )
		{
			$session_id = Helper::_get( 'bkntc_session_id', '', 'string' );

			if( !empty( $session_id ) )
			{
				$payment = new Stripe();
				$result = $payment->checkSession( $session_id );

				if( $result['status'] === true )
				{
					Tenant::billingStatusUpdate( $result['billing_id'], $result['subscription'] );

					Helper::redirect( admin_url( 'admin.php?page=' . \BookneticApp\Providers\Helpers\Helper::getSlugName() . '&module=billing&payment_status=success' ) );
				}
				else
				{
					Helper::redirect( admin_url( 'admin.php?page=' . \BookneticApp\Providers\Helpers\Helper::getSlugName() . '&module=billing&payment_status=cancel' ) );
				}
			}
			else
			{
				Helper::redirect( admin_url( 'admin.php?page=' . \BookneticApp\Providers\Helpers\Helper::getSlugName() . '&module=billing&payment_status=cancel' ) );
			}
		}
		else if( $booknetic_saas_action == 'stripe_webhook' )
		{
			$stripe = new Stripe();
			$stripe->webhook();
			exit();
		}
	}

	private static function addShortcodes()
	{
		add_shortcode('booknetic-saas-signin', function( $atts )
		{
			wp_enqueue_script( 'booknetic-saas', Helper::assets('js/booknetic-saas-signin.js', 'front-end'), [ 'jquery' ] );

			if( Permission::userId() > 0 )
			{
				$redirectToUrl = Helper::getURLOfUsersDashboard();
				wp_add_inline_script( 'booknetic-saas', 'location.href="' . $redirectToUrl . '";' );
				return bkntcsaas__('You are already signed in. Please wait, you are being redirected...');
			}

			wp_enqueue_style('Booknetic-font', '//fonts.googleapis.com/css?family=Poppins:200,200i,300,300i,400,400i,500,500i,600,600i,700&display=swap');
			wp_enqueue_style('booknetic-saas-signin', Helper::assets('css/booknetic-saas-signin.css', 'front-end'));

			wp_localize_script( 'booknetic-saas', 'BookneticSaaSData', [
				'ajax_url'		    => admin_url( 'admin-ajax.php' ),
				'assets_url'	    => Helper::assets('/', 'front-end') ,
				'localization'      => []
			]);

			return self::view('signin');
		});

		add_shortcode('booknetic-saas-signup', function( $atts )
		{
			wp_enqueue_script( 'select2-booknetic-saas', Helper::assets('js/select2.min.js'), ['jquery']);
			wp_enqueue_script( 'booknetic-saas', Helper::assets('js/booknetic-saas-signup.js', 'front-end'), [ 'jquery' ] );

			if( Permission::userId() > 0 )
			{
				$redirectToUrl = Helper::getURLOfUsersDashboard();
				wp_add_inline_script( 'booknetic-saas', 'location.href="' . $redirectToUrl . '";' );
				return bkntcsaas__('You are already signed in. Please wait, you are being redirected...');
			}

			$activation_token = Helper::_get('activation_token', '', 'string');

			if( !empty( $activation_token ) )
			{
				wp_enqueue_script( 'datepicker-booknetic-saas', Helper::assets('js/datepicker.min.js', 'front-end'), [ 'jquery' ] );
				wp_enqueue_script( 'booknetic-saas', Helper::assets('js/booknetic-saas-signup.js', 'front-end'), [ 'jquery' ] );

				$tenantInfo = Tenant::where('activation_token', $activation_token)->fetch();
				if( !$tenantInfo )
				{
					wp_add_inline_script( 'booknetic-saas', 'location.href="' . htmlspecialchars(site_url()) . '";' );
					return bkntcsaas__('Redirecting...');
				}

				wp_localize_script( 'booknetic-saas', 'BookneticSaaSData', [
					'ajax_url'		    => admin_url( 'admin-ajax.php' ),
					'date_format'	    => Helper::getOption('date_format', 'Y-m-d'),
					'assets_url'	    => Helper::assets('/', 'front-end') ,
					'localization'      => []
				]);

				wp_enqueue_style('Booknetic-font', '//fonts.googleapis.com/css?family=Poppins:200,200i,300,300i,400,400i,500,500i,600,600i,700&display=swap');
				wp_enqueue_style('select2-bootstrap', Helper::assets('css/select2-bootstrap.css'));
				wp_enqueue_style('booknetic-select2', Helper::assets('css/select2.css'));
				wp_enqueue_style('booknetic-saas-signup', Helper::assets('css/booknetic-saas-signup.css', 'front-end'));
				wp_enqueue_style('booknetic.datapicker', Helper::assets('css/datepicker.min.css', 'front-end'));
				

				$customData = DB::DB()->get_results(
					'
						SELECT 
							*
						FROM `'.DB::table('tenant_form_inputs').'` tb1
						ORDER BY tb1.order_number',
					ARRAY_A
				);
		
				foreach ( $customData AS $fKey => $formInput )
				{
					if( in_array( $formInput['type'], ['select', 'checkbox', 'radio'] ) )
					{
						$choicesList = TenantFormInputChoice::where('form_input_id', (int)$formInput['id'])->orderBy('order_number')->fetchAll();
		
						$customData[ $fKey ]['choices'] = [];
		
						foreach( $choicesList AS $choiceInf )
						{
							$customData[ $fKey ]['choices'][] = [ (int)$choiceInf['id'], htmlspecialchars($choiceInf['title']) ];
						}
					}
				}

				return self::view('signup_complete', [
					'activation_token'	=>	$activation_token,
					'custom_fields'		=>  $customData
				]);
			}

			wp_localize_script( 'booknetic-saas', 'BookneticSaaSData', [
				'ajax_url'		    => admin_url( 'admin-ajax.php' ),
				'assets_url'	    => Helper::assets('/', 'front-end') ,
				'localization'      => []
			]);

			wp_enqueue_style('Booknetic-font', '//fonts.googleapis.com/css?family=Poppins:200,200i,300,300i,400,400i,500,500i,600,600i,700&display=swap');
			wp_enqueue_style('booknetic-saas-signup', Helper::assets('css/booknetic-saas-signup.css', 'front-end'));

			return self::view('signup');
		});

		add_shortcode('booknetic-saas-forgot-password', function( $atts )
		{
			wp_enqueue_script( 'booknetic-saas', Helper::assets('js/booknetic-saas-forgot-password.js', 'front-end'), [ 'jquery' ] );

			if( Permission::userId() > 0 )
			{
				$redirectToUrl = Helper::getURLOfUsersDashboard();
				wp_add_inline_script( 'booknetic-saas', 'location.href="' . $redirectToUrl . '";' );
				return bkntcsaas__('You are already signed in. Please wait, you are being redirected...');
			}

			$activation_token = Helper::_get('token', '', 'string');

			if( !empty( $activation_token ) )
			{
				wp_enqueue_script( 'booknetic-saas', Helper::assets('js/booknetic-saas-forgot-password.js', 'front-end'), [ 'jquery' ] );

				$tenantInfo = Tenant::where('activation_token', $activation_token)->fetch();
				if( !$tenantInfo )
				{
					wp_add_inline_script( 'booknetic-saas', 'location.href="' . htmlspecialchars(site_url()) . '";' );
					return bkntcsaas__('Redirecting...');
				}

				wp_localize_script( 'booknetic-saas', 'BookneticSaaSData', [
					'ajax_url'		    => admin_url( 'admin-ajax.php' ),
					'assets_url'	    => Helper::assets('/', 'front-end') ,
					'localization'      => []
				]);

				wp_enqueue_style('Booknetic-font', '//fonts.googleapis.com/css?family=Poppins:200,200i,300,300i,400,400i,500,500i,600,600i,700&display=swap');
				wp_enqueue_style('booknetic-saas-forgot-password', Helper::assets('css/booknetic-saas-forgot-password.css', 'front-end'));

				return self::view('forgot_password_complete', [
					'activation_token'	=>	$activation_token
				]);
			}

			wp_localize_script( 'booknetic-saas', 'BookneticSaaSData', [
				'ajax_url'		    => admin_url( 'admin-ajax.php' ),
				'assets_url'	    => Helper::assets('/', 'front-end') ,
				'localization'      => []
			]);

			wp_enqueue_style('Booknetic-font', '//fonts.googleapis.com/css?family=Poppins:200,200i,300,300i,400,400i,500,500i,600,600i,700&display=swap');
			wp_enqueue_style('booknetic-saas-forgot-password', Helper::assets('css/booknetic-saas-forgot-password.css', 'front-end'));

			return self::view('forgot_password');
		});
	}

	private static function view( $name, $parameters = [] )
	{
		ob_start();
		require self::FRONT_DIR . 'view' . DIRECTORY_SEPARATOR . $name . '.php';
		$viewOutput = ob_get_clean();

		return $viewOutput;
	}

	public static function tenantBookingPage( $posts )
	{
		$bookingPageId = Helper::getOption('booking_page', '');

		foreach ( $posts AS $postInf )
		{
			if( $bookingPageId == $postInf->ID )
			{
				wp_redirect( home_url( '/' ) );
				exit();
			}
		}

		$currentDomain = Helper::getCurrentDomain();

		if( !empty( $currentDomain ) )
		{
			$checkTenantExist = Tenant::where('domain', $currentDomain)->fetch();

			if( $checkTenantExist )
			{
				// check this action later...
				remove_action('template_redirect', 'redirect_canonical');

				\BookneticApp\Providers\Core\Permission::setTenantId( $checkTenantExist->id );

				$iframe = Helper::_get( 'iframe', '0', 'int' );

				if( $iframe === 1 )
				{
					add_filter( 'template_include', function ( $page_template )
					{
						return self::VIEW_DIR . 'iframe.php';
					}, PHP_INT_MAX );
				}

				global $wp_query;

				unset( $wp_query->query['error'] );

				$wp_query->is_page     = true;
				$wp_query->is_singular = true;
				$wp_query->is_home     = false;
				$wp_query->is_archive  = false;
				$wp_query->is_category = false;
				$wp_query->is_404      = false;

				$wp_query->query_vars['error'] = '';

				$posts = [ get_post( $bookingPageId ) ];
			}
		}

		return $posts;
	}

}
