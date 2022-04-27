<?php

namespace BookneticApp\Backend\Appointments\Helpers;

use BookneticApp\Models\Appointment;
use BookneticApp\Models\AppointmentCustomer;
use BookneticApp\Models\AppointmentCustomerPrice;
use BookneticApp\Models\Customer;
use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\ServiceCategory;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\Helpers\Math;

class AppointmentCustomerSmartObject
{

	private static $appointmentCustomerDataById = [];

	private $appointmentCustomerId;

	/**
	 * @var AppointmentCustomer
	 */
	private $appointmentCustomerInf;

	/**
	 * @var Appointment
	 */
	private $appointmentInf;

	/**
	 * @var Staff
	 */
	private $staffInf;

	/**
	 * @var Location
	 */
	private $locationInf;

	/**
	 * @var Service
	 */
	private $serviceInf;

	/**
	 * @var ServiceCategory
	 */
	private $serviceCategoryInf;

	/**
	 * @var Customer
	 */
	private $customerInf;

	/**
	 * @var AppointmentCustomerPrice[]
	 */
	private $prices;

    /**
     * @var bool
     */
    private $noTenant;

    public function __construct( $appointmentCustomerId, $noTenant = false )
	{
		$this->appointmentCustomerId = $appointmentCustomerId;
        $this->noTenant = $noTenant;
	}

	/**
	 * AppointmentCustomer`in movcudlugunu ve
	 * Appointment`e permissionun movcudlugunu validate edir.
	 * Permission classindan avtomatik olaraq Appointment modeline staff_id`e gore filter elave edilir.
	 * Bu filterin admine aidiyyati yoxdu. Yalniz staff`lar uchundur ki, bir staff ancaq oz appointmentlerini gore bilsin.
	 *
	 * @return bool
	 */
	public function validate()
	{
		return $this->getInfo() && $this->getAppointmentInfo() ? true : false;
	}

	public static function load( $appointmentCustomerId, $noTenant = false )
	{
		/*if( ! isset( self::$appointmentCustomerDataById[ $appointmentCustomerId ] ) )
		{
		}
		*/
		self::$appointmentCustomerDataById[ $appointmentCustomerId ] = new AppointmentCustomerSmartObject( $appointmentCustomerId, $noTenant );;

		return self::$appointmentCustomerDataById[ $appointmentCustomerId ];
	}

	public function getInfo()
	{
		if( is_null( $this->appointmentCustomerInf ) )
		{
			$this->appointmentCustomerInf = AppointmentCustomer::get( $this->getId() );
		}

		return $this->appointmentCustomerInf;
	}

	public function getId()
	{
		return $this->appointmentCustomerId;
	}

	public function getAppointmentInfo()
	{
		if( is_null( $this->appointmentInf ) )
		{
			$this->appointmentInf = $this->getInfo() ? $this->getInfo()->appointment()->noTenant($this->noTenant)->fetch() : false;
		}

		return $this->appointmentInf;
	}

	public function getStaffInf()
	{
		if( is_null( $this->staffInf ) )
		{
			$this->staffInf = $this->getAppointmentInfo() ? $this->getAppointmentInfo()->staff()->noTenant($this->noTenant)->fetch() : false;
		}

		return $this->staffInf;
	}

	public function getServiceInf()
	{
		if( is_null( $this->serviceInf ) )
		{
			$this->serviceInf = $this->getAppointmentInfo() ? $this->getAppointmentInfo()->service()->noTenant($this->noTenant)->fetch() : false;
		}

		return $this->serviceInf;
	}

	public function getServiceCategoryInf()
	{
		if( is_null( $this->serviceCategoryInf ) )
		{
			$this->serviceCategoryInf = $this->getServiceInf() ? $this->getServiceInf()->category()->noTenant($this->noTenant)->fetch() : false;
		}

		return $this->serviceCategoryInf;
	}

	public function getLocationInf()
	{
		if( is_null( $this->locationInf ) )
		{
			$this->locationInf = $this->getAppointmentInfo() ? $this->getAppointmentInfo()->location()->noTenant($this->noTenant)->fetch() : false;
		}

		return $this->locationInf;
	}

	public function getCustomerInf()
	{
		if( is_null( $this->customerInf ) )
		{
			$this->customerInf = $this->getInfo() ? $this->getInfo()->customer()->noTenant($this->noTenant)->fetch() : false;
		}

		return $this->customerInf;
	}


	public function getPrices()
	{
		if( is_null( $this->prices ) )
		{
			$this->prices = $this->getInfo() ? $this->getInfo()->prices()->noTenant($this->noTenant)->fetchAll() : [];
		}

		return $this->prices;
	}

	public function getPrice( $uniqueKey )
	{
        $prices = $this->getPrices();

		foreach ( $prices AS $priceInf )
		{
			if( $uniqueKey == $priceInf->unique_key )
				return $priceInf;
		}

		return new Collection();
	}

	public function getTotalAmount( $sumForAllRecurringAppointments = false )
	{
		$subTotal = 0;

        if ( $sumForAllRecurringAppointments )
        {
            $appointmentCustomers = $this->getAllRecurringAppointmentCustomersId();
        }
        else
        {
            $appointmentCustomers = [ $this->getId() ];
        }

        foreach ( $appointmentCustomers as $appCustomerID )
        {
            $appointmentCustomerSmartObject = AppointmentCustomerSmartObject::load( $appCustomerID, $this->noTenant );

            foreach ( $appointmentCustomerSmartObject->getPrices() AS $priceInf )
            {
                $subTotal += $priceInf->price * $priceInf->negative_or_positive;
            }
        }

		return Math::floor( $subTotal );
	}

	public function getPaidAmount()
	{
		return Math::floor( $this->getInfo()->paid_amount );
	}

    public function getRealPaidAmount()
    {
        return Math::floor( $this->getInfo()->payment_status == 'paid' ? $this->getInfo()->paid_amount : 0 );
    }

	public function getDueAmount()
	{
		return Math::floor( $this->getTotalAmount() - $this->getRealPaidAmount() );
	}

    public function getAllRecurringAppointmentIds()
    {
        if( ! $this->getServiceInf()->is_recurring )
            return [ $this->getInfo()->id ];

        $appointmentCustomers   = AppointmentCustomer::where( 'recurring_id', $this->getInfo()->recurring_id )
            ->select(['appointment_id'], true)
            ->fetchAll();
        $idList         = [];

        foreach ( $appointmentCustomers AS $appointmentCustomer )
        {
            $idList[] = $appointmentCustomer->appointment_id;
        }

        return $idList;
    }

	public function getAllRecurringAppointmentCustomersId()
	{
		if( ! $this->getServiceInf()->is_recurring )
			return [ $this->getInfo()->id ];

		$appointmentCustomers   = AppointmentCustomer::where( 'recurring_id', $this->getInfo()->recurring_id )
            ->select(['id'], true)
            ->fetchAll();
		$idList         = [];

		foreach ( $appointmentCustomers AS $appointmentCustomer )
		{
			$idList[] = $appointmentCustomer->id;
		}

		return $idList;
	}


}