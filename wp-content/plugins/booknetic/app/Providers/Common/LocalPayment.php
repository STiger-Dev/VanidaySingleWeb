<?php

namespace BookneticApp\Providers\Common;

use BookneticApp\Backend\Appointments\Helpers\AppointmentRequestData;
use BookneticApp\Providers\Core\Backend;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Common\PaymentGatewayService;

class LocalPayment extends PaymentGatewayService
{

	protected $slug = 'local';


	public function __construct()
	{
		$this->setTitle( bkntc__('Local') );
		$this->setSettingsView( Backend::MODULES_DIR . 'Settings/view/modal/local_payment_settings.php' );
		$this->setIcon( Helper::icon( 'local.svg', 'front-end') );

		$this->init();

		add_action( 'bkntc_appointment_request_data_load', [ self::class, 'appointmentPayableToday' ]);
	}

	public function when( $status )
	{
		if( ! $status )
		{
			if( Helper::getOption('hide_confirm_details_step', 'off') == 'on' )
			{
				return true;
			}

			$appointmentData = AppointmentRequestData::getInstance();

			if( ! empty( $appointmentData ) && $appointmentData->getSubTotal( null, true ) <= 0 )
			{
				return true;
			}
		}

		return $status;
	}

    /**
     * @param AppointmentRequestData $appointmentObj
     * @return object
     */
    public function doPayment( $appointmentObj )
    {
        $appointmentCustomerId = $appointmentObj->getFirstAppointmentCustomerId();
        do_action( 'bkntc_payment_confirmed', $appointmentCustomerId );

        return (object) [
            'status' => true,
            'data'   => []
        ];
    }

    public static function appointmentPayableToday( AppointmentRequestData $appointmentObj )
    {
	    if( $appointmentObj->paymentMethod == 'local' )
	    {
		    $appointmentObj->setPayableToday( 0 );
	    }
    }

}