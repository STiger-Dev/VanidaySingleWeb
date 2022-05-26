<?php

namespace BookneticApp\Providers\Common;

use BookneticApp\Backend\Appointments\Helpers\AppointmentRequestData;
use BookneticApp\Backend\Appointments\Helpers\AppointmentRequests;
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

	public function when( $status, $appointmentRequests = null )
	{
		if( ! $status )
		{
			if ( Helper::getOption( 'hide_confirm_details_step', 'off' ) == 'on' )
			{
				return true;
			}

			if ( ! empty( $appointmentRequests ) && $appointmentRequests->getPayableToday() <= 0 )
			{
				return true;
			}
		}

		return $status;
	}

    /**
     * @param AppointmentRequests $appointmentRequests
     * @return object
     */
    public function doPayment( $appointmentRequests )
    {
        foreach ($appointmentRequests->appointments as $appointment)
        {
            foreach ($appointment->createdAppointments as $createdAppointmentId)
            {
                do_action('bkntc_appointment_before_mutation', null);
                do_action('bkntc_appointment_after_mutation', $createdAppointmentId);
            }
            do_action('bkntc_payment_confirmed', $appointment->getFirstAppointmentId());
        }

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