<?php

namespace BookneticApp\Frontend\Controller;

use BookneticApp\Backend\Appearance\Helpers\Theme;
use BookneticApp\Backend\Appointments\Helpers\AppointmentCustomerSmartObject;
use BookneticApp\Backend\Appointments\Helpers\AppointmentRequestData;
use BookneticApp\Backend\Appointments\Helpers\CalendarService;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Helpers\Math;
use BookneticApp\Backend\Appointments\Helpers\AppointmentService;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\AppointmentCustomer;
use BookneticApp\Models\Customer;
use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\ServiceCategory;
use BookneticApp\Models\ServiceExtra;
use BookneticApp\Models\ServiceStaff;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Helpers\Curl;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Frontend;
use BookneticApp\Providers\Core\FrontendAjax;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Common\PaymentGatewayService;
use Cassandra\Custom;

class Ajax extends FrontendAjax
{
    private $categories;

	public function __construct()
	{

	}

	// is okay + tested
	public function get_data_location( $return_as_array = false )
	{
		try
		{
			$appointmentObj = AppointmentRequestData::load();
		}
		catch ( \Exception $e )
		{
			return $this->response( false, $e->getMessage() );
		}

		$locations      = Location::where('is_active', 1)->orderBy('id');

		if( $appointmentObj->staffId > 0 )
		{
			$locationsFilter = empty( $appointmentObj->staffInf->locations ) ? [0] : explode( ',', $appointmentObj->staffInf->locations );
			$locations->where('id', $locationsFilter);
		}
		else if( $appointmentObj->serviceId > 0 )
		{
			$locationsFilter    = [];
			$staffList          = ServiceStaff::where('service_id', $appointmentObj->serviceId)->leftJoin( 'staff', ['locations'] )->fetchAll();

			foreach ( $staffList AS $staffInf )
			{
				$locationsFilter = array_merge( $locationsFilter, explode(',', $staffInf->staff_locations) );
			}

			$locationsFilter = array_unique( $locationsFilter );
			$locationsFilter = empty( $locationsFilter ) ? [0] : $locationsFilter;

			$locations->where('id', $locationsFilter);
		}

		$locations	= $locations->fetchAll();

		if( $return_as_array )
		{
			return $locations;
		}

		return $this->view('booking_panel.locations', [
			'locations'		=>	$locations
		]);
	}

    public function get_booking_panel()
    {
        add_shortcode('booknetic', [\BookneticApp\Providers\Core\Frontend::class, 'addBookneticShortCode']);

        $atts = [
            'location'   => Helper::_post('location' , '' , 'int'),
            'staff'      => Helper::_post('staff' , '' , 'int'),
            'service'    => Helper::_post('service' , '' , 'int'),
            'category'   => Helper::_post('category' , '' , 'int'),
            'theme'      => Helper::_post('theme' , '' , 'int'),
        ];

        $shortcode = "booknetic";

        foreach ($atts as $key=>$value ) {
            if( ! empty( $value ) )
            {
                $shortcode .= " $key=$value";
            }
        }

        $bookneticShortcode =  do_shortcode( "[$shortcode]" );

        return $bookneticShortcode;
	}

