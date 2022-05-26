<?php

namespace BookneticApp\Providers\Core;

use BookneticApp\Backend\Appearance\Helpers\Theme;
use BookneticApp\Models\Appearance;
use BookneticApp\Backend\Appointments\Helpers\AppointmentService;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\Customer;
use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Backend\Settings\Helpers\LocalizationService;
use BookneticApp\Models\Staff;
use BookneticApp\Integrations\LoginButtons\FacebookLogin;
use BookneticApp\Integrations\LoginButtons\GoogleLogin;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;

class Frontend
{

	const FRONT_DIR		= __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Frontend' . DIRECTORY_SEPARATOR;
	const VIEW_DIR		= __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Frontend' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;

	public static function init()
	{
		do_action( 'bkntc_frontend' );

		self::checkSocialLogin();

		LocalizationService::changeLanguageIfNeed();

		self::initAjaxRequests();

		if( !(defined('DOING_AJAX') && DOING_AJAX) )
		{
			add_shortcode('booknetic', [self::class, 'addBookneticShortCode']);
            add_shortcode('booknetic-booking-button', [ static::class, 'addBookingPopupShortcode' ]);
        }
	}

    public static function addBookingPopupShortcode( $atts  )
    {
        $bookneticShortcode =  do_shortcode('[booknetic]');

        wp_enqueue_script( 'booknetic-popup', Helper::assets('js/booknetic-popup.js', 'front-end'), [ 'jquery' ] );
        wp_enqueue_style( 'booknetic-popup', Helper::assets('css/booknetic-popup.css', 'front-end'));

        ob_start();
        require self::FRONT_DIR . 'view' . DIRECTORY_SEPARATOR . 'popup/index.php';
        $viewOutput = ob_get_clean();
        return $viewOutput;
	}

	private static function checkSocialLogin()
	{
		$booknetic_action = Helper::_get( Helper::getSlugName() . '_action', '', 'string' );
		if( $booknetic_action == 'facebook_login' )
		{
			Helper::redirect( FacebookLogin::getLoginURL() );
		}
		else if( $booknetic_action == 'facebook_login_callback' )
		{
			$data = FacebookLogin::getUserData();
			echo bkntc__('Loading...');
			echo '<script>var booknetic_user_data = ' . json_encode( $data ) . ';</script>';
			exit;
		}
		else if( $booknetic_action == 'google_login' )
		{
			Helper::redirect( GoogleLogin::getLoginURL() );
		}
		else if( $booknetic_action == 'google_login_callback' )
		{
			$data = GoogleLogin::getUserData();
			echo bkntc__('Loading...');
			echo '<script>var booknetic_user_data = ' . json_encode( $data ) . ';</script>';
			exit;
		}

	}

