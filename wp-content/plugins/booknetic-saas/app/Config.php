<?php

namespace BookneticSaaS;

use BookneticApp\Providers\Common\ShortCodeService;
use BookneticApp\Providers\Common\WorkflowDriversManager;
use BookneticApp\Providers\Common\WorkflowEventsManager;
use BookneticApp\Providers\Core\Route;
use BookneticApp\Providers\Helpers\Date;
use BookneticSaaS\Backend\Billing\Ajax;
use BookneticSaaS\Backend\Billing\Controller;
use BookneticSaaS\Integrations\PaymentGateways\WooCoommerce;
use BookneticSaaS\Models\Plan;
use BookneticSaaS\Models\Tenant;
use BookneticSaaS\Models\TenantFormInput;
use BookneticApp\Providers\UI\Abstracts\AbstractMenuUI;
use BookneticSaaS\Providers\Common\ShortCodeServiceImpl;
use BookneticSaaS\Providers\Core\Route as SaaSRoute;
use BookneticApp\Providers\UI\MenuUI;
use BookneticApp\Providers\Core\Permission as PermissionRegular;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Providers\UI\MenuUI as SaaSMenuUI;

class Config
{
    private static $planCaches = [];

    /**
     * @var WorkflowDriversManager
     */
    private static $workflowDriversManager;

    /**
     * @var WorkflowEventsManager
     */
    private static $workflowEventsManager;

    /**
     * @var ShortCodeService
     */
    private static $shortCodeService;

    /**
     * @return WorkflowDriversManager
     */
    public static function getWorkflowDriversManager()
    {
        return self::$workflowDriversManager;
    }

    /**
     * @return WorkflowEventsManager
     */
    public static function getWorkflowEventsManager()
    {
        return self::$workflowEventsManager;
    }

    /**
     * @return ShortCodeService
     */
    public static function getShortCodeService()
    {
        return self::$shortCodeService;
    }

	public static function init()
	{
        self::$shortCodeService = new ShortCodeService();
        self::$workflowDriversManager = new WorkflowDriversManager();
        self::$workflowEventsManager = new WorkflowEventsManager();
        self::$workflowEventsManager->setDriverManager( self::$workflowDriversManager );
        self::$workflowEventsManager->setShortcodeService( self::$shortCodeService );

        if ( ! class_exists( \BookneticApp\Providers\Helpers\Helper::class ) )
		{
			require_once ABSPATH . 'wp-admin/includes/plugin.php';

			activate_plugins( 'booknetic/init.php' );
		}

		self::registerWPUserRoles();
		self::registerSigninPage();
		self::registerCoreCapabilites();
        self::registerCoreShortCodes();
        self::registerCoreWorkflowEvents();

		add_action( 'bkntc_backend',                    [self::class, 'registerTenantActivities'] );
		add_filter( 'bkntc_tenant_capability_filter',   [self::class, 'tenantCapabilities'], 10, 2 );
		add_filter( 'bkntc_capability_limit_filter',    [self::class, 'tenantLimits'], 10, 2 );

		add_action( 'bkntcsaas_backend',                [ self::class, 'registerCoreRoutes' ] );
		add_action( 'bkntcsaas_backend',                [ self::class, 'registerCoreMenus' ] );

		add_filter( 'woocommerce_prevent_admin_access', function ()
		{
			return false;
		});

		add_action( 'wp_loaded', [ WooCoommerce::class, 'initFilters' ] );
	}

	public static function registerWPUserRoles()
	{
		add_role( 'booknetic_saas_tenant', bkntcsaas__('Booknetic SaaS Tenant'), [
			'read'         => true,
			'edit_posts'   => false,
			'upload_files' => true
		]);
	}

	public static function registerSigninPage()
	{
		$sign_in_page = Helper::getOption( 'sign_in_page' );

		if( !empty( $sign_in_page ) && ( $sign_in_page_link = get_permalink( $sign_in_page ) ) && !empty( $sign_in_page_link ) )
		{
			add_filter( 'login_url', function () use( $sign_in_page_link )
			{
				return $sign_in_page_link;
			});
		}
	}

