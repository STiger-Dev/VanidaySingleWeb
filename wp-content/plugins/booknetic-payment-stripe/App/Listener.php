<?php

namespace BookneticAddon\StripePaymentGateway;

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Common\PaymentGatewayService;
use Stripe\Exception\ApiErrorException;
use function BookneticAddon\StripePaymentGateway\bkntc__;

class Listener
{

	public static function saveSettings ( $response )
	{
		$stripe_client_id       = Helper::_post( 'stripe_client_id', '', 'string' );
		$stripe_client_secret   = Helper::_post( 'stripe_client_secret', '', 'string' );

        if ( PaymentGatewayService::find( 'stripe' )->isEnabled() && ( empty( $stripe_client_id ) || empty( $stripe_client_secret ) ) )
        {
            return Helper::response( false, bkntc__( 'Please, fill all fields to enable Stripe payment gateway!' ), true );
        }

		Helper::setOption( 'stripe_client_id', $stripe_client_id );
		Helper::setOption( 'stripe_client_secret', $stripe_client_secret );

		return $response;
	}

	public static function checkStripeCallback()
	{
        $sessionId				    = Helper::_get('bkntc_stripe_session_id', '', 'string');
        $bookneticStripeStatus      = Helper::_get('bkntc_stripe_status', false, 'string', ['success', 'cancel']);

        if (empty($sessionId))
            return;

        if (empty($bookneticStripeStatus))
        {
            echo '<script src="//js.stripe.com/v3/"></script>' .
                '<div>...</div>' .
                '<script type="text/javascript">
						var stripe = Stripe("'. htmlspecialchars(Helper::getOption('stripe_client_id')) .'");
						stripe.redirectToCheckout({ sessionId: "'.htmlspecialchars($sessionId).'" });
					</script>';
            exit();
        }

        try
        {
            $sessionInf = \Stripe\Checkout\Session::retrieve($sessionId);
        }
        catch (ApiErrorException $e)
        {
            exit;
        }

        if (
            (isset($sessionInf->payment_status) && $sessionInf->payment_status == 'paid') &&
            (isset($sessionInf->metadata) && isset($sessionInf->metadata->payment_id)) &&
            ($bookneticStripeStatus == 'success')
        )
        {
            PaymentGatewayService::confirmPayment( $sessionInf->metadata->payment_id );
            echo '<script>window.opener.bookneticPaymentStatus( true );</script>';
            exit;
        }

        if (
            (isset($sessionInf->payment_status) && $sessionInf->payment_status != 'paid') &&
            (isset($sessionInf->metadata) && isset($sessionInf->metadata->payment_id)) &&
            ($bookneticStripeStatus == 'cancel')
        )
        {
            PaymentGatewayService::cancelPayment( $sessionInf->metadata->payment_id );
            echo '<script>window.opener.bookneticPaymentStatus( false );</script>';
            exit;
        }

		exit;
	}

}