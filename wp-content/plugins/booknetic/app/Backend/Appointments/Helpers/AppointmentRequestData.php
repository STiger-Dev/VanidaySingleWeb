<?php

namespace BookneticApp\Backend\Appointments\Helpers;

use BookneticApp\Backend\Appointments\Helpers\AppointmentPriceObject;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\AppointmentCustomer;
use BookneticApp\Models\Customer;
use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\ServiceExtra;
use BookneticApp\Models\ServiceStaff;
use BookneticApp\Models\Staff;
use BookneticApp\Frontend\Controller\Ajax;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Math;
use BookneticApp\Providers\Common\PaymentGatewayService;
use BookneticApp\Providers\Core\Permission;

/**
 * Class AppointmentRequestData
 * @package BookneticApp\Backend\Appointments\Helpers
 *
 * @property Appointment|null $appointmentInf
 * @property Service|null $serviceInf
 * @property Staff|null $staffInf
 * @property Location|null $locationInf
 * @property ServiceStaff|null $serviceStaffInf
 */
class AppointmentRequestData
{

	/**
	 * @var AppointmentRequestData
	 */
	private static $appointmentDataInstance;

	/**
	 * @var AppointmentPriceObject[]
	 */
	private $prices;
	private $payableToday;

	public $appointmentId;

	public $date;
	public $time;

	public $note;

	public $locationId;
	public $staffId;
	public $serviceId;
	public $serviceCategoryId;

	private $serviceExtras;

	public $totalCustomerCount;

	public $customerData;
	public $customerId = -1;
    public $setBillingData = false;
	public $newCustomerPass;
	public $customers;

	public $paymentMethod;

	public $recurringStartDate;
	public $recurringEndDate;
	public $recurringTimes;
	public $recurringAppointmentsList;

	public $clientTimezone;

	public $createdAppointments = [];

	public $calledFromBackend = false;

	public $appointmentList;

	/**
	 * Magic methoddan istifade ederek ashagidaki Infolari collect edir.
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function __get( $name )
	{
		switch ( $name ){
			case 'appointmentInf':
				$this->appointmentInf = Appointment::get( $this->appointmentId );
				break;
			case 'serviceInf':
				$this->serviceInf = Service::get( $this->serviceId );
				break;
			case 'locationInf':
				$this->locationInf = Location::get( $this->locationId );
				break;
			case 'serviceStaffInf':
				$this->serviceStaffInf = ServiceStaff::where( 'service_id', $this->serviceId )->where( 'staff_id', $this->staffId )->fetch();
				break;
			case 'staffInf':
				$this->staffInf = Staff::get( $this->staffId );
				break;
		}

		return isset( $this->$name ) ? $this->$name : null;
	}

	public function __construct()
	{
		if( is_null( static::$appointmentDataInstance ) )
		{
			static::$appointmentDataInstance = $this;
		}
	}

	/**
	 * Singleton methodla cari obyektin tekrar init olmaginin qarshisi alinir
	 * Ve lazimi diger prosesler avtomatik ishe salinir...
	 *
	 * @return AppointmentRequestData
	 */
	public static function load( $calledFromBackend = false, $validateAllData = false )
	{
		if( is_null( static::$appointmentDataInstance ) )
		{
			static::$appointmentDataInstance = new AppointmentRequestData();

			if( $calledFromBackend )
			{
				static::$appointmentDataInstance->calledFromBackend();
			}

			static::$appointmentDataInstance->initDefaultProperties();
			static::$appointmentDataInstance->handleCustomerInfo();

			if( $validateAllData && ! static::$appointmentDataInstance->calledFromBackend )
			{
				static::$appointmentDataInstance->handleAnyStaffOption();
			}

			static::$appointmentDataInstance->initDefaultPrices();

			if( $validateAllData )
			{
				static::$appointmentDataInstance->validateBasicData();
				static::$appointmentDataInstance->validateRecurringData();
				static::$appointmentDataInstance->validateCustomerData();
				static::$appointmentDataInstance->validateAppointmentsAvailability();
			}

			do_action( 'bkntc_appointment_request_data_load', static::$appointmentDataInstance );
		}

		return static::$appointmentDataInstance;
	}

	public static function getInstance()
	{
		return self::$appointmentDataInstance;
	}