	public static function registerTextDomain()
	{
		add_action( 'plugins_loaded', function()
		{
			load_plugin_textdomain( 'booknetic-saas', false, 'booknetic-saas/languages' );
		});
	}

	public static function registerCoreCapabilites()
	{

	}

	public static function registerCoreRoutes ()
	{
		SaaSRoute::post('base', \BookneticSaaS\Backend\Base\Ajax::class);

		SaaSRoute::get('dashboard', \BookneticSaaS\Backend\Dashboard\Controller::class);

		SaaSRoute::get('tenants', \BookneticSaaS\Backend\Tenants\Controller::class);
		SaaSRoute::post('tenants', \BookneticSaaS\Backend\Tenants\Ajax::class);

		SaaSRoute::get('payments', \BookneticSaaS\Backend\Payments\Controller::class);
		SaaSRoute::post('payments', \BookneticSaaS\Backend\Payments\Ajax::class);

		SaaSRoute::get('plans', \BookneticSaaS\Backend\Plans\Controller::class);
		SaaSRoute::post('plans', \BookneticSaaS\Backend\Plans\Ajax::class);

		SaaSRoute::get('custom-fields', \BookneticSaaS\Backend\Customfields\Controller::class);
		SaaSRoute::post('custom-fields', \BookneticSaaS\Backend\Customfields\Ajax::class);

        SaaSRoute::get('workflow', new \BookneticApp\Backend\Workflow\Controller(self::getWorkflowEventsManager()));
        SaaSRoute::post('workflow', new \BookneticApp\Backend\Workflow\Ajax(self::getWorkflowEventsManager()));

		SaaSRoute::get('settings', \BookneticSaaS\Backend\Settings\Controller::class);
		SaaSRoute::post('settings', \BookneticSaaS\Backend\Settings\Ajax::class);

        SaaSRoute::get( 'boostore', \BookneticApp\Backend\Boostore\Controller::class );
        SaaSRoute::post( 'boostore', \BookneticApp\Backend\Boostore\Ajax::class );
	}

	public static function registerCoreMenus()
	{
		SaaSMenuUI::get( 'dashboard' )
            ->setTitle( bkntcsaas__( 'Dashboard' ) )
            ->setIcon( 'fa fa-cube' )
            ->setPriority( 100 );

        SaaSMenuUI::get( 'tenants' )
            ->setTitle( bkntcsaas__( 'Tenants' ) )
            ->setIcon( 'fa fa-user-tie' )
            ->setPriority( 200 );

        SaaSMenuUI::get( 'payments' )
            ->setTitle( bkntcsaas__( 'Payments' ) )
            ->setIcon( 'fa fa-credit-card' )
            ->setPriority( 300 );

        SaaSMenuUI::get( 'plans' )
            ->setTitle( bkntcsaas__( 'Plans' ) )
            ->setIcon( 'fa fa-rocket' )
            ->setPriority( 400 );

        SaaSMenuUI::get( 'custom-fields' )
            ->setTitle( bkntcsaas__( 'Custom fields' ) )
            ->setIcon( 'fa fa-magic' )
            ->setPriority( 500 );

        SaaSMenuUI::get( 'workflow' )
            ->setTitle( bkntcsaas__( 'Workflows' ) )
            ->setIcon( 'fa fa-project-diagram' )
            ->setPriority( 600 );

        SaaSMenuUI::get( 'settings' )
            ->setTitle( bkntcsaas__( 'Settings' ) )
            ->setIcon( 'fa fa-cog' )
            ->setPriority( 1000 );

        SaaSMenuUI::get( 'back_to_wordpress', AbstractMenuUI::MENU_TYPE_TOP_LEFT )
            ->setTitle( bkntc__( 'WORDPRESS' ) )
            ->setIcon( 'fa fa-angle-left' )
            ->setLink( admin_url() )
            ->setPriority( 100 );

        SaaSMenuUI::get( 'boostore', AbstractMenuUI::MENU_TYPE_TOP_LEFT )
            ->setTitle( bkntc__( 'Boostore' ) )
            ->setIcon( 'fa fa-puzzle-piece' )
            ->setPriority( 200 );
	}

