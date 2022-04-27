<?php

namespace BookneticApp\Backend\Appointments;

use BookneticApp\Config;
use BookneticApp\Providers\Common\PaymentGatewayService;
use BookneticApp\Providers\UI\TabUI;
use BookneticApp\Backend\Appointments\Helpers\AppointmentCustomerSmartObject;
use BookneticApp\Backend\Appointments\Helpers\AppointmentRequestData;
use BookneticApp\Backend\Appointments\Helpers\AppointmentService;
use BookneticApp\Backend\Appointments\Helpers\CalendarService;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\AppointmentCustomer;
use BookneticApp\Models\AppointmentExtra;
use BookneticApp\Models\Customer;
use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\ServiceCategory;
use BookneticApp\Models\ServiceExtra;
use BookneticApp\Models\ServiceStaff;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;

class Ajax extends \BookneticApp\Providers\Core\Controller
{

	public function add_new ()
	{
		Capabilities::must( 'appointments_add' );

		$date           = Helper::_post('date', '', 'string');
		$locations      = Location::where('is_active', 1)->fetchAll();
		$locationInf    = count( $locations ) == 1 ? $locations[0] : false;

        TabUI::get( 'appointments_add_new' )
             ->item( 'details' )
             ->setTitle( bkntc__( 'Appointment details' ) )
             ->addView( __DIR__ . '/view/tab/details.php' )
             ->setPriority( 1 );

        TabUI::get( 'appointments_add_new' )
             ->item( 'extras' )
             ->setTitle( bkntc__( 'Extras' ) )
             ->addView( __DIR__ . '/view/tab/extras.php' )
             ->setPriority( 2 );

        $data = [
            'location'  => $locationInf,
            'date'      => $date,
        ];

		return $this->modalView( 'add_new', [
            'data' => $data
        ] );
	}

	public function create_appointment()
	{
		Capabilities::must( 'appointments_add' );

        $run_workflows = Helper::_post('run_workflows', 1, 'num');
        Config::getWorkflowEventsManager()->setEnabled($run_workflows === 1);

		try
		{
			$appointmentData = AppointmentRequestData::load( true, true );
		}
		catch ( \Exception $e )
		{
			return $this->response( false, $e->getMessage() );
		}

		if( $appointmentData->isRecurring() && empty( $appointmentData->recurringAppointmentsList ) )
		{
			return $this->response(true, [ 'dates' => AppointmentService::getRecurringDates( $appointmentData ) ]);
		}

		try
		{
			do_action( 'bkntc_before_appointment_created' );
		}
		catch ( \Exception $e )
		{
			return $this->response( false, $e->getMessage() );
		}

		AppointmentService::createAppointment( $appointmentData );

        /*doit add_action()*/
		do_action( 'bkntc_after_appointment_created' , $appointmentData );

        PaymentGatewayService::find('local')->doPayment($appointmentData);

		return $this->response(true );
	}