	/**
	 * POST Request ile gelen datalar burada emal olunaraq uygun propertieslere oturulur.
	 */
	public function initDefaultProperties()
	{
		$this->appointmentId				=	Helper::_post('id', 0, 'int');

		$this->locationId				    =	Helper::_post('location', 0, 'int');
		$this->staffId					    =	Helper::_post('staff', 0, 'int');
		$this->serviceId				    =	Helper::_post('service', 0, 'int');
		$this->serviceCategoryId	        =   Helper::_post('service_category', 0, 'int');

		$this->date						    =	Helper::_post('date', '', 'str');
		$this->time						    =	Helper::_post('time', '', 'str');

		$this->note						    =	Helper::_post('note', '', 'str');

		$this->totalCustomerCount		    =	Helper::_post('brought_people_count', 0 , 'int') + 1;

		$this->customerData				    =	Helper::_post('customer_data', [], 'arr');

		$this->paymentMethod			    =	Helper::_post('payment_method', '', 'str' );

		$this->clientTimezone			    =   Helper::_post('client_time_zone', '-', 'string');

		$this->recurringStartDate		    =	Helper::_post('recurring_start_date', '', 'string');
		$this->recurringEndDate			    =	Helper::_post('recurring_end_date', '', 'string');
		$this->recurringTimes			    =	Helper::_post('recurring_times', '', 'string');
		$this->recurringAppointmentsList	=	Helper::_post('appointments', '', 'string');

		$this->recurringAppointmentsList	=	json_decode( $this->recurringAppointmentsList, true );
		$this->recurringAppointmentsList	=	is_array( $this->recurringAppointmentsList ) ? $this->recurringAppointmentsList : [];

		$this->date                         = Date::reformatDateFromCustomFormat( $this->date );
		$this->recurringStartDate           = Date::reformatDateFromCustomFormat( $this->recurringStartDate );
		$this->recurringEndDate             = Date::reformatDateFromCustomFormat( $this->recurringEndDate );

		if( $this->calledFromBackend )
		{
			$this->paymentMethod = 'local';
		}

		if( $this->locationId == -1 )
		{
			$locationArr = (new Ajax())->get_data_location( true );
			$this->locationId = isset( $locationArr[0] ) ? $locationArr[0]['id'] : null;
		}

        // doit: bu niye elave olunub? frontda critical bug yaradirdi, commente alindi
//		if( $this->isDateBasedService() )
//		{
//			$this->time = '00:00';
//		}
	}


	public function getServiceExtras( $filterCustomerId = false )
	{
		if( is_null( $this->serviceExtras ) )
		{
			$serviceExtras = Helper::_post('service_extras', '[]', 'string');
            $serviceExtras = json_decode($serviceExtras, true);

			$this->serviceExtras = [];

			foreach ( $serviceExtras AS $extraDetails )
			{
				if( ! isset( $extraDetails['extra'] ) || ! isset( $extraDetails['quantity'] )
				    || ! ( is_numeric( $extraDetails['extra'] ) && $extraDetails['extra'] > 0 )
					|| ! ( is_numeric( $extraDetails['quantity'] ) && $extraDetails['quantity'] > 0 )
					|| ( $this->calledFromBackend && ! ( isset( $extraDetails['customer'] ) && is_numeric( $extraDetails['customer'] ) && $extraDetails['customer'] > 0 ) )
				)
				{
					continue;
				}

				$extraObj = ServiceExtra::where( 'service_id', $this->serviceId )->where( 'id', $extraDetails['extra'] )->fetch();

				if( $extraObj && $extraObj['max_quantity'] >= $extraDetails['quantity'] )
				{
					$extraObj['quantity'] = $extraDetails['quantity'];

					if( ! $this->calledFromBackend )
					{
						$extraObj['customer'] = $this->customerId;
					} else {
                        $extraObj['customer'] = $extraDetails['customer'];
                    }

					$this->serviceExtras[] = $extraObj;
				}
			}

            /*doit add_filter()*/
			$this->serviceExtras = apply_filters( 'bkntc_appointment_data_service_extras', $this->serviceExtras, $this );
		}

		if( ! $filterCustomerId )
		{
			return $this->serviceExtras;
		}

		$filteredExtras = [];
		foreach ( $this->serviceExtras AS $extra )
		{
			if( $filterCustomerId == $extra['customer'] )
			{
				$filteredExtras[] = $extra;
			}
		}

		return $filteredExtras;
	}

	public function getExtrasPrice( $filterCustomerId = false )
	{
		return ExtrasService::calcExtrasPrice( $this->getServiceExtras( $filterCustomerId ) );
	}

	public function getExtrasDuration( $filterCustomerId = false )
	{
		return ExtrasService::calcExtrasDuration( $this->getServiceExtras( $filterCustomerId ) );
	}