    public static function registerTenantActivities ()
    {
		if( PermissionRegular::isAdministrator() )
		{
			Route::get('billing', Controller::class);
			Route::post('billing', Ajax::class);

			MenuUI::get( 'billing', AbstractMenuUI::MENU_TYPE_TOP_RIGHT )
			      ->setTitle( bkntcsaas__( 'Billing' ) )
			      ->setIcon( 'fa fa-credit-card' )
			      ->setPriority( 110 );
		}

		if( Route::getCurrentModule() == 'dashboard' && \BookneticApp\Providers\Core\Capabilities::tenantCan( 'dashboard' ) == false )
		{
			\BookneticApp\Providers\Helpers\Helper::redirect( Route::getURL( 'billing' ) );
		}
    }

	public static function tenantCapabilities( $can, $capability )
	{
		$tenantInf = PermissionRegular::tenantInf();

		if( ! $tenantInf )
			return $can;

		if(! array_key_exists($tenantInf->id , self::$planCaches))
        {
            if( Date::epoch( Date::dateSQL() ) > Date::epoch( $tenantInf->expires_in ) && !Tenant::haveEnoughBalanceToPay() )
            {
                $plan = Plan::where('expire_plan', 1)->fetch();
            }
            else
            {
                $plan = $tenantInf->plan()->fetch();
            }
            self::$planCaches[$tenantInf->id] = $plan;
        }else{
            $plan = self::$planCaches[$tenantInf->id];
        }


		if( ! $plan )
			return false;

		$permissions = json_decode( $plan->permissions, true );

		if( ! isset( $permissions['capabilities'][ $capability ] ) || $permissions['capabilities'][ $capability ] === 'off' )
			return false;

		return $can;
	}

	public static function tenantLimits( $limit, $limitName )
	{
		$tenantInf = PermissionRegular::tenantInf();

		if( ! $tenantInf )
			return $limit;

		if( Date::epoch( Date::dateSQL() ) > Date::epoch( $tenantInf->expires_in ) && !Tenant::haveEnoughBalanceToPay() )
		{
			$plan = Plan::where('expire_plan', 1)->fetch();
		}
		else
		{
			$plan = $tenantInf->plan()->fetch();
		}

		if( ! $plan )
			return 0;

		$permissions = json_decode( $plan->permissions, true );

		if( ! isset( $permissions['limits'][ $limitName ] ) )
			return 0;

		return (int)$permissions['limits'][ $limitName ];
	}

    public static function registerCoreWorkflowEvents()
    {
        self::$workflowEventsManager->get('tenant_signup')
            ->setTitle(bkntcsaas__('New tenant signed up'))
            ->setAvailableParams(['tenant_id', 'tenant_password']);

        self::$workflowEventsManager->get('tenant_signup_completed')
            ->setTitle(bkntcsaas__('Tenant sign-up completed'))
            ->setAvailableParams(['tenant_id']);

        self::$workflowEventsManager->get('tenant_forgot_password')
            ->setTitle(bkntcsaas__('Tenant forgot password'))
            ->setAvailableParams(['tenant_id']);

        self::$workflowEventsManager->get('tenant_reset_password')
            ->setTitle(bkntcsaas__('Tenant password was reset'))
            ->setAvailableParams(['tenant_id']);

        self::$workflowEventsManager->get('tenant_subscribed')
            ->setTitle(bkntcsaas__('Tenant subscribed to a plan'))
            ->setAvailableParams(['tenant_id']);

        self::$workflowEventsManager->get('tenant_unsubscribed')
            ->setTitle(bkntcsaas__('Tenant unsubscribed to a plan'))
            ->setAvailableParams(['tenant_id']);

        self::$workflowEventsManager->get('tenant_paid')
            ->setTitle(bkntcsaas__('Tenant payment received'))
            ->setAvailableParams(['tenant_id']);

        add_action( 'bkntcsaas_tenant_sign_up_confirm', function ($tenantId, $password)
        {
            self::$workflowEventsManager->trigger('tenant_signup', [
                'tenant_id' => $tenantId,
                'tenant_password' => $password
            ]);
        }, 10, 2 );

        add_action( 'bkntcsaas_tenant_paid', function ($tenantId)
        {
            $prev_tenantId = PermissionRegular::tenantId();
            PermissionRegular::setTenantId(null);

            self::$workflowEventsManager->trigger('tenant_paid', [
                'tenant_id' => $tenantId,
            ]);

            PermissionRegular::setTenantId($prev_tenantId);
        });

        add_action( 'bkntcsaas_tenant_subscribed', function ($tenantId)
        {
            $prev_tenantId = PermissionRegular::tenantId();
            PermissionRegular::setTenantId(null);

            self::$workflowEventsManager->trigger('tenant_subscribed', [
                'tenant_id' => $tenantId
            ], false, true);

            PermissionRegular::setTenantId($prev_tenantId);
        });

        add_action( 'bkntcsaas_tenant_unsubscribed', function ($tenantId)
        {
            $prev_tenantId = PermissionRegular::tenantId();
            PermissionRegular::setTenantId(null);

            self::$workflowEventsManager->trigger('tenant_unsubscribed', [
                'tenant_id' => $tenantId
            ], false, true);

            PermissionRegular::setTenantId($prev_tenantId);
        });

        add_action( 'bkntcsaas_tenant_sign_up_completed', function ($tenantId)
        {
            self::$workflowEventsManager->trigger('tenant_signup_completed', [
                'tenant_id' => $tenantId
            ]);
        });

        add_action('bkntcsaas_tenant_reset_password', function ($tenantId)
        {
            self::$workflowEventsManager->trigger('tenant_forgot_password', [
                'tenant_id' => $tenantId
            ]);
        });

        add_action('bkntcsaas_tenant_reset_password_completed', function ($tenantId)
        {
            self::$workflowEventsManager->trigger('tenant_reset_password', [
                'tenant_id' => $tenantId
            ]);
        });

    }

