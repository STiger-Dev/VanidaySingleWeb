<?php


namespace BookneticAddon\StripePaymentGateway;

use BookneticApp\Providers\Core\AddonLoader;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\UI\TabUI;

function bkntc__($text, $params = [], $esc = true )
{
    return \bkntc__( $text, $params, $esc, StripeAddon::getAddonSlug() );
}

class StripeAddon extends AddonLoader
{

    public function init()
    {
	    Capabilities::registerTenantCapability( 'stripe', bkntc__('Stripe integration') );

	    if( ! Capabilities::tenantCan( 'stripe' ) )
		    return;

        Capabilities::register('stripe_settings' , bkntc__('Stripe settings') , 'settings');

		Stripe::load();
    }

    public function initBackend()
    {
	    if( ! Capabilities::tenantCan( 'stripe' ) )
		    return;

	    if( Capabilities::userCan('stripe_settings') )
        {
            TabUI::get('payment_gateways_settings')
                ->item('stripe')
                ->setTitle('Stripe')
                ->addView( __DIR__ . '/Backend/view/settings.php' );

            add_action( 'bkntc_enqueue_assets', [ self::class, 'enqueueAssets' ], 10, 2 );
            add_filter( 'bkntc_after_request_settings_save_payment_gateways_settings',  [ Listener::class , 'saveSettings' ]);
        }
    }

    public static function enqueueAssets ( $module, $action )
    {
        if( $module == 'settings' && $action == 'payment_gateways_settings' )
        {
            echo '<script type="application/javascript" src="' . self::loadAsset('assets/backend/js/stripe-settings.js') . '"></script>';
        }
    }

    public function initFrontend()
    {
	    if( ! Capabilities::tenantCan( 'stripe' ) )
		    return;

	    Listener::checkStripeCallback();

        add_action('bkntc_after_booking_panel_shortcode', function ()
        {
            wp_enqueue_script( 'booknetic-stripe-init', self::loadAsset('assets/frontend/js/init.js' ), [ 'booknetic' ] );
        });
    }

}