	public function edit ()
	{
		Capabilities::must( 'appointments_edit' );

		$id = Helper::_post( 'id', '0', 'integer' );

		$appointmentInfo = Appointment::leftJoin( 'location',   [ 'name' ] )
                                      ->leftJoin( 'service',    [ 'name' ] )
                                      ->leftJoin( 'staff',      [ 'name' ] )
                                      ->where( Appointment::getField( 'id' ), $id )
                                      ->fetch();

		if( ! $appointmentInfo )
		{
            return $this->response( false, bkntc__( 'Selected appointment not found!' ) );
		}

		// get service categories...
		$serviceInfo = Service::get( $appointmentInfo[ 'service_id' ] );

		$categories = [];

		$categoryId = $serviceInfo['category_id'];
		$deep = 15;
		while( true )
		{
			$categoryInf = ServiceCategory::get( $categoryId );
			$categories[] = $categoryInf;

			$categoryId = (int)$categoryInf['parent_id'];

			if( ($deep--) < 0 || $categoryId <= 0 )
			{
				break;
			}
		}

		// get customers list and info
		$getCustomers = DB::DB()->get_results(
			DB::DB()->prepare( '
				SELECT 
					tb1.* , (SELECT CONCAT(`first_name`, \' \', `last_name`) FROM `' . DB::table('customers') . '` WHERE id=tb1.customer_id) AS customer_name
				FROM ' . DB::table('appointment_customers') . ' tb1
				WHERE tb1.appointment_id=%d', [ $id ]
			),
			ARRAY_A
		);

        TabUI::get( 'appointments_edit' )
             ->item( 'details' )
             ->setTitle( bkntc__( 'Appointment details' ) )
             ->addView( __DIR__ . '/view/tab/edit_details.php' )
             ->setPriority( 1 );

        TabUI::get( 'appointments_edit' )
             ->item( 'extras' )
             ->setTitle( bkntc__( 'Extras' ) )
             ->addView( __DIR__ . '/view/tab/edit_extras.php' )
             ->setPriority( 2 );

        $data = [
            'id'            => $id,
            'service'       => $serviceInfo,
            'appointment'   => $appointmentInfo,
            'customers'     => $getCustomers,
            'categories'    => array_reverse( $categories )
        ];

		return $this->modalView( 'edit', [
            'data'           => $data,
			'id'				=> $id,
			'service_capacity'	=> $serviceInfo['max_capacity'],
		] );
	}

	public function save_edited_appointment()
	{
		Capabilities::must( 'appointments_edit' );

        $run_workflows = Helper::_post('run_workflows', 1, 'num');
        Config::getWorkflowEventsManager()->setEnabled($run_workflows === 1);

		try
		{
			$appointmentObj = AppointmentRequestData::load( true, true );
		}
		catch ( \Exception $e )
		{
			return $this->response( false, $e->getMessage() );
		}

		do_action( 'bkntc_appointment_before_edit', $appointmentObj->appointmentId );

		AppointmentService::editAppointment( $appointmentObj );

		do_action( 'bkntc_appointment_after_edit', $appointmentObj->appointmentId );

		return $this->response(true, ['id' => $appointmentObj->appointmentId]);
	}

	public function info()
	{
		Capabilities::must( 'appointments' );

		$id = Helper::_post('id', '0', 'integer');

		$locationSubQuery = Location::select('name')->where('id', DB::field(DB::table('appointments').'.location_id'));
		$serviceSubQuery = Service::select('name')->where('id', DB::field(DB::table('appointments').'.service_id'));
		$appointmentInfo = Appointment::select('*')
		                              ->selectSubQuery( $locationSubQuery, 'location_name' )
		                              ->selectSubQuery( $serviceSubQuery, 'service_name' )
		                              ->leftJoin('staff', ['id', 'name', 'profile_image', 'email', 'phone_number'],DB::table('staff').'.id', DB::table('appointments').'.staff_id')->where(DB::table('appointments').'.id', $id)->fetch();

		if( !$appointmentInfo )
		{
			return $this->response(false, bkntc__('Appointment not found!'));
		}

		$customers = AppointmentCustomer::where( AppointmentCustomer::getField('appointment_id'), $id )
		                                ->leftJoin('customers', ['first_name', 'last_name', 'phone_number', 'email', 'profile_image'], AppointmentCustomer::getField('customer_id'), Customer::getField('id'))->fetchAll();


		$customersArr = [];

		foreach( $customers AS $customerInfKey => $customerInf )
		{
			$customersArr[] = (int)$customerInf['customer_id'];

			$customerBillingData = json_decode(  AppointmentCustomer::getData( $customerInf['id'], 'customer_billing_data', "" ) );

			$billingFirstName = ( empty($customerBillingData->customer_first_name) ? "" :  $customerBillingData->customer_first_name);
			$billingLastName = ( empty($customerBillingData->customer_last_name) ? "" :  $customerBillingData->customer_last_name);
			$billingPhone = ( empty($customerBillingData->customer_phone) ? "" :  $customerBillingData->customer_phone);

			if (!empty($billingFirstName) && !empty($billingLastName))
			{
				$customers[ $customerInfKey ] ['billing_full_name'] = $billingFirstName . ' ' . $billingLastName;
			}
			$customers[ $customerInfKey ] ['billing_phone'] = $billingPhone;
		}

		$extrasArr = [];

		foreach( $customers AS $customerInfKey => $customerInf )
		{
			$customerId = (int)$customerInf['customer_id'];

			$extras = AppointmentExtra::where('appointment_customer_id', $customerInf['id'])
			                          ->leftJoin(ServiceExtra::class, ['name'], ServiceExtra::getField('id'), AppointmentExtra::getField('extra_id'))
			                          ->fetchAll();

			if (count($extras) === 0)
			{
				continue;
			}

			$extrasArr[ $customerId ] = [
				'name'			=>	$customerInf['customers_first_name'] . ' ' . $customerInf['customers_last_name'],
				'profile_image'	=>	$customerInf['customers_profile_image'],
				'email'			=>	$customerInf['customers_email'],
				'phone_number'	=>	$customerInf['customers_phone_number'],
				'extras'		=>	$extras
			];
		}

        TabUI::get( 'appointments_info' )
             ->item( 'details' )
             ->setTitle( bkntc__( 'Appointment details' ) )
             ->addView( __DIR__ . '/view/tab/info_details.php' )
             ->setPriority( 1 );

        TabUI::get( 'appointments_info' )
             ->item( 'extras' )
             ->setTitle( bkntc__( 'Extras' ) )
             ->addView( __DIR__ . '/view/tab/info_extras.php', [
                 'extras' => $extrasArr
             ] )
             ->setPriority( 2 );

        $data = [
            'id'            => $id,
            'info'          => $appointmentInfo,
            'customers'     => $customers,
        ];

		return $this->modalView( 'info', [
            'data'  => $data,
			'id'    => $id,
		] );
	}

	public function group_payments_info()
	{
		Capabilities::must( 'payments' );

		$appointment_id     = Helper::_post('id', '0', 'integer');
		$appointment        = Appointment::get( $appointment_id );

		if( ! $appointment )
		{
			return $this->response(false, bkntc__('Appointment not found or permission denied!'));
		}

		$appointment_customers = AppointmentCustomer::where('appointment_id', $appointment_id)->fetchAll();

		return $this->modalView( 'group_payments_info', [
			'appointment' => $appointment,
			'appointment_customers' => $appointment_customers,
			'staff_name' => $appointment->staff()->fetch()['name'],
			'location_name' => $appointment->location()->fetch()['name'],
			'service_name' => $appointment->service()->fetch()['name'],
			'info' => AppointmentCustomerSmartObject::load(222)
		] );
	}

	public function get_services()
	{
		$search		= Helper::_post('q', '', 'string');
		$category	= Helper::_post('category', '', 'int');

		$addFilter = '';
		$filters = [ '%' . $search . '%' ];

		if( !empty( $category ) )
		{
			$addFilter .= ' AND category_id=%d';
			$filters[] = (int)$category;
		}

		if ( ! Permission::isAdministrator() )
		{
			$addFilter .= ' AND ( SELECT `service_id` FROM '. DB::table( 'service_staff' ) .' WHERE `service_id` = `service`.`id` AND `staff_id` IN ( '.implode( ', ', Permission::myStaffId() ).' ) ) IS NOT NULL ';
		}

		$services = DB::DB()->get_results(
			DB::DB()->prepare( "SELECT `service`.* FROM " . DB::table( 'services' ) . " `service` WHERE `is_active` = 1 AND `name` LIKE %s " . $addFilter . DB::tenantFilter(), $filters ),
			ARRAY_A
		);

		$data = [];

		foreach ( $services AS $service )
		{
			$data[] = [
				'id'				=>	(int)$service['id'],
				'text'				=>	htmlspecialchars($service['name']),
				'repeatable'		=>	(int)$service['is_recurring'],
				'repeat_type'		=>	htmlspecialchars( $service['repeat_type'] ),
				'repeat_frequency'	=>	htmlspecialchars( $service['repeat_frequency'] ),
				'full_period_type'	=>	htmlspecialchars( $service['full_period_type'] ),
				'full_period_value'	=>	(int)$service['full_period_value'],
				'max_capacity'		=>	(int)$service['max_capacity'],
				'date_based'		=>	$service['duration'] >= 1440
			];
		}

		return $this->response(true, [ 'results' => $data ]);
	}

	public function get_locations()
	{
		$search		= Helper::_post('q', '', 'string');
		$locations  = Location::where('is_active', 1)->where('name', 'LIKE', '%' . $search . '%')->fetchAll();

		$data = [];

		foreach ( $locations AS $location )
		{
			$data[] = [
				'id'	=> (int)$location['id'],
				'text'	=> htmlspecialchars($location['name'])
			];
		}

		return $this->response(true, [ 'results' => $data ]);
	}

	public function get_service_categories()
	{
		$search		= Helper::_post('q', '', 'string');
		$category	= Helper::_post('category', 0, 'int');

        $filters = [ '%' . $search . '%' , (int)$category ];

        $services = DB::DB()->get_results(
            DB::DB()->prepare( "SELECT *, (SELECT COUNT(0) FROM " . DB::table('service_categories') . " WHERE parent_id=tb1.id) AS sub_categs FROM " . DB::table('service_categories') . " tb1 WHERE `name` LIKE %s AND parent_id=%d" . DB::tenantFilter() , $filters ),
            ARRAY_A
        );

		$data = [];

            foreach ( $services AS $service )
            {
                $data[] = [
                    'id'                => (int)$service['id'],
                    'text'                => htmlspecialchars($service['name']),
                    'have_sub_categ'    => $service['sub_categs']
                ];
            }

		return $this->response(true, [ 'results' => $data ]);
	}

	public function get_staff()
	{
		$search		= Helper::_post('q', '', 'string');
		$location	= Helper::_post('location', 0, 'int');
		$service	= Helper::_post('service', 0, 'int');

		$staff = Staff::where('is_active', 1)
		                 ->where('name', 'like', "%$search%");

		if( !empty( $location ) )
		{
			$staff->whereFindInSet( 'locations', $location );
		}

		if( !empty( $service ) )
		{
			$serviceStaffSubQuery = ServiceStaff::where( 'service_id', $service )->select('staff_id');
			$staff->where( 'id', 'IN', $serviceStaffSubQuery );
		}

		$staff = $staff->fetchAll();

		$data = [];
		foreach ( $staff AS $staffInf )
		{
			$data[] = [
				'id'	=> (int)$staffInf['id'],
				'text'	=> htmlspecialchars($staffInf['name'])
			];
		}

		return $this->response(true, [ 'results' => $data ]);
	}

	public function get_customers()
	{
		$search = Helper::_post('q', '', 'string');

		$customers = Customer::my();

		if( !empty( $search ) )
		{
			$customers = $customers->where(function ( $query ) use ( $search )
			{
				$query->where('CONCAT(`first_name`, \' \', `last_name`)', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
			});
		}

		$customers = $customers->limit(100)->fetchAll();

		$data = [];

		foreach ( $customers AS $service )
		{
			$data[] = [
				'id'	=> (int)$service['id'],
				'text'	=> htmlspecialchars($service['first_name'] . ' ' . $service['last_name'])
			];
		}

		return $this->response(true, [ 'results' => $data ]);
	}

	public function get_available_times( $calledFromBackend = true )
	{
		$id				= Helper::_post('id', -1, 'int');
		$search			= Helper::_post('q', '', 'string');

        $location		= Helper::_post('location', 0, 'int');
		$service		= Helper::_post('service', 0, 'int');
		$staff			= Helper::_post('staff', 0, 'int');
		$date			= Helper::_post('date', '', 'string');

		$date           = Date::reformatDateFromCustomFormat( $date );

		$service_extras	= Helper::_post('service_extras', '[]', 'string');
		$service_extras	= json_decode($service_extras, true);

		$extras_arr	= [];
		foreach ( $service_extras AS $extraInf )
		{
			if( !( is_array( $extraInf )
			       && isset($extraInf['customer']) && is_numeric( $extraInf['customer'] ) && $extraInf['customer'] > 0
			       && isset($extraInf['extra']) && is_numeric( $extraInf['extra'] ) && $extraInf['extra'] > 0
			       && isset($extraInf['quantity']) && is_numeric($extraInf['quantity']) && $extraInf['quantity'] > 0)
			)
			{
				continue;
			}

			$extra_inf = ServiceExtra::where('service_id', $service)->where('id', $extraInf['extra'])->fetch();

			if( $extra_inf && $extra_inf['max_quantity'] >= $extraInf['quantity'] )
			{
				$extra_inf['quantity'] = $extraInf['quantity'];
				$extra_inf['customer'] = $extraInf['customer'];

				$extras_arr[] = $extra_inf;
			}
		}

		$dataForReturn = [];

		$calendarData = new CalendarService( $date );
		$calendarData->setStaffId( $staff )
		             ->setLocationId( $location )
		             ->setServiceId( $service )
		             ->setServiceExtras( $extras_arr )
		             ->setExcludeAppointmentId( $id )
		             ->setShowExistingTimeSlots( false )
		             ->setCalledFromBackEnd( $calledFromBackend );

		$calendarData = $calendarData->getCalendar();
		$data = $calendarData['dates'];

		if( isset( $data[ $date ] ) )
		{
			foreach ( $data[ $date ] AS $dataInf )
			{
				$startTime = $dataInf['start_time_format'];

				// search...
				if( !empty( $search ) && strpos( $startTime, $search ) === false )
				{
					continue;
				}

				$dataForReturn[] = [
					'id'					=>	$dataInf['start_time'],
					'text'					=>	$startTime,
					'max_capacity'			=>	$dataInf['max_capacity'],
					'number_of_customers'	=>	$dataInf['number_of_customers']
				];
			}
		}

		return $this->response(true, [ 'results' => $dataForReturn ]);
	}

	public function get_available_times_all()
	{
		$search		= Helper::_post('q', '', 'string');
		$service	= Helper::_post('service', 0, 'int');
		$location	= Helper::_post('location', 0, 'int');
		$staff		= Helper::_post('staff', 0, 'int');
		$dayOfWeek	= Helper::_post('day_number', 1, 'int');

		if( $dayOfWeek != -1 )
		{
			$dayOfWeek -= 1;
		}

		$calendarServ = new CalendarService();

		$calendarServ->setStaffId( $staff )
		             ->setServiceId( $service )
		             ->setLocationId( $location );

		return $this->response(true, [
			'results' => $calendarServ->getCalendarByDayOfWeek( $dayOfWeek, $search )
		]);
	}

	public function get_day_offs()
	{
		try
		{
			$appointmentObj = AppointmentRequestData::load();
		}
		catch ( \Exception $e )
		{
			return $this->response( false, $e->getMessage() );
		}

		if(
			! Date::isValid( $appointmentObj->recurringStartDate )
			|| ! Date::isValid( $appointmentObj->recurringEndDate )
			|| $appointmentObj->serviceId <= 0
		)
		{
			return $this->response(false, bkntc__('Please fill in all required fields correctly!'));
		}

		$calendarService = new CalendarService( $appointmentObj->recurringStartDate, $appointmentObj->recurringEndDate );
		$calendarService->setDefaultsFrom( $appointmentObj );

		return $this->response( true, $calendarService->getDayOffs() );
	}

	public function get_customers_list()
	{
		$appointment = Helper::_post('appointment', '0', 'integer');

		$checkAppointment = Appointment::get( $appointment );
		if ( !$checkAppointment )
		{
			return $this->response( false );
		}

		$customers = DB::DB()->get_results(
			DB::DB()->prepare( 'SELECT tb1.*, CONCAT(tb2.`first_name`, \' \', tb2.`last_name`) AS `customer_name`, tb2.`email`, tb2.`phone_number`, tb2.`profile_image` FROM `' . DB::table('appointment_customers') . '` tb1 LEFT JOIN `' . DB::table('customers') . '` tb2 ON tb2.`id`=tb1.`customer_id` WHERE `appointment_id`=%d', [ $appointment ] ),
			ARRAY_A
		);

		foreach ($customers as $i => $customer)
		{
			$customerBillingData = json_decode( AppointmentCustomer::getData($customer['id'], 'customer_billing_data') );

			$billingFirstName = ( empty($customerBillingData->customer_first_name) ? "" :  $customerBillingData->customer_first_name);
			$billingLastName = ( empty($customerBillingData->customer_last_name) ? "" :  $customerBillingData->customer_last_name);
			$billingPhone = ( empty($customerBillingData->customer_phone) ? "" :  $customerBillingData->customer_last_name);

			if ( $billingFirstName != "" && $billingLastName != "" )
			{
				$customers[$i]['billing_full_name'] = $billingFirstName . ' ' . $billingLastName;
				$customers[$i]['billing_phone'] = $billingPhone;
			}
		}

		return $this->modalView('customers_list', [ 'customers' => $customers ]);
	}

	public function get_service_extras()
	{
		$id			= Helper::_post('id', 0, 'integer');
		$service	= Helper::_post('service', 0, 'integer');
		$customers	= Helper::_post('customers', [], 'arr');

		$customersArr = [];
		$appointment_extras = [];
		foreach ( $customers AS $custId )
		{
			if( is_numeric( $custId ) && $custId > 0 )
			{
				$customersArr[] = Customer::get( $custId );

				$ac_id = AppointmentCustomer::where('appointment_id', $id)->where('customer_id', $custId)->select(['id'], true)->fetch();
				if (!$ac_id)
				{
					continue;
				}

				$appointmentExtras = AppointmentExtra::where('appointment_customer_id', $ac_id['id'])->fetchAll();
				foreach ( $appointmentExtras AS $appointmentExtra )
				{
					$appointment_extras[ $custId . '_' . (int)$appointmentExtra['extra_id'] ] = (int)$appointmentExtra['quantity'];
				}
			}
		}

		$extras = ServiceExtra::where('service_id', $service)->fetchAll();

		return $this->modalView( 'service_extras', [
			'customers'				=> $customersArr,
			'extras'				=> $extras,
			'appointment_extras'	=> $appointment_extras
		] );
	}


}