    public static function registerCoreShortCodes()
    {
        $shortCodeService = self::$shortCodeService;
        $shortCodeService->addReplacer([ShortCodeServiceImpl::class, 'replace']);

        $shortCodeService->registerCategory('tenant_info', bkntcsaas__('Tenant Info'));
        $shortCodeService->registerCategory('plan_and_billing_info', bkntcsaas__('Plan & Billing'));

        $shortCodeService->registerShortCode('tenant_id', [
            'name'      =>  bkntcsaas__('Tenant ID'),
            'category'  =>  'tenant_info',
            'depends'   =>  'tenant_id',
        ]);

        $shortCodeService->registerShortCode('tenant_full_name', [
            'name'      =>  bkntcsaas__('Tenant Full Name'),
            'category'  =>  'tenant_info',
            'depends'   =>  'tenant_id',
        ]);

        $shortCodeService->registerShortCode('tenant_email', [
            'name'      =>  bkntcsaas__('Tenant Email'),
            'category'  =>  'tenant_info',
            'depends'   =>  'tenant_id',
            'kind'      =>  'email'
        ]);

        $shortCodeService->registerShortCode('tenant_password', [
            'name'      =>  bkntcsaas__('Tenant Password'),
            'category'  =>  'tenant_info',
            'depends'   =>  'tenant_password',
        ]);

        $shortCodeService->registerShortCode('tenant_registration_date', [
            'name'      =>  bkntcsaas__('Tenant Registration Date'),
            'category'  =>  'tenant_info',
            'depends'   =>  'tenant_id',
        ]);

        $shortCodeService->registerShortCode('plan_id', [
            'name'      =>  bkntcsaas__('Plan ID'),
            'category'  =>  'plan_and_billing_info',
            'depends'   =>  'tenant_id',
        ]);

        $shortCodeService->registerShortCode('plan_name', [
            'name'      =>  bkntcsaas__('Plan Name'),
            'category'  =>  'plan_and_billing_info',
            'depends'   =>  'tenant_id',
        ]);

        $shortCodeService->registerShortCode('plan_color', [
            'name'      =>  bkntcsaas__('Plan Color'),
            'category'  =>  'plan_and_billing_info',
            'depends'   =>  'tenant_id',
        ]);

        $shortCodeService->registerShortCode('plan_description', [
            'name'      =>  bkntcsaas__('Plan Description'),
            'category'  =>  'plan_and_billing_info',
            'depends'   =>  'tenant_id',
        ]);

        $shortCodeService->registerShortCode('payment_amount', [
            'name'      =>  bkntcsaas__('Payment Amount'),
            'category'  =>  'plan_and_billing_info',
            'depends'   =>  'tenant_id',
        ]);

        $shortCodeService->registerShortCode('payment_method', [
            'name'      =>  bkntcsaas__('Payment Method'),
            'category'  =>  'plan_and_billing_info',
            'depends'   =>  'tenant_id',
        ]);

        $shortCodeService->registerShortCode('payment_cycle', [
            'name'      =>  bkntcsaas__('Payment Cycle'),
            'category'  =>  'plan_and_billing_info',
            'depends'   =>  'tenant_id',
        ]);


        // GENERALS
        $shortCodeService->registerShortCode('company_name', [
            'name'      =>  bkntcsaas__('Tenant Company Name'),
            'category'  =>  'tenant_info',
            'depends'   =>  'tenant_id',
        ]);

        $shortCodeService->registerShortCode('company_address', [
            'name'      =>  bkntcsaas__('Tenant Company Address'),
            'category'  =>  'tenant_info',
            'depends'   =>  'tenant_id',
        ]);

        $shortCodeService->registerShortCode('company_phone_number', [
            'name'      =>  bkntcsaas__('Tenant Company Phone'),
            'category'  =>  'tenant_info',
            'depends'   =>  'tenant_id',
            'kind'      =>  'phone',
        ]);

        $shortCodeService->registerShortCode('company_website', [
            'name'      =>  bkntcsaas__('Tenant Company Website'),
            'category'  =>  'tenant_info',
            'depends'   =>  'tenant_id',
        ]);

        $shortCodeService->registerShortCode('company_image_url', [
            'name'      =>  bkntcsaas__('Tenant Company Image URL'),
            'category'  =>  'tenant_info',
            'depends'   =>  'tenant_id',
        ]);

        $shortCodeService->registerShortCode('tenant_domain', [
            'name'      =>  bkntcsaas__('Tenant Domain'),
            'category'  =>  'tenant_info',
            'depends'   =>  'tenant_id',
        ]);

        $shortCodeService->registerShortCode('url_to_complete_signup', [
            'name'      =>  bkntcsaas__('URL To Complete Signup'),
            'category'  =>  'tenant_info',
            'depends'   =>  'tenant_id',
        ]);

        $shortCodeService->registerShortCode('url_to_reset_password', [
            'name'      =>  bkntcsaas__('URL To Reset Password'),
            'category'  =>  'tenant_info',
            'depends'   =>  'tenant_id',
        ]);

        foreach (TenantFormInput::fetchAll() as $tenantFormInput)
        {
            if (in_array($tenantFormInput['type'], ['label', 'link'])) continue;

            if ($tenantFormInput->type === 'file')
            {
                $shortCodeService->registerShortCode('tenant_custom_field_' . $tenantFormInput['id'] . '_url', [
                    'name'      =>  bkntcsaas__('Custom Field - ' . $tenantFormInput['label'] . ' [URL]'),
                    'category'  =>  'others',
                    'depends'   =>  'tenant_id',
                    'kind'      =>  'url'
                ]);

                $shortCodeService->registerShortCode('tenant_custom_field_' . $tenantFormInput['id'] . '_path', [
                    'name'      =>  bkntcsaas__('Custom Field - ' . $tenantFormInput['label'] . ' [PATH]'),
                    'category'  =>  'others',
                    'depends'   =>  'tenant_id',
                    'kind'      =>  'file'
                ]);

                $shortCodeService->registerShortCode('tenant_custom_field_' . $tenantFormInput['id'] . '_name', [
                    'name'      =>  bkntcsaas__('Custom Field - ' . $tenantFormInput['label'] . ' [NAME]'),
                    'category'  =>  'others',
                    'depends'   =>  'tenant_id',
                ]);
                continue;
            }

            $shortCodeService->registerShortCode('tenant_custom_field_' . $tenantFormInput['id'], [
                'name'      =>  bkntcsaas__('Custom Field - ' . $tenantFormInput['label']),
                'category'  =>  'others',
                'depends'   =>  'tenant_id',
            ]);

        }
    }


}