	// isokay + tested
	public function get_data_staff()
	{
		try
		{
			$appointmentObj = AppointmentRequestData::load();
		}
		catch ( \Exception $e )
		{
			return $this->response( false, $e->getMessage() );
		}

		$staffList      = Staff::where('is_active', 1)->orderBy('id');

        if( $appointmentObj->serviceCategoryId > 0 )
        {
            $categoriesFiltr = Helper::getAllSubCategories( $appointmentObj->serviceCategoryId );

            $services = Service::select(['id'])->where('category_id' , 'in' ,array_values($categoriesFiltr))->fetchAll();

            $servicesIdList = array_map(function ($service){
                return $service->id;
            },$services);

            $servicesStaffList = ServiceStaff::select(['staff_id'])->where('service_id' , 'in' ,$servicesIdList)->fetchAll();

            $filterStaffIdList = array_map(function ($serviceStaff){
                return $serviceStaff->staff_id;
            },$servicesStaffList);

            $staffList->where('id' ,'in' , $filterStaffIdList);
        }


		if( $appointmentObj->locationId > 0 )
		{
			$staffList->whereFindInSet( 'locations', $appointmentObj->locationId );
		}

		if( $appointmentObj->serviceId > 0 )
		{
			$subQuery = ServiceStaff::where('service_id', $appointmentObj->serviceId)
				->where( 'staff_id', DB::field( 'id', 'staff' ) )
				->select('count(0)');

			$staffList->where( $subQuery, '>', 0 );
		}

		$staffList = $staffList->fetchAll();

		if( $appointmentObj->getAppointmentsCount() > 0 )
		{
			$onlyAvailableStaffList = [];

			foreach ( $staffList AS $staffInf )
			{
				$appointmentObj->staffId            = $staffInf->id;
				$appointmentObj->appointmentList    = null;
				$staffIsOkay                        = true;

				foreach ( $appointmentObj->getAllAppointments() AS $timeSlot )
				{
					if( ! $timeSlot->isBookable() )
					{
						$staffIsOkay = false;
						break;
					}
				}

				if( $staffIsOkay )
					$onlyAvailableStaffList[] = $staffInf;

				$appointmentObj->staffId = null;
				$appointmentObj->appointmentList = null;
			}

			$staffList = $onlyAvailableStaffList;
		}

		return $this->view('booking_panel.staff', [
			'staff'		=>	$staffList
		]);
	}