	/**
	 * Front-end booking zamani mushteri melumatlari yoxlanilir, onun yeni mushteri ve ya movcud mushteri olmasi arashdirilir.
	 */
	public function handleCustomerInfo()
	{
		if( $this->calledFromBackend )
		{
			$customers                  =	Helper::_post('customers', '', 'string');
			$this->customers            =	json_decode($customers, true);
			$this->totalCustomerCount   =   0;

			foreach ( $this->customers AS $customer )
			{
				if(
					! (
						isset( $customer['id'] ) && is_numeric($customer['id']) && $customer['id'] > 0
						&& isset( $customer['status'] ) && is_string($customer['status'])
						&& isset( $customer['number'] ) && is_numeric($customer['number']) && $customer['number'] >= 0
					)
				)
				{
					throw new \Exception( bkntc__('Please select customers!') );
				}

				if(
					$this->isEdit() &&
					! ( isset( $customer['ac_id'] ) && is_numeric($customer['ac_id']) && $customer['ac_id'] >= 0 )
				)
				{
					throw new \Exception( bkntc__('Please select customers!') );
				}

				$checkCustomerExists = Customer::get( $customer['id'] );
				if( ! $checkCustomerExists )
				{
					throw new \Exception( bkntc__('Please select customers!') );
				}

				if( $this->isEdit() && $customer['ac_id'] > 0 )
				{
					$checkAppointmentCustomerExists = AppointmentCustomer::get( $customer['ac_id'] );

					if( ! $checkAppointmentCustomerExists )
					{
						throw new \Exception( bkntc__('Please select customers!') );
					}
				}

                $busyStatuses = Helper::getBusyAppointmentStatuses();

				if ( in_array( $customer['status'] , $busyStatuses ) )
				{
                    $this->totalCustomerCount += (int)$customer['number'];
                }

            }
		}
		else
		{
		    if( ! empty( $this->customerData['email'] ) )
            {
                $wpUserId = Permission::userId();

                if ( $wpUserId > 0 )
                {
                    $checkCustomerExists = Customer::where('user_id', $wpUserId)->fetch();

                    if ( $checkCustomerExists )
                    {
                        if ( $checkCustomerExists->email != $this->customerData['email'] )
                        {
	                        throw new \Exception( bkntc__('You cannot use any email other than your own email.') );
                        }
                    }

                }
                else
                {
//                    if ( get_user_by('email', $this->customerData['email']) )
//                    {
//                        throw new \Exception( bkntc__('Please login and continue.') );
//                    }

                    $checkCustomerExists = Customer::where('email', $this->customerData['email'])->fetch();
                }

                if ( $checkCustomerExists )
                {
                    $this->customerId = $checkCustomerExists->id;

                    if (
                        ( $checkCustomerExists->phone_number != $this->customerData['phone'] && ! empty( $this->customerData['phone'] ) ) ||
                        ( $checkCustomerExists->first_name != $this->customerData['first_name'] && ! empty( $this->customerData['first_name'] ) ) ||
                        ( $checkCustomerExists->last_name != $this->customerData['last_name'] && ! empty( $this->customerData['last_name'] ) )
                    )
                    {
                        $this->setBillingData = true;
                    }
                }
            }

			$this->customers = [
				[
					'id'		=>	$this->customerId,
					'status'	=>	Helper::getDefaultAppointmentStatus(),
					'number'	=>	$this->totalCustomerCount,
				]
			];
		}
	}

	/**
	 * Front-end`de booking zamani yoxlanilir, eger yeni mushteridirse, o halda onun datalari bazaya elave olunur
	 * ve yeni mushteri olaraq qeydiyyatdan kechirilir.
	 */
	public function registerNewCustomer()
	{
		if( $this->customerId == -1 )
		{
			$wpUserId           = Permission::userId();
			$customerWPUserId   = $wpUserId > 0 ? $wpUserId : null;

            if( Helper::getOption('new_wp_user_on_new_booking', 'off', false) == 'on' && !empty( $this->customerData['email'] ) )
            {
                if( is_null( $customerWPUserId )  )
                {
                    $newCustomerPass = wp_generate_password( 8, false );

                    $customerWPUserId = wp_insert_user( [
                        'user_login'	=>	$this->customerData['email'],
                        'user_email'	=>	$this->customerData['email'],
                        'display_name'	=>	$this->customerData['first_name'] . ' ' . $this->customerData['last_name'],
                        'first_name'	=>	$this->customerData['first_name'],
                        'last_name'		=>	$this->customerData['last_name'],
                        'role'			=>	'booknetic_customer',
                        'user_pass'		=>	$newCustomerPass
                    ] );

                    if( is_wp_error( $customerWPUserId ) )
                    {
                        $customerWPUserId = null;
                    }
                    else if( !empty( $this->customerData['phone'] ) )
                    {
                        add_user_meta( $customerWPUserId, 'billing_phone', $this->customerData['phone'], true );
                    }
                }
                else
                {
                    $userInfo = wp_get_current_user();

                    if( ! Helper::checkUserRole( $userInfo, [ 'administrator', 'booknetic_customer', 'booknetic_staff', 'booknetic_saas_tenant' ] ) )
                    {
                        $userInfo->set_role('booknetic_customer');
                    }
                }
            }


			Customer::insert( [
				'user_id'		=>	$customerWPUserId,
				'first_name'	=>	$this->customerData['first_name'],
				'last_name'		=>	$this->customerData['last_name'],
				'phone_number'	=>	$this->customerData['phone'],
				'email'			=>	$this->customerData['email'],
				'created_at'	=>	date('Y-m-d'),
			] );

			$this->newCustomerPass    =  isset( $newCustomerPass ) ? $newCustomerPass : '';

			$this->customerId         = DB::lastInsertedId();
			$this->customers[0]['id'] = $this->customerId;

			if( isset( $this->prices[ -1 ] ) )
			{
				$this->prices[ $this->customerId ] = $this->prices[ -1 ];
				unset( $this->prices[ -1 ] );
			}

            if ( !empty($this->serviceExtras) )
            {
                foreach ($this->serviceExtras as $k => $serviceExtra)
                {
                    if ($serviceExtra['customer'] == -1)
                    {
                        $this->serviceExtras[$k]['customer'] = $this->customerId;
                    }
                }
            }

			do_action( 'bkntc_customer_created', $this->customerId, $this->newCustomerPass );
		}

	}