	public static function initAjaxRequests( $class = false )
	{
		$controllerClass = $class !== false ? $class : \BookneticApp\Frontend\Controller\Ajax::class;
		$methods = get_class_methods( $controllerClass );
		$actionPrefix = (is_user_logged_in() ? 'wp_ajax_' : 'wp_ajax_nopriv_') . 'bkntc_';
		$controllerClass = new $controllerClass();

		foreach( $methods AS $method )
		{
			// break helper methods
			if( strpos( $method, '_' ) === 0 )
				continue;

			add_action( $actionPrefix . $method, function () use ( $controllerClass, $method )
			{
				do_action( "bkntc_before_frontend_request_" . $method );

				$result = call_user_func( [ $controllerClass, $method ] );

				$result = apply_filters('bkntc_after_frontend_request_' . $method, $result);

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

	public static function addBookneticShortCode( $atts )
	{
        $atts = empty( $atts ) ? [] : $atts;
        wp_enqueue_script( 'booknetic', Helper::assets('js/booknetic.js', 'front-end'), [ 'jquery' ] );
		if(Helper::getOption('only_registered_users_can_book', 'off') == 'on' && !is_user_logged_in())
		{
			wp_add_inline_script( 'booknetic', 'location.href="'.wp_login_url().'";' );
			return bkntc__('Redirecting...');
		}
		$theme = null;
		if( isset( $atts['theme'] ) && is_numeric( $atts['theme'] ) && $atts['theme'] > 0 )
		{
			$theme = Appearance::get( $atts['theme'] );
		}
		if( empty( $theme ) )
		{
			$theme = Appearance::where('is_default', '1')->fetch();
		}
		$fontfamily = $theme ? $theme['fontfamily'] : 'Poppins';

		$bookneticJSData = [
			'ajax_url'		            => admin_url( 'admin-ajax.php' ),
			'assets_url'	            => Helper::assets('/', 'front-end') ,
			'date_format'	            => Helper::getOption('date_format', 'Y-m-d'),
			'week_starts_on'            => Helper::getOption('week_starts_on', 'sunday') == 'monday' ? 'monday' : 'sunday',
			'client_time_zone'	        => htmlspecialchars(Helper::getOption('client_timezone_enable', 'off')),
			'skip_extras_step_if_need'  => htmlspecialchars(Helper::getOption('skip_extras_step_if_need', 'on')),
			'localization'              => [
				// months
				'January'               => bkntc__('January'),
				'February'              => bkntc__('February'),
				'March'                 => bkntc__('March'),
				'April'                 => bkntc__('April'),
				'May'                   => bkntc__('May'),
				'June'                  => bkntc__('June'),
				'July'                  => bkntc__('July'),
				'August'                => bkntc__('August'),
				'September'             => bkntc__('September'),
				'October'               => bkntc__('October'),
				'November'              => bkntc__('November'),
				'December'              => bkntc__('December'),

				//days of week
				'Mon'                   => bkntc__('Mon'),
				'Tue'                   => bkntc__('Tue'),
				'Wed'                   => bkntc__('Wed'),
				'Thu'                   => bkntc__('Thu'),
				'Fri'                   => bkntc__('Fri'),
				'Sat'                   => bkntc__('Sat'),
				'Sun'                   => bkntc__('Sun'),

				// select placeholders
				'select'                => bkntc__('Select...'),
				'searching'				=> bkntc__('Searching...'),

				// messages
				'select_location'       => bkntc__('Please select location.'),
				'select_staff'          => bkntc__('Please select staff.'),
				'select_service'        => bkntc__('Please select service'),
				'select_week_days'      => bkntc__('Please select week day(s)'),
				'date_time_is_wrong'    => bkntc__('Please select week day(s) and time(s) correctly'),
				'select_start_date'     => bkntc__('Please select start date'),
				'select_end_date'       => bkntc__('Please select end date'),
				'select_date'           => bkntc__('Please select date.'),
				'select_time'           => bkntc__('Please select time.'),
				'select_available_time' => bkntc__('Please select an available time'),
				'fill_all_required'     => bkntc__('Please fill in all required fields correctly!'),
				'email_is_not_valid'    => bkntc__('Please enter a valid email address!'),
				'phone_is_not_valid'    => bkntc__('Please enter a valid phone number!'),
				'Select date'           => bkntc__('Select date'),
				'NEXT STEP'             => bkntc__('NEXT STEP'),
				'CONFIRM BOOKING'       => bkntc__('CONFIRM BOOKING'),
			],
			'tenant_id'                 => Permission::tenantId()
		];

        $bookneticJSData['localization'] = apply_filters('bkntc_frontend_localization' , $bookneticJSData['localization'] );

		wp_enqueue_script( 'select2-bkntc', Helper::assets('js/select2.min.js') );
		wp_enqueue_script( 'booknetic.datapicker', Helper::assets('js/datepicker.min.js', 'front-end') );
		wp_enqueue_script( 'jquery.nicescroll', Helper::assets('js/jquery.nicescroll.min.js', 'front-end'), [ 'jquery' ] );
		wp_enqueue_script( 'intlTelInput', Helper::assets('js/intlTelInput.min.js', 'front-end'), [ 'jquery' ] );

		if( Helper::getOption('google_recaptcha', 'off', false) == 'on' )
		{
			$google_site_key = Helper::getOption('google_recaptcha_site_key', '', false);
			$google_secret_key = Helper::getOption('google_recaptcha_secret_key', '', false);

			if( !empty( $google_site_key ) && !empty( $google_secret_key ) )
			{
				wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . urlencode($google_site_key) );
				$bookneticJSData['google_recaptcha_site_key'] = $google_site_key;
			}
		}

		wp_localize_script( 'booknetic', 'BookneticData', $bookneticJSData);

		wp_enqueue_style('Booknetic-font', '//fonts.googleapis.com/css?family='.urlencode($fontfamily).':200,200i,300,300i,400,400i,500,500i,600,600i,700&display=swap');
		wp_enqueue_style('bootstrap-booknetic', Helper::assets('css/bootstrap-booknetic.css', 'front-end'));

		wp_enqueue_style('booknetic', Helper::assets('css/booknetic.css', 'front-end') ,['bootstrap-booknetic']);

		wp_enqueue_style('select2', Helper::assets('css/select2.min.css'));
		wp_enqueue_style('select2-bootstrap', Helper::assets('css/select2-bootstrap.css'));
		wp_enqueue_style('booknetic.datapicker', Helper::assets('css/datepicker.min.css', 'front-end'));
		wp_enqueue_style('intlTelInput', Helper::assets('css/intlTelInput.min.css', 'front-end'));

		$theme_id = $theme ? $theme['id'] : 0;

		if( $theme_id > 0 )
		{
			$themeCssFile = Theme::getThemeCss( $theme_id );
			wp_enqueue_style('booknetic-theme', str_replace(['http://', 'https://'], '//', $themeCssFile));
		}

		$company_phone_number = Helper::getOption('company_phone', '');

		$steps = [
			'service'			=> [
				'value'			=>	'',
				'hidden'		=>	false,
				'loader'		=>	'card2',
				'title'			=>	bkntc__('Service'),
				'head_title'	=>	bkntc__('Select service'),
				'attrs'			=>	' data-service-category="'.(isset($atts['category']) && is_numeric($atts['category']) && $atts['category'] > 0 ? $atts['category'] : '').'"'
			],
			'staff'				=> [
				'value'			=>	'',
				'hidden'		=>	false,
				'loader'		=>	'card1',
				'title'			=>	bkntc__('Staff'),
				'head_title'	=>	bkntc__('Select staff')
			],
			'location'			=> [
				'value'			=>	isset($select_location_id) && $select_location_id > 0 ? $select_location_id : '',
				'hidden'		=>	false,
				'loader'		=>	'card1',
				'title'			=>	bkntc__('Location'),
				'head_title'	=>	bkntc__('Select location')
			],
			'service_extras'	=> [
				'value'			=>	'',
				'hidden'		=>	( Capabilities::tenantCan( 'services' ) == false ) || Helper::getOption('show_step_service_extras', 'on') == 'off',
				'loader'		=>	'card2',
				'title'			=>	bkntc__('Service Extras'),
				'head_title'	=>	bkntc__('Select service extras')
			],
			'information'		=> [
				'value'			=>	'',
				'hidden'		=>	false,
				'loader'		=>	'card3',
				'title'			=>	bkntc__('Information'),
				'head_title'	=>	bkntc__('Fill information')
			],
			'cart'		=> [
				'value'			=>	'',
				'hidden'		=>	Helper::getOption('show_step_cart', 'on') == 'off',
				'loader'		=>	'card3',
				'title'			=>	bkntc__('Cart'),
				'head_title'	=>	bkntc__('Add to cart')
			],
			'date_time'			=> [
				'value'			=>	'',
				'hidden'		=>	false,
				'loader'		=>	'card3',
				'title'			=>	bkntc__('Date & Time'),
				'head_title'	=>	bkntc__('Select Date & Time')
			],
			'recurring_info'	=> [
				'value'			=>	'',
				'hidden'		=>	true,
				'loader'		=>	'card3',
				'title'			=>	bkntc__('Recurring info'),
				'head_title'	=>	bkntc__('Recurring info')
			],
			'confirm_details'	=> [
				'value'			=>	'',
				'hidden'		=>	Helper::getOption('show_step_confirm_details', 'on') == 'off',
				'loader'		=>	'card3',
				'title'			=>	bkntc__('Confirmation'),
				'head_title'	=>	bkntc__('Confirm Details')
			],
		];
		$steps_order = Helper::getBookingStepsOrder(true);

		if( ( Capabilities::tenantCan( 'locations' ) == false ) || ( Helper::getOption('show_step_location', 'on') == 'off' ) && ($location = Location::where('is_active', '1')->fetch()) )
		{
			$steps['location']['hidden'] = true;
			$steps['location']['value'] = -1;
		}

        if( isset($_GET['location']) && is_numeric($_GET['location']) && $_GET['location'] > 0)
        {
            $atts['location'] = $_GET['location'];
        }

		if( isset($atts['location']) && is_numeric($atts['location']) && $atts['location'] > 0 )
		{
			$locationInfo = Location::get( $atts['location'] );

			if( $locationInfo )
			{
				$steps['location']['hidden'] = true;
				$steps['location']['value'] = (int)$locationInfo['id'];
			}
		}

		if( ( Capabilities::tenantCan( 'staff' ) == false ) || ( Helper::getOption('show_step_staff', 'on') == 'off' ) && ($staff = Staff::where('is_active', '1')->fetch()) )
		{
			$steps['staff']['hidden'] = true;
			$steps['staff']['value'] = -1;
		}

		if( isset($_GET['staff']) && is_numeric($_GET['staff']) && $_GET['staff'] > 0)
        {
            $atts['staff'] = $_GET['staff'];
        }

		if( isset($atts['staff']) && is_numeric($atts['staff']) && $atts['staff'] > 0 )
		{
			$staffInfo = Staff::get( $atts['staff'] );

			if( $staffInfo )
			{
				$steps['staff']['hidden'] = true;
				$steps['staff']['value'] = (int)$staffInfo['id'];
			}
		}

        $serviceRecurringAttrs = '';
		if(
			(
				( Capabilities::tenantCan( 'services' ) == false ) ||
				( Helper::getOption('show_step_service', 'on') == 'off' )
			)
			&& ($service = Service::where('is_active', '1')->fetch())
		)
		{
			$steps['service']['hidden'] = true;
			$steps['service']['value'] = $service['id'];
            $serviceRecurringAttrs = ' data-is-recurring="' . (int)$service['is_recurring'] . '"';

			if( $service['is_recurring'] == 1 )
			{
				$steps['recurring_info']['hidden'] = false;
			}
		}

        if( isset($_GET['service']) && is_numeric($_GET['service']) && $_GET['service'] > 0)
        {
            $atts['service'] = $_GET['service'];
        }

		if( isset($atts['service']) && is_numeric($atts['service']) && $atts['service'] > 0 )
		{
			$serviceInfo = Service::get( $atts['service'] );

			if( $serviceInfo )
			{
				$steps['service']['hidden'] = true;
				$steps['service']['value'] = $serviceInfo['id'];
                $serviceRecurringAttrs = ' data-is-recurring="' . (int)$serviceInfo['is_recurring'] . '"';

				if( $serviceInfo['is_recurring'] == 1 )
				{
					$steps['recurring_info']['hidden'] = false;
				}
			}
		}
        $steps['service']['attrs'] .= $serviceRecurringAttrs;
		$hide_confirmation_number = Helper::getOption('hide_confirmation_number', 'off') == 'on';

		ob_start();
		require self::FRONT_DIR . 'view' . DIRECTORY_SEPARATOR . 'booking_panel/booknetic.php';
        do_action('bkntc_after_booking_panel_shortcode');
		$viewOutput = ob_get_clean();

		return $viewOutput;
	}

}