	public function get_data_service()
	{
		try
		{
			$appointmentObj = AppointmentRequestData::load();
		}
		catch ( \Exception $e )
		{
			return $this->response( false, $e->getMessage() );
		}

		$queryAttrs = [ $appointmentObj->staffId ];
		if( $appointmentObj->serviceCategoryId > 0 )
        {
            $categoriesFiltr = Helper::getAllSubCategories( $appointmentObj->serviceCategoryId );
        }

		$locationFilter = '';
		if( $appointmentObj->locationId > 0 && !( $appointmentObj->staffId > 0 ) )
		{
			$locationFilter = " AND tb1.`id` IN (SELECT `service_id` FROM `".DB::table('service_staff')."` WHERE `staff_id` IN (SELECT `id` FROM `".DB::table('staff')."` WHERE FIND_IN_SET('{$appointmentObj->locationId}', IFNULL(`locations`, ''))))";
		}

		$services = DB::DB()->get_results(
			DB::DB()->prepare( "
				SELECT
					tb1.*,
					IFNULL(tb2.price, tb1.price) AS real_price,
					(SELECT count(0) FROM `" . DB::table('service_extras') . "` WHERE service_id=tb1.id AND `is_active`=1) AS extras_count
				FROM `" . DB::table('services') . "` tb1 
				".( $appointmentObj->staffId > 0 ? 'INNER' : 'LEFT' )." JOIN `" . DB::table('service_staff') . "` tb2 ON tb2.service_id=tb1.id AND tb2.staff_id=%d
				WHERE tb1.`is_active`=1 AND (SELECT count(0) FROM `" . DB::table('service_staff') . "` WHERE service_id=tb1.id)>0 ".DB::tenantFilter()." ".$locationFilter."
				" . ( $appointmentObj->serviceCategoryId > 0 && !empty( $categoriesFiltr ) ? "AND tb1.category_id IN (". implode(',', $categoriesFiltr) . ")" : "" ) . "
				ORDER BY tb1.category_id, tb1.id", $queryAttrs ),
			ARRAY_A
		);

		foreach ( $services AS $k => $service )
		{
			$services[$k]['category_name'] = $this->__getServiceCategoryName( $service['category_id'] );
		}

		return $this->view('booking_panel.services', [
			'services'		=>	$services
		]);
	}

	public function get_data_service_extras()
	{
		try
		{
			$appointmentObj = AppointmentRequestData::load();
		}
		catch ( \Exception $e )
		{
			return $this->response( false, $e->getMessage() );
		}

		$extras		    = ServiceExtra::where('service_id', $appointmentObj->serviceId)->where('is_active', 1)->where('max_quantity', '>', 0)->orderBy('id')->fetchAll();

		return $this->view('booking_panel.extras', [
			'extras'		=>	$extras,
			'service_name'	=>	htmlspecialchars($appointmentObj->serviceInf->name)
		]);
	}

	public function get_data_date_time()
	{
		try
		{
			$appointmentObj = AppointmentRequestData::load();
		}
		catch ( \Exception $e )
		{
			return $this->response( false, $e->getMessage() );
		}

		if( ! $appointmentObj->serviceInf )
		{
			return $this->response( false, bkntc__('Please fill in all required fields correctly!') );
		}

		$month			= Helper::_post('month', (int)Date::format('m'), 'int', [ 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12 ]);
		$year			= Helper::_post('year', Date::format('Y'), 'int');

		$date_start		= Date::dateSQL( $year . '-' . $month . '-01' );
		$date_end		= Date::format('Y-m-t', $year . '-' . $month . '-01' );

		// check for "Limited booking days" settings...
		$available_days_for_booking = Helper::getOption('available_days_for_booking', '365');
		if( $available_days_for_booking > 0 )
		{
			$limitEndDate = Date::epoch('+' . $available_days_for_booking . ' days');

			if( Date::epoch( $date_end ) > $limitEndDate )
			{
				$date_end = Date::dateSQL( $limitEndDate );
			}
		}

		if( $appointmentObj->isRecurring() )
		{
			$recurringType  = $appointmentObj->serviceInf->repeat_type;
			$service_type   = 'recurring_' . ( in_array( $appointmentObj->serviceInf->repeat_type, ['daily', 'weekly', 'monthly'] ) ? $appointmentObj->serviceInf->repeat_type : 'daily' );
			$calendarData   = null;
		}
		else
		{
			$service_type = 'non_recurring';

			$calendarData = new CalendarService( $date_start, $date_end );
			$calendarData = $calendarData->setDefaultsFrom( $appointmentObj )->getCalendar();

			$calendarData['hide_available_slots'] = Helper::getOption('hide_available_slots', 'off');
		}

		return $this->view('booking_panel.date_time_' . $service_type, [
			'date_based'	        =>	$appointmentObj->serviceInf->duration >= 1440,
			'service_max_capacity'	=>  (int) $appointmentObj->serviceInf->max_capacity > 0 ? (int) $appointmentObj->serviceInf->max_capacity : 1
		], [
			'data'			    =>	$calendarData,
			'service_type'	    =>	$service_type,
			'time_show_format'  =>  Helper::getOption('time_view_type_in_front', '1'),
			'service_info'	    =>	[
				'date_based'		=>	$appointmentObj->isDateBasedService(),
				'repeat_type'		=>	htmlspecialchars( $appointmentObj->serviceInf->repeat_type ),
				'repeat_frequency'	=>	htmlspecialchars( $appointmentObj->serviceInf->repeat_frequency ),
				'full_period_type'	=>	htmlspecialchars( $appointmentObj->serviceInf->full_period_type ),
				'full_period_value'	=>	(int)$appointmentObj->serviceInf->full_period_value
			]
		]);
	}

	// isokay
	public function get_data_recurring_info()
	{
		try
		{
			$appointmentObj = AppointmentRequestData::load();
		}
		catch ( \Exception $e )
		{
			return $this->response( false, $e->getMessage() );
		}

		if( ! $appointmentObj->isRecurring() )
		{
			return $this->response(false, bkntc__('Please select service'));
		}

		try {
			$appointmentObj->validateRecurringData();
		}
		catch ( \Exception $e )
		{
			return $this->response( false, $e->getMessage() );
		}


		$recurringAppointments = AppointmentService::getRecurringDates( $appointmentObj );

		if( ! count( $recurringAppointments ) )
		{
			return $this->response(false , bkntc__('Please choose dates' ));
		}

		return $this->view('booking_panel.recurring_information', [
			'appointmentObj'    => $appointmentObj,
			'appointments'      => $recurringAppointments
		]);
	}

	public function get_data_information()
	{
		try
		{
			$appointmentObj = AppointmentRequestData::load();
		}
		catch ( \Exception $e )
		{
			return $this->response( false, $e->getMessage() );
		}

		if( $appointmentObj->serviceId <= 0 )
		{
			$checkAllFormsIsTheSame = DB::DB()->get_row('SELECT * FROM `'.DB::table('forms').'` WHERE (SELECT count(0) FROM `'.DB::table('services').'` WHERE FIND_IN_SET(`id`, `service_ids`) AND `is_active`=1)<(SELECT count(0) FROM `'.DB::table('services').'` WHERE `is_active`=1)' . DB::tenantFilter(), ARRAY_A);
			if( !$checkAllFormsIsTheSame )
			{
				$firstRandomService = Service::where('is_active', '1')->limit(1)->fetch();
				$appointmentObj->serviceId = $firstRandomService->id;
			}
		}

		// Logged in user data
		$name		= '';
		$surname	= '';
		$email		= '';
		$phone 		= '';

		if( is_user_logged_in() )
		{
            $wpUserId = get_current_user_id();
            $checkCustomerExists = Customer::where('user_id', $wpUserId)->fetch();

            if ($checkCustomerExists)
            {
                $name		= $checkCustomerExists->first_name;
                $surname	= $checkCustomerExists->last_name;
                $email		= $checkCustomerExists->email;
                $phone		= $checkCustomerExists->phone_number;
            }
            else
            {
                $userData = wp_get_current_user();

                $name		= $userData->first_name;
                $surname	= $userData->last_name;
                $email		= $userData->user_email;
                $phone		= get_user_meta( $wpUserId, 'billing_phone', true );
            }

        }

		$emailIsRequired = Helper::getOption('set_email_as_required', 'on');
		$phoneIsRequired = Helper::getOption('set_phone_as_required', 'off');

		$howManyPeopleCanBring = false;
		foreach ( $appointmentObj->getAllAppointments() AS $appointments )
		{
			$timeslotInf = $appointments->getInfo();
			$availableSpaces = $timeslotInf['max_capacity'] - $timeslotInf['number_of_customers'] - 1;

			if( $howManyPeopleCanBring === false || $availableSpaces < $howManyPeopleCanBring )
			{
				$howManyPeopleCanBring = $availableSpaces;
			}
		}

		return $this->view('booking_panel.information', [
			'service'                   => $appointmentObj->serviceId,

			'name'				        => $name,
			'surname'			        => $surname,
			'email'				        => $email,
			'phone'				        => $phone,

			'email_is_required'	        => $emailIsRequired,
			'phone_is_required'	        => $phoneIsRequired,

			'show_only_name'            => Helper::getOption('separate_first_and_last_name', 'on') == 'off',

			'how_many_people_can_bring' =>  $howManyPeopleCanBring
		]);
	}

	// isokay
	public function get_data_confirm_details()
	{
		try
		{
			$appointmentObj = AppointmentRequestData::load( false, true );
		}
		catch ( \Exception $e )
		{
			return $this->response( false, $e->getMessage() );
		}

		$hide_confirm_step      = Helper::getOption('hide_confirm_details_step', 'off') == 'on';
		$hide_price_section	    = Helper::getOption('hide_price_section', 'off');
		$hideMothodSelecting    = $appointmentObj->getSubTotal( null, true ) <= 0 ? true : Helper::getOption('disable_payment_options', 'off') == 'on';

		return $this->view('booking_panel.confirm_details', [
			'appointmentData'       =>  $appointmentObj,

			'hide_confirm_step'		=>	$hide_confirm_step,
			'hide_payments'			=>	$hideMothodSelecting,
			'hide_price_section'    =>  $hide_price_section == 'on',
		], [
            'has_deposit'           =>  $appointmentObj->hasDeposit()
        ] );
	}

	// isokay
	public function confirm()
	{
		if( ! Capabilities::tenantCan( 'receive_appointments' ) )
			return $this->response( false );

		try
		{
			AjaxHelper::validateGoogleReCaptcha();

			$appointmentObj = AppointmentRequestData::load( false, true );
		}
		catch ( \Exception $e )
		{
			return $this->response( false, $e->getMessage() );
		}

		$appointmentObj->registerNewCustomer();

		if( $appointmentObj->isRecurring() && empty( $appointmentObj->recurringAppointmentsList ) )
		{
			return $this->response(false, bkntc__('Please fill in all required fields correctly!'));
		}

		do_action( 'bkntc_booking_step_confirmation_validation', $appointmentObj );

		$paymentGateway = PaymentGatewayService::find( $appointmentObj->paymentMethod );

		if( ( ! $paymentGateway || ! $paymentGateway->isEnabled() ) && $appointmentObj->paymentMethod !== 'local' )
		{
			return $this->response(false, bkntc__('Please fill in all required fields correctly!'));
		}

        if( $appointmentObj->paymentMethod === 'local' && ! in_array( 'local', PaymentGatewayService::getEnabledGatewayNames() ) )
        {
            return $this->response(false, bkntc__('Method is not active'));
        }

		try
		{
			do_action( 'bkntc_before_appointment_created' );
		}
		catch ( \Exception $e )
		{
			return  $this->response( false, $e->getMessage() );
		}

		AppointmentService::createAppointment( $appointmentObj );

        /*doit add_action()*/
		do_action( 'bkntc_after_appointment_created' , $appointmentObj );

		$payment = $paymentGateway->doPayment( $appointmentObj );

		$responseStatus = is_bool( $payment->status ) ? $payment->status : false;
		$responseData   = is_array( $payment->data ) ? $payment->data : [];

		$responseData['id']                     = $appointmentObj->getFirstAppointmentCustomerId();
		$responseData['google_calendar_url']    = AjaxHelper::addToGoogleCalendarURL( $appointmentObj );

        $appointmentTokenArr                    = AppointmentCustomer::get( $responseData['id'] )->toArray();
        unset($appointmentTokenArr['payment_status']);
        unset($appointmentTokenArr['status']);
		$responseData['unique_token']           = md5( json_encode( $appointmentTokenArr ) );

		return $this->response( $responseStatus, $responseData );
	}

	public function delete_unpaid_appointment()
	{
		$appointmentCustomerId          = Helper::_post('id', 0, 'int');
		$uniqueToken                    = Helper::_post('unique_token', '', 'string');
		$appointmentCustomerSmartObject = AppointmentCustomerSmartObject::load( $appointmentCustomerId );

		if( ! $appointmentCustomerSmartObject->getInfo() )
		{
			return $this->response( true );
		}

		$customerId                     = $appointmentCustomerSmartObject->getInfo()->customer_id;

        $appointmentTokenArr            = AppointmentCustomer::get( $appointmentCustomerId )->toArray();
        unset($appointmentTokenArr['payment_status']);
        unset($appointmentTokenArr['status']);

		if( empty( $uniqueToken ) || md5( json_encode( $appointmentTokenArr ) ) != $uniqueToken )
		{
			return $this->response( false );
		}

		foreach ( $appointmentCustomerSmartObject->getAllRecurringAppointmentCustomersId() AS $ac_id )
		{
            $a_id = AppointmentCustomerSmartObject::load( $ac_id )->getAppointmentInfo()->id;

            AppointmentService::deleteAppointmentCustomer( $ac_id );

			$checkSlotIsEmpty = AppointmentCustomer::where( 'appointment_id', $a_id )->count();

			if ( $checkSlotIsEmpty == 0 )
			{
				AppointmentService::deleteAppointment( $a_id );
			}
		}

		return $this->response( true );
	}

	public function get_available_times_all()
	{
		$ajax = new \BookneticApp\Backend\Appointments\Ajax();
		return $ajax->get_available_times_all();
	}

	public function get_available_times()
	{
		$ajax = new \BookneticApp\Backend\Appointments\Ajax();
        return $ajax->get_available_times( false );
	}

	public function get_day_offs()
	{
		$ajax = new \BookneticApp\Backend\Appointments\Ajax();
		return $ajax->get_day_offs();
	}

	private function __getServiceCategoryName( $categId )
	{
		if( is_null( $this->categories ) )
		{
			$this->categories = ServiceCategory::fetchAll();
		}

		$categNames = [];

		$attempts = 0;
		while( $categId > 0 && $attempts < 10 )
		{
			$attempts++;
			foreach ( $this->categories AS $category )
			{
				if( $category['id'] == $categId )
				{
					$categNames[] = $category['name'];
					$categId = $category['parent_id'];
					break;
				}
			}
		}

		return implode(' > ', array_reverse($categNames));
	}


}