	/**
	 * Eger Any Staff optionu sechilibse o halda staffId=-1 olacaq.
	 * Sechilmish serviceId ve locationId`e uygun gelen butun stafflari listeleyir
	 * Ve o stafflarin her birinin sechilmish date & time`a uygunlugunu yoxlayir.
	 * Uygun gelen stafflardan 1-cisi staffId`e verilir.
	 * Qeyd: Stafflarin siralamasi Settingsdeki algoritmaya uygun olaraq alinir ki, 1-ci staffi goturende uygun staff gelsin.
	 */
	public function handleAnyStaffOption()
	{
		if( $this->staffId != -1 )
			return;

		$availableStaffIDs = AnyStaffService::staffByService( $this->serviceInf->id, $this->locationId, true, $this->date );

		foreach ( $availableStaffIDs AS $staffID )
		{
			$this->staffId          = $staffID;
			unset( $this->staffInf );
			$this->appointmentList  = null;

			$staffIsOkay            = true;

			foreach ( $this->getAllAppointments() AS $timeSlot )
			{
				if( ! $timeSlot->isBookable() )
				{
					$staffIsOkay = false;
					break;
				}
			}

			if( $staffIsOkay )
				break;

			$this->staffId          = -1;
			$this->appointmentList  = null;
			unset( $this->staffInf );
		}
	}

	/**
	 * Eger recurring deilse sechilmish date ve time`e uygun eks halda ise butun recurring date & time`lari arraya yigib geri qaytarir.
	 *
	 * @return TimeSlotService[]
	 */
	public function getAllAppointments()
	{
		if( is_null( $this->appointmentList ) )
		{
			$this->appointmentList      = [];
			$listAppointments           = $this->isRecurring() ? $this->recurringAppointmentsList : [ [ $this->date , $this->time ] ];

			foreach( $listAppointments AS $appointmentDateAndTime )
			{
				if( empty( $appointmentDateAndTime[0] ) || ( ! $this->isDateBasedService() && empty( $appointmentDateAndTime[1] ) ) )
					continue;

				$timeSlot = new TimeSlotService( $appointmentDateAndTime[0], $appointmentDateAndTime[1] );
				$timeSlot->setDefaultsFrom( $this );

				$this->appointmentList[] = $timeSlot;
			}
		}

		return $this->appointmentList;
	}

	/**
	 * Recurringi de nezere alaraq butun appointmentlerin sayi.
	 * Tebiiki recurring deyilse geri 1 qaytaracaq.
	 *
	 * @return int
	 */
	public function getAppointmentsCount()
	{
		return count( $this->getAllAppointments() );
	}

	/**
	 * Eger Service`nin settingsinde butun appointmentleri odemelidir deye sechilibse (The Customer pays full amount of recurring appointments):
	 * O halda butun appointmentlerin sayi qaytarilir, eks halda yalniz birinci appointmenti odemeli olacacg deye 1 qaytarilir.
	 *
	 * @return int
	 */
	public function getPayableAppointmentsCount()
	{
		if( $this->isRecurring() && $this->serviceInf->recurring_payment_type == 'first_month' )
			return 1;

		return $this->getAppointmentsCount();
	}

	/**
	 * Eger recurring appointmentdirse o halda sechilmish butun date&time`lari, eks halda yalniz 1 date&time sechilir.
	 * Her birinin booking uchun uygun oldugunu validasiya edir.
	 */
	public function validateAppointmentsAvailability()
	{
		foreach ( $this->getAllAppointments() AS $timeSlot )
		{
			if( ! $timeSlot->isBookable() )
			{
				throw new \Exception( bkntc__('Please select a valid time! ( %s %s is busy! )', [ $timeSlot->getDate( true ), $timeSlot->getTime( true ) ]) );
			}
		}
	}

