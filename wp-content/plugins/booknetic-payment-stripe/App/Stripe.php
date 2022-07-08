<?php

namespace BookneticAddon\StripePaymentGateway;

use BookneticApp\Backend\Appointments\Helpers\AppointmentRequestData;
use BookneticApp\Backend\Appointments\Helpers\AppointmentRequests;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Common\PaymentGatewayService;
use BookneticApp\Providers\Core\Permission;
use Stripe\Exception\ApiErrorException;
use function BookneticAddon\StripePaymentGateway\bkntc__;

class Stripe extends PaymentGatewayService
{

	protected $slug = 'stripe';

	private $_paymentId;
	private $_successURL;
	private $_cancelURL;
    private $_itemList = [];


	public function __construct()
	{
		$this->setTitle( bkntc__( 'Credit card' ) );
		$this->setIcon( StripeAddon::loadAsset( 'assets/frontend/icons/stripe.svg' ) );

		\Stripe\Stripe::setApiKey( Helper::getOption('stripe_client_secret') );
	}

	public function when( $status, $appointmentRequests = null )
	{
		if( $status && Helper::getOption('hide_confirm_details_step', 'off') == 'on' )
		{
			return false;
		}

		return $status;
	}

	public function setId( $paymentId )
	{
		$this->_paymentId = $paymentId;

		return $this;
	}

    public function addItem( $price , $currency , $itemName , $itemImage )
    {
        $this->_itemList[] = [
            'price_data' => [
                'currency' => $currency,
                'unit_amount' => $this->normalizePrice( $price, $currency ),
                'product_data' => [
                    'name' => $itemName,
                    'images' => [$itemImage],
                ],
            ],
            'quantity' => 1
        ];
        return $this;
    }

	public function setSuccessURL( $url )
	{
		$this->_successURL = $url;

		return $this;
	}

	public function setCancelURL( $url )
	{
		$this->_cancelURL = $url;

		return $this;
	}

	private function createPaymentRequest()
	{
		try
		{
			$checkout_session = \Stripe\Checkout\Session::create([
				'payment_method_types' => ['card'],
				'line_items' => $this->_itemList,
				'mode' => 'payment',
				'success_url' => $this->_successURL,
				'cancel_url' => $this->_cancelURL,
				"metadata" => ['payment_id' => $this->_paymentId]
			]);
		}
		catch (ApiErrorException $e)
		{
			return 0;
		}

		return $checkout_session->id;
	}

    private function normalizePrice ( $price, $currency )
    {
        $currencies = [
            'BIF' => 1,
            'DJF' => 1,
            'JPY' => 1,
            'KRW' => 1,
            'PYG' => 1,
            'VND' => 1,
            'XAF' => 1,
            'XPF' => 1,
            'CLP' => 1,
            'GNF' => 1,
            'KMF' => 1,
            'MGA' => 1,
            'RWF' => 1,
            'VUV' => 1,
            'XOF' => 1,
            'ISK' => 1,
            'UGX' => 1,
            'UYI' => 1,

            'BHD' => 1000,
            'IQD' => 1000,
            'JOD' => 1000,
            'KWD' => 1000,
            'LYD' => 1000,
            'OMR' => 1000,
            'TND' => 1000,
        ];

        if ( array_key_exists( $currency, $currencies ) )
        {
            return $price * $currencies[ $currency ];
        }
        else
        {
            return $price * 100;
        }
    }

    /**
     * @param AppointmentRequests $appointmentRequests
     * @return object
     */
    public function doPayment( $appointmentRequests )
    {
	    $tenant_id_param = ( Helper::isSaaSVersion() ? '&tenant_id=' . Permission::tenantId() : '' );

		$this->setId( $appointmentRequests->paymentId  );
        foreach ( $appointmentRequests->appointments as $appointmentObj)
        {
            $this->addItem(
                $appointmentObj->getPayableToday( true ),
                Helper::getOption('currency', 'USD') ,
                $appointmentObj->serviceInf->name,
                Helper::profileImage( $appointmentObj->serviceInf->image, 'Services')
            );

        }
		$this->setSuccessURL(site_url() . '/?bkntc_stripe_status=success&bkntc_stripe_session_id={CHECKOUT_SESSION_ID}' . $tenant_id_param);
		$this->setCancelURL(site_url() . '/?bkntc_stripe_status=cancel&bkntc_stripe_session_id={CHECKOUT_SESSION_ID}' . $tenant_id_param);

		$stripeSessionId = $this->createPaymentRequest();

        $status = true;
        $data = [ 'url' => site_url() . '/?bkntc_stripe_session_id=' . $stripeSessionId . $tenant_id_param ];

        if( $stripeSessionId === 0 )
        {
            $status = false;
            $data = [ 'error_msg' => bkntc__( "Couldn't create a payment!" ) ];
        }

		return (object) [
			'status'    => $status,
			'data'      => $data
		];
    }

}