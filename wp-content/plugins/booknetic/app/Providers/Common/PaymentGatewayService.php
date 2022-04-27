<?php 

namespace BookneticApp\Providers\Common;

use BookneticApp\Backend\Appointments\Helpers\AppointmentCustomerSmartObject;
use BookneticApp\Backend\Appointments\Helpers\AppointmentRequestData;
use BookneticApp\Models\AppointmentCustomer;
use BookneticApp\Providers\Helpers\Helper;

class PaymentGatewayService
{

	/**
	 * @var PaymentGatewayService[]
	 */
	public static $gateways = [];

	protected $slug;
    protected $icon;
    protected $title;
    protected $settingsView;

	final public static function load()
	{
		$gatewayInstance = new static();
		$gatewayInstance->init();
	}

    public function setTitle( $title )
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setSlug( $slug )
    {
        $this->slug = $slug;

        return $this;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setIcon( $icon )
    {
        $this->icon = $icon;

        return $this;
    }

    public function setSettingsView( $view )
    {
        $this->settingsView = $view;

        return $this;
    }

    public function getIcon()
    {
        return $this->icon;
    }

    public function getSettingsView()
	{
		if( empty( $this->settingsView ) )
			return false;

        return $this->settingsView;
	}

    public function getPriority()
    {
        $gateways_order = Helper::getOption('payment_gateways_order', 'local');
        $gateways_order = explode(',', $gateways_order);
        
        if( in_array( $this->slug, $gateways_order ) )
        {
            return array_search( $this->slug, $gateways_order );
        }

        $gateways_order[] = $this->slug;

        Helper::setOption('payment_gateways_order',  implode( ',', $gateways_order ) );

        return $this->getPriority();
    }

    public function showSettings()
	{
		$view = $this->getSettingsView();

		if( !file_exists( $view ))
		{
			echo htmlspecialchars( $view ) . ' - view not exists!';
			return false;
		}

		include( $view );
	}

    public function isEnabled()
    {
    	$enabled = Helper::getOption( $this->slug . '_payment_enabled', 'off') === 'on';

    	if( method_exists( $this, 'when' ) )
	    {
	    	return $this->when( $enabled );
	    }

        return $enabled;
    }

    public function init()
    {
        self::$gateways[ $this->slug ] = $this;
    }

    /**
     * Override this method to accept incoming payment requests
     * @param AppointmentRequestData $appointmentObj
     * @return mixed
     */
    public function doPayment( $appointmentObj )
    {
        return null;
    }

    public static function find( $slug )
    {
        if( isset( self::$gateways[ $slug ] ) )
        {
            return self::$gateways[ $slug ];
        }

        return false;
    }

    public static function getEnabledGatewayNames()
    {
        $gateways = self::$gateways;
        $names    = [];

        if( count( $gateways ) > 0 )
        {
            foreach( $gateways as $slug => $gateway )
            {
                if( $gateway->isEnabled() )
                {
                    $names[] = $slug;
                }
            }

            if ( empty( $names ) )
            {
            	$names[] = 'local';
            }
        }

        return $names;
    }

    public static function getInstalledGatewayNames()
    {
        $gateways = self::$gateways;
        $names    = [];

        if( count( $gateways ) > 0 )
        {
            foreach( $gateways as $slug => $gateway )
            {
                $names[] = $slug;
            }
        }

        return $names;
    }

    /**
     * @param $getOnlyEnabledPaymentGateways
     * @param $getGatewaysForSettings
     * @return PaymentGatewayService[]
     */
    public static function getGateways ( $getOnlyEnabledPaymentGateways = false, $getGatewaysForSettings = false )
	{
        uasort( self::$gateways , function($g1, $g2)
        {
            return ( $g1->getPriority() == $g2->getPriority() ? 0 : ( $g1->getPriority() > $g2->getPriority() ? 1 : -1 ) );
        });

        $returnList = [];

        foreach ( self::$gateways AS $slug => $gateway )
        {
        	if( $getOnlyEnabledPaymentGateways && ! $gateway->isEnabled() )
		        continue;

        	if( $getGatewaysForSettings && $gateway->getSettingsView() === false )
        		continue;

	        $returnList[ $slug ] = $gateway;
        }

        if ( empty( $returnList ) )
        {
        	$returnList[ 'local' ] = self::$gateways[ 'local' ];
        }

        return $returnList;
	}


	public static function confirmPayment( $appointmentCustomerId )
	{
		$info                       =   AppointmentCustomerSmartObject::load( $appointmentCustomerId );
        $successAppointmentStatus   =   Helper::getOption('successful_payment_status');

        $updateData  = [ 'payment_status' => 'paid' ];

        if (!empty($successAppointmentStatus))
        {
            $updateData['status'] = $successAppointmentStatus;
        }

        AppointmentCustomer::where('id', $appointmentCustomerId)
            ->update( $updateData );

		if( $info->getServiceInf()->is_recurring == 1 && $info->getInfo()->recurring_payment_type == 'full' )
		{
            AppointmentCustomer::where('id', '!=', $appointmentCustomerId)
                ->where('recurring_id', $info->getInfo()->recurring_id)
                ->update( $updateData );
		}
		else
		{
            AppointmentCustomer::where('id', '!=', $appointmentCustomerId)
                ->where('recurring_id', $info->getInfo()->recurring_id)
                ->update( [ 'payment_status' => 'pending', 'paid_amount' => 0, 'payment_method' => 'local' ] );
		}

		do_action( 'bkntc_payment_confirmed', $appointmentCustomerId );
	}

	public static function cancelPayment( $appointmentCustomerId )
	{
		$info = AppointmentCustomerSmartObject::load( $appointmentCustomerId );

        $updateData = [
            'payment_status'    =>  'canceled'
        ];

        $failedStatus = Helper::getOption('failed_payment_status');
        if (!empty($failedStatus))
        {
            $updateData['status'] = $failedStatus;
        }

        AppointmentCustomer::where('recurring_id', $info->getInfo()->recurring_id)
		                   ->update($updateData);

		/**
		 * @doc bkntc_payment_confirmed Trigger events when payment canceled
		 */
		do_action( 'bkntc_payment_canceled', $appointmentCustomerId );
	}

}