	private function validateBasicData()
	{
		if(
			empty( $this->locationId )
			|| empty( $this->serviceId )
			|| empty( $this->staffId )
		)
		{
			throw new \Exception( bkntc__('Please fill in all required fields correctly!') );
		}

		if( ! $this->isRecurring() && ( ! Date::isValid( $this->date ) || ( ! $this->isDateBasedService() && ! Date::isValid( $this->time ) ) ) )
		{
			throw new \Exception( bkntc__('Please fill the "Date" and "Time" field correctly!') );
		}

		if( ! $this->serviceStaffInf )
		{
			throw new \Exception( bkntc__('Please fill in all required fields correctly!') );
		}

		if( $this->isEdit() && ! $this->appointmentInf )
		{
			throw new \Exception( bkntc__('Appointment not found or permission denied!') );
		}

		if( ! $this->locationInf || ! $this->staffInf )
		{
			throw new \Exception( bkntc__('Please fill in all required fields correctly!') );
		}

		$isStaffAllowed = Staff::where( 'id', $this->staffId )->whereFindInSet( 'locations', $this->locationInf->id )->fetch();

		if ( empty( $isStaffAllowed ) )
		{
			throw new \Exception( bkntc__('Please fill in all required fields correctly!') );
		}
	}

	private function validateCustomerData()
	{
		if( $this->calledFromBackend )
		{
			if( empty( $this->customers ) )
			{
				throw new \Exception( bkntc__('Please fill in all required fields correctly!') );
			}

			return;
		}

		$customer_inputs = ['first_name', 'last_name', 'email', 'phone'];

		foreach ( $customer_inputs AS $required_input_name )
		{
			if( !isset( $this->customerData[ $required_input_name ] ) || !is_string( $this->customerData[ $required_input_name ] ) )
			{
				throw new \Exception( bkntc__('Please fill in all required fields correctly!') );
			}
		}

		foreach ( $this->customerData AS $input_name => $customer_datum )
		{
			if( !((in_array( $input_name, $customer_inputs ) || ( strpos( $input_name, 'custom_field' ) === 0)) && is_string($customer_datum)) )
			{
				throw new \Exception( bkntc__('Please fill in all required fields correctly!') );
			}
		}

        $email_required = Helper::getOption('set_email_as_required', 'on');
        $phone_required = Helper::getOption('set_phone_as_required', 'off');

        if ( ! filter_var( $this->customerData['email'], FILTER_VALIDATE_EMAIL ) && $email_required === 'on')
        {
	        throw new \Exception( bkntc__('Please enter a valid email address!') );
        }

        if ( empty( $this->customerData['phone'] ) && $phone_required === 'on')
        {
	        throw new \Exception( bkntc__('Please enter a valid phone number!') );
        }
    }

	public function validateRecurringData()
	{
		if( ! $this->isRecurring() )
			return;

		if( ! empty( $this->recurringAppointmentsList ) )
		{
			foreach( $this->recurringAppointmentsList AS $appointmentElement )
			{
				if(
					!(
						isset( $appointmentElement[0] ) && is_string( $appointmentElement[0] ) && Date::isValid( $appointmentElement[0] ) &&
						isset( $appointmentElement[1] ) && is_string( $appointmentElement[1] ) && Date::isValid( $appointmentElement[1] )
					)
				)
				{
					throw new \Exception( bkntc__('Please fill in all required fields correctly!') );
				}
			}
		}
		else
		{
			if( empty( $this->recurringStartDate ) || empty( $this->recurringEndDate ) )
			{
				throw new \Exception( bkntc__('Please fill "Start date" and "End date" fields!') );
			}

			$recurringType      = $this->serviceInf['repeat_type'];
			$repeat_frequency   = (int)$this->serviceInf['repeat_frequency'];

			if( $recurringType == 'weekly' )
			{
				$recurringTimes = json_decode( $this->recurringTimes, true );

				if( !is_array( $recurringTimes ) || empty( $recurringTimes ) )
				{
					throw new \Exception( bkntc__('Please fill in all required fields correctly!') );
				}

				if( $repeat_frequency > 0 && $repeat_frequency != count( $recurringTimes ) )
				{
					throw new \Exception( bkntc__('Repeat frequency is %d for selected service!', [ (int)$repeat_frequency ]) );
				}
			}
			else if( $recurringType == 'daily' )
			{
				$everyNdays = (int)$this->recurringTimes;

				if( !( $everyNdays > 0 && $everyNdays < 99 ) )
				{
					throw new \Exception( bkntc__('Repeat frequency is is invalid!') );
				}

				if( $repeat_frequency > 0 && $repeat_frequency != $everyNdays )
				{
					throw new \Exception( bkntc__('Repeat frequency is %d for selected service!' , [ (int)$repeat_frequency ]) );
				}

				if( empty( $this->time ) || ! Date::isValid( $this->time ) )
				{
					throw new \Exception( bkntc__('Please fill "Time" field!') );
				}
			}
			else if( $recurringType == 'monthly' )
			{
				$recurringTimes = explode(':', (string)$this->recurringTimes);

				if( count( $recurringTimes ) !== 2 )
				{
					throw new \Exception( bkntc__('Please fill in all required fields correctly!') );
				}

				$monthlyType = $recurringTimes[0];
				$monthlyDays = $recurringTimes[1];

				if( $monthlyType == 'specific_day' )
				{
					$monthlyDays = empty($monthlyDays) ? [] : explode(',', $monthlyDays);
				}

				if( empty( $this->time ) || ! Date::isValid( $this->time ) || empty( $monthlyDays ) )
				{
					throw new \Exception( bkntc__('Please fill "Time" field!') );
				}
			}

			if( $this->serviceInf['full_period_value'] > 0 )
			{
				$fullPeriodValue	= (int)$this->serviceInf['full_period_value'];
				$fullPeriodType		= (string)$this->serviceInf['full_period_type'];

				if( $fullPeriodType == 'time' )
				{
					if( count( AppointmentService::getRecurringDates( $this ) ) != $this->serviceInf['full_period_value'] )
					{
						throw new \Exception( bkntc__('Please fill in all required fields correctly!') );
					}
				}
				else if( $fullPeriodType == 'day' || $fullPeriodType == 'week' || $fullPeriodType == 'month' )
				{
					$checkDate = Date::epoch( Date::epoch( $this->recurringStartDate, '+' . $fullPeriodValue . ' ' . $fullPeriodType ), '-1 days' );

					if( $checkDate != Date::epoch( $this->recurringEndDate ) )
					{
						throw new \Exception( bkntc__('Please fill in all required fields correctly!') );
					}
				}
				else
				{
					throw new \Exception( bkntc__('Error! Full period is wrong on Service options! Please edit your service info!') );
				}
			}
		}
	}


	/**
	 * Sechilmish servisin qiymetini geri qaytarir.
	 *
	 * @return float
	 */
	public function getServicePrice()
	{
		if ( $this->serviceStaffInf && $this->serviceStaffInf->price != -1 )
		{
			return Math::floor( $this->serviceStaffInf->price );
		}

		return Math::floor( $this->serviceInf->price );
	}

	/**
	 * Checkout uchun pricelari burdan idare ede bilersiz.
	 * Yeni price elave ede ve ya movcud priceni chagirib uzerinde editler etmek mumkundur.
	 * Her price oz uniqueKey-i ile tanidilir.
	 * Meselen eger "tax" adinda yeni bir price elave etmek isteyirsiznizse, onu bu methodla chagirib, sonra ise meblegi menimsede bilersiz.
	 * Yeginda elave etdiyiniz "tax" adli price da subTotal`in uzerinde toplanacaq avtomatik olarag.
	 * Bu qaydada sonsuz sayda pricelar elave etmek olar ve elave olunan her price da Confirm sehifesindeki checkoutda gorunecek.
	 *
	 * @param string $uniqueKey
	 *
	 * @return AppointmentPriceObject
	 */
	public function price( $uniqueKey, $customerId, $groupByKey = null )
	{
		if( ! isset( $this->prices[ $customerId ][ $uniqueKey ] ) )
		{
			if( !isset( $this->prices[ $customerId ] ) )
			{
				$this->prices[ $customerId ] = [];
			}

			$this->prices[ $customerId ][ $uniqueKey ] = new AppointmentPriceObject( $uniqueKey, $groupByKey );
			$this->prices[ $customerId ][ $uniqueKey ]->setAppointmentsCount( $this->getPayableAppointmentsCount() );
		}

		return $this->prices[ $customerId ][ $uniqueKey ];
	}

	/**
	 * Elave olunan price`larin siyahisini array sheklinde geri qaytarir.
	 *
	 * @param $customerId - Her customerin ferqli pricelari ola biler. Meselen 1de coupon var, digerlerinde yoxdu. O sebebden CustoemrId`e gore filter vacibdi.
	 * @param false $groupPrices - Pricelari hansi parametre gore quruplashdirmagi mueyyenleshdirir. Esasen service_extra`lar uchun istifade olunur. Chunki servis extralar front-endde her biri ayri row kimi dushduyu teqdirde database`e insert zamani onlar qruplashir ve "service_extra" ile insert olunur. Insert zamani bura true gelir, diger hallarda false.
	 *
	 * @return AppointmentPriceObject[]
	 */
	public function getPrices( $customerId = null, $groupPrices = false )
	{
		$customerId = is_null( $customerId ) ? $this->customerId : $customerId;

		$prices = ! ( isset( $this->prices[ $customerId ] ) && is_array( $this->prices[ $customerId ] ) ) ? [] : $this->prices[ $customerId ];

		if( ! $groupPrices || empty( $prices ) )
			return $prices;

		/**
		 * @var $groupedPrices AppointmentPriceObject[]
		 */
		$groupedPrices = [];

		foreach ( $prices AS $priceKey => $priceObj )
		{
			if( ! is_null( $priceObj->getGroupByKey() ) )
			{
				$priceKey = $priceObj->getGroupByKey();
			}

			if( isset( $groupedPrices[ $priceKey ] ) )
			{
				$groupedPrices[ $priceKey ]->setPrice( $groupedPrices[ $priceKey ]->getPrice() + $priceObj->getPrice() );
			}
			else
			{
				$groupedPrices[ $priceKey ] = $priceObj;
			}
		}

		return $groupedPrices;
	}

	/**
	 * Confirmation stepindeki checkout sectionu uchun pricelarin sirali HTML formasi...
	 *
	 * @return string
	 */
	public function getPricesHTML( $customerId = null )
	{
		$pricesHTML = '';

		foreach ( $this->getPrices( $customerId ) AS $price )
		{
			$pricesHTML .= '<div class="booknetic_confirm_details ' . ($price->isHidden() ? ' booknetic_hidden' : '') . '" data-price-id="' . htmlspecialchars($price->getId()) . '">
				<div class="booknetic_confirm_details_title">' . $price->getLabel() . '</div>
				<div class="booknetic_confirm_details_price">' . $price->getPriceView( true ) . '</div>
			</div>';
		}

		return $pricesHTML;
	}

	/**
	 * Butun menimsedilen price`larin toplamini geri qaytarir.
	 * Default olaraq 1 appointment uchun toplam meblegi geri qaytarir.
	 * Eger recurring appointmentdirse ve butun appointmentlerin cemi toplamini almag isteyirsizse argumente true deyerini gonderin.
	 *
	 * @param bool $sumForAllRecurringAppointments
	 *
	 * @return float
	 */
	public function getSubTotal( $customerId = null, $sumForAllRecurringAppointments = false )
	{
		$subTotal = 0;

		foreach ( $this->getPrices( $customerId ) AS $priceInf )
		{
			$subTotal += $priceInf->getPrice( $sumForAllRecurringAppointments );
		}

		return $subTotal;
	}

	/**
	 * Paymente gonderilecek hisseni geri qaytarir.
	 * Meselen subTotal 100$ edirse ve 50% deposit varsa, paymente 50$ gedecek.
	 * Default olaraq 1 appointment uchun olan meblegi geri qaytarir.
	 * Eger recurring appointmentdirse ve butun appointmentlerin toplamini almag isteyirsizse argumente true deyerini gonderin.
	 *
	 * @param bool $sumForAllRecurringAppointments
	 *
	 * @return float
	 */
	public function getPayableToday( $sumForAllRecurringAppointments = false )
	{
		if( is_null( $this->payableToday ) )
		{
			if( $this->hasDeposit() )
			{
				$payableToday = $this->getDepositPrice();
			}
			else
			{
				$payableToday = $this->getSubTotal();
			}

			$payableToday = Math::floor( $payableToday );
		}
		else
		{
			$payableToday = $this->payableToday;
		}

		return $sumForAllRecurringAppointments ? Math::floor( $payableToday * $this->getPayableAppointmentsCount() ) : $payableToday;
	}

	/**
	 * Odemeye gonderilecek meblegi deyishdirmek uchun olan method.
	 *
	 * @param float $amount
	 */
	public function setPayableToday( $amount )
	{
		$this->payableToday = $amount;
	}

	/**
	 * Odemeye geden meblegin vergi hissesini geri qaytarir.
	 * Meselen Paypal-da API ile vergi hissesini ayri menimsetmek lazimdi.
	 * Default olaraq 1 appointment uchun olan meblegi geri qaytarir.
	 * Eger recurring appointmentdirse ve butun appointmentlerin toplamini almag isteyirsizse argumente true deyerini gonderin.
	 *
	 * @param bool $sumForAllRecurringAppointments
	 *
	 * @return float
	 */
	public function getPayableTodayPrice( $priceUniqueKey, $sumForAllRecurringAppointments = false )
	{
		$price = $this->getSubTotal( null, $sumForAllRecurringAppointments ) > 0 ? $this->getPayableToday( $sumForAllRecurringAppointments ) / $this->getSubTotal( null, $sumForAllRecurringAppointments ) * $this->price( $priceUniqueKey, $this->customerId )->getPrice( $sumForAllRecurringAppointments ) : 0;

		return Math::floor( $price );
	}

	/**
	 * Deposit odenilmeli olan meblegi geri qaytarir
	 * Default olaraq 1 appointment uchun olan meblegi geri qaytarir.
	 * Eger recurring appointmentdirse ve butun appointmentlerin toplamini almag isteyirsizse argumente true deyerini gonderin.
	 *
	 * @param bool $sumForAllRecurringAppointments
	 *
	 * @return float
	 */
	public function getDepositPrice( $sumForAllRecurringAppointments = false )
	{
		if( $this->serviceStaffInf['price'] == -1 )
		{
			$deposit		= $this->serviceInf['deposit'];
			$deposit_type	= $this->serviceInf['deposit_type'];
		}
		else
		{
			$deposit		= $this->serviceStaffInf['deposit'];
			$deposit_type	= $this->serviceStaffInf['deposit_type'];
		}

		if( $deposit_type == 'price' )
		{
			return $sumForAllRecurringAppointments ? Math::floor( $deposit * $this->getPayableAppointmentsCount() ) : Math::floor( $deposit );
		}
		else
		{
			return Math::floor( $this->getSubTotal( null, $sumForAllRecurringAppointments ) * $deposit / 100 );
		}
	}

	/**
	 * Deposit odenishin olub olmadigini check etmek uchundur.
	 *
	 * @return bool
	 */
	public function hasDeposit()
	{
		if( $this->serviceStaffInf['price'] == -1 )
		{
			$deposit		= $this->serviceInf['deposit'];
			$deposit_type	= $this->serviceInf['deposit_type'];
		}
		else
		{
			$deposit		= $this->serviceStaffInf['deposit'];
			$deposit_type	= $this->serviceStaffInf['deposit_type'];
		}

		if( $deposit == 0 )
			return false;

		if( $deposit_type == 'price' && $deposit == $this->getServicePrice() )
			return false;

		if( $deposit_type == 'percent' && ($deposit <= 0 || $deposit >= 100) )
			return false;

		if( Helper::_post('deposit_full_amount', 0, 'int', [ 0, 1 ]) === 1 && Helper::getOption('deposit_can_pay_full_amount', 'on') == 'on' )
			return false;

		return true;
	}

	/**
	 * Default pricelari menimsetmek uchundur. Meselen: "service_price", extralarin qiymetleri ve s.
	 */
	public function initDefaultPrices()
	{
		if( empty( $this->serviceId ) )
			return;

		foreach ( $this->customers as $customer )
		{
			$customerId = $customer['id'];

			$servicePrice = $this->price( 'service_price', $customerId );
			$servicePrice->setPrice( $this->getServicePrice() * $customer['number'] );
			$servicePrice->setLabel( $this->serviceInf->name . ( $customer['number'] > 1 ? ' [x' . $customer['number'] . ']' : '' ) );

			foreach ( $this->getServiceExtras($customerId) as $extra )
			{
				$addExtraPrice = $this->price( 'service_extra_' . $extra['id'], $customerId, 'service_extra' );
				$addExtraPrice->setPrice( $extra['price'] * $extra['quantity'] );
				$addExtraPrice->setLabel( $extra['name'].' [x' . ( $extra['quantity'] ) . ']' );
			}

			$discountPrice = $this->price( 'discount', $customerId );
			$discountPrice->setPrice( 0 );
			$discountPrice->setLabel( bkntc__('Discount') );
			$discountPrice->setNegativeOrPositive(-1);

			if( Helper::getOption('hide_discount_row', 'off') == 'on' )
			{
				$discountPrice->setHidden( true );
			}
		}
	}


	public function isRecurring()
	{
        if (empty($this->serviceInf['is_recurring']))
            return false;

		return $this->serviceInf['is_recurring'] == 1 && ! $this->isEdit();
	}

	public function isDateBasedService()
	{
		return $this->serviceInf['duration'] >= 24 * 60;
	}


	public function getDateTimeView()
	{
		if( $this->isRecurring() )
		{
			$dateTimeView = Date::datee( $this->recurringStartDate ) . ' / ' . Date::datee( $this->recurringEndDate );
		}
		else if( ! $this->isDateBasedService() )
		{
			$dateTime = $this->date . ' ' . $this->time;

			$dateTimeView = Date::datee( $dateTime, false, true ) . ' / ' . Date::time( $dateTime, false, true );

			if( Helper::getOption('time_view_type_in_front', '1') == '1' )
			{
				$dateTimeView .= '-' . Date::time( $dateTime, '+' . $this->getAppointmentDuration() . ' minutes', true );
			}
		}
		else
		{
			$dateTime = $this->date . ' ' . $this->time;

			$dateTimeView = Date::datee( $dateTime, false, true );

		}

		return $dateTimeView;
	}

	/**
	 * Appointmentin umumi muddetini hesablayir
	 *
	 * @return int
	 */
	public function getAppointmentDuration()
	{
		return $this->serviceInf->duration + $this->getExtrasDuration();
	}

	/**
	 * Recurring ola bileceyini de nezere alaraq ilk appointmentin ID-sini geri qaytarir.
	 *
	 * @return int
	 */
	public function getFirstAppointmentId()
	{
		$createdAppointmentIDs = array_keys( $this->createdAppointments );

		return reset( $createdAppointmentIDs );
	}

	/**
	 * Recurring ola bileceyini de nezere alaraq ilk appointmentin ilk appointmentCustomerId`sini geri qaytarir
	 *
	 * @return int
	 */
	public function getFirstAppointmentCustomerId()
	{
		$firstAppointment = reset( $this->createdAppointments );

		return isset( $firstAppointment[0] ) ? $firstAppointment[0] : 0;
	}

	/**
	 * Back-end`den load olursa eger bu object o halda bu methoda true deyeri gonderilmelidir.
	 *
	 * @param bool $isBackend
	 *
	 * @return $this
	 */
	public function calledFromBackend( $bool = true )
	{
		$this->calledFromBackend = $bool;

		return $this;
	}

	/**
	 * Appointmentin editi prosesi olub olmadigini yoxlamag uchun method.
	 *
	 * @return bool
	 */
	public function isEdit()
	{
		return $this->calledFromBackend && $this->appointmentId > 0;
	}

}
