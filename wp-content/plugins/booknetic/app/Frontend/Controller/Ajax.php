<?php

namespace BookneticApp\Frontend\Controller;

use BookneticApp\Backend\Appointments\Helpers\AppointmentRequests;
use BookneticApp\Backend\Appointments\Helpers\CalendarService;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Backend\Appointments\Helpers\AppointmentService;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\Customer;
use BookneticApp\Models\Service;
use BookneticApp\Models\ServiceCategory;
use BookneticApp\Models\ServiceExtra;
use BookneticApp\Models\ServiceStaff;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Frontend;
use BookneticApp\Providers\Core\FrontendAjax;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Common\PaymentGatewayService;

class Ajax extends FrontendAjax
{
    private $categories;

	public function __construct()
	{

	}

	// is okay + tested
	public function get_data_location()
	{
		$appointmentRequests = AppointmentRequests::load();

        $appointmentObj = $appointmentRequests->currentRequest();

		$locations = $appointmentObj->getAvailableLocations()->fetchAll();

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
        $appointmentRequests = AppointmentRequests::load();

        $appointmentObj = $appointmentRequests->currentRequest();

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

        $this->handleCalendarServiceCartAppointments( $appointmentRequests );

		if( $appointmentObj->getTimeslotsCount() > 0 )
		{
			$onlyAvailableStaffList = [];

			foreach ( $staffList AS $staffInf )
			{
				$appointmentObj->staffId            = $staffInf->id;
				$appointmentObj->timeslots    = null;
				$staffIsOkay                        = true;

				foreach ($appointmentObj->getAllTimeslots() AS $timeSlot )
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
				$appointmentObj->timeslots = null;
			}

			$staffList = $onlyAvailableStaffList;
		}

        $staffList = array_map(function ($staff){
            $staff['name']          = htmlspecialchars($staff['name']);
            $staff['email']         = htmlspecialchars($staff['email']);
            $staff['phone_number']  = htmlspecialchars($staff['phone_number']);
            $staff['profession']    = htmlspecialchars($staff['profession']);
            return $staff;
        } , $staffList);

		return $this->view('booking_panel.staff', [
			'staff'		=>	$staffList
		]);
	}

	public function get_data_service()
	{
        $appointmentRequests = AppointmentRequests::load();

        $appointmentObj = $appointmentRequests->currentRequest();

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
            $services[$k]['name'] = htmlspecialchars($service['name']);
            $services[$k]['notes'] = htmlspecialchars($service['notes']);
		}

		return $this->view('booking_panel.services', [
			'services'		=>	$services
		]);
	}

	public function get_data_service_extras()
	{
        $appointmentRequests = AppointmentRequests::load();

        $appointmentObj = $appointmentRequests->currentRequest();

		$extras		    = ServiceExtra::where('service_id', $appointmentObj->serviceId)->where('is_active', 1)->where('max_quantity', '>', 0)->orderBy('id')->fetchAll();

		return $this->view('booking_panel.extras', [
			'extras'		=>	$extras,
			'service_name'	=>	htmlspecialchars($appointmentObj->serviceInf->name)
		]);
	}

	public function get_data_date_time()
	{
        $appointmentRequests = AppointmentRequests::load();

        $appointmentObj = $appointmentRequests->currentRequest();

		if( ! $appointmentObj->serviceInf )
		{
			return $this->response( false, bkntc__('Please fill in all required fields correctly!') );
		}

        $this->handleCalendarServiceCartAppointments($appointmentRequests);

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
        $appointmentRequests = AppointmentRequests::load();

        $appointmentObj = $appointmentRequests->currentRequest();

        $appointmentObj->handleAnyStaffOption();

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
        $appointmentRequests = AppointmentRequests::load();

        $appointmentObj = $appointmentRequests->currentRequest();

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
        $emailDisabled = false;


		if( is_user_logged_in() )
		{
            $emailDisabled = true;
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
        else
        {
            $appointmentCount = count($appointmentRequests->appointments);
            if ( $appointmentCount > 1 )
            {
                $lastAppointment = $appointmentRequests->appointments[$appointmentCount-2];
                $name       = isset($lastAppointment->customerData['first_name']) ? $lastAppointment->customerData['first_name'] : '';
                $surname    = isset($lastAppointment->customerData['last_name']) ? $lastAppointment->customerData['last_name'] : '';
                $email      = isset($lastAppointment->customerData['email']) ? $lastAppointment->customerData['email'] : '';
                $phone      = isset($lastAppointment->customerData['phone']) ? $lastAppointment->customerData['phone'] : '';
            }
        }

		$emailIsRequired = Helper::getOption('set_email_as_required', 'on');
		$phoneIsRequired = Helper::getOption('set_phone_as_required', 'off');

		$howManyPeopleCanBring = false;

        $appointmentObj->handleAnyStaffOption();

		foreach ($appointmentObj->getAllTimeslots() AS $appointments )
		{
			$timeslotInf = $appointments->getInfo();
			$availableSpaces = $timeslotInf['max_capacity'] - $timeslotInf['weight'] - 1;

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
            'email_disabled'            => $emailDisabled,

			'show_only_name'            => Helper::getOption('separate_first_and_last_name', 'on') == 'off',

			'how_many_people_can_bring' =>  $howManyPeopleCanBring
		]);
	}

    public function get_data_cart()
    {
        $currentIndex = Helper::_post('current' , 0 ,'int');
        $appointmentRequests = AppointmentRequests::load();

        return $this->view( 'booking_panel.cart', [
            'appointmentList'   => $appointmentRequests ,
            'current_index'     => $currentIndex
        ] );
    }

	// isokay
	public function get_data_confirm_details()
	{

        $appointmentRequests = AppointmentRequests::load();

        if( ! $appointmentRequests->validate() )
        {
            return $this->response(false,['errors'=>$appointmentRequests->getErrors()]);
        }

        $appointmentObj = $appointmentRequests->currentRequest();

		$hide_confirm_step      = Helper::getOption('hide_confirm_details_step', 'off') == 'on';
		$hide_price_section	    = Helper::getOption('hide_price_section', 'off');
		$hideMethodSelecting    = $appointmentRequests->getSubTotal(true) <= 0 || Helper::getOption('disable_payment_options', 'off') == 'on';

        $arr = [
            PaymentGatewayService::getInstalledGatewayNames()
        ];

        foreach ($appointmentRequests->appointments as $appointmentRequestData)
        {
            $serviceCustomPaymentMethods = $appointmentRequestData->serviceInf->getData( 'custom_payment_methods' );
            $serviceCustomPaymentMethods = json_decode( $serviceCustomPaymentMethods ,true );
            $arr[] = empty( $serviceCustomPaymentMethods ) ? PaymentGatewayService::getEnabledGatewayNames() : $serviceCustomPaymentMethods;
        }

        if (!isset($showDepositLabel)) $showDepositLabel = false;
        if (!isset($depositPrice)) $depositPrice = 0;
        foreach ($appointmentRequests->appointments as $appointment) {
            if ($appointment->hasDeposit()) {
                $showDepositLabel = true;
                $depositPrice += $appointment->getDepositPrice(true);
            } else {
                $depositPrice += $appointment->getSubTotal();
            }
        }

        $allowedPaymentMethods = call_user_func_array('array_intersect' , $arr);

		return $this->view('booking_panel.confirm_details', [
			'appointmentData'           =>  $appointmentObj,
            'custom_payment_methods'    =>  $allowedPaymentMethods,
            'appointment_requests'      =>  $appointmentRequests,
			'hide_confirm_step'		    =>	$hide_confirm_step,
            'hide_payments'			    =>	$hideMethodSelecting,
            'hide_price_section'        =>  $hide_price_section == 'on',
            'has_deposit'               =>  $showDepositLabel,
            'deposit_price'             =>  $depositPrice,
		], [
            'has_deposit'               =>  $appointmentObj->hasDeposit()
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
		}
		catch ( \Exception $e )
		{
			return $this->response( false, $e->getMessage() );
		}

        $appointmentRequests = AppointmentRequests::load();

        if( ! $appointmentRequests->validate() )
        {
            return $this->response(false,$appointmentRequests->getFirstError());
        }

        foreach ($appointmentRequests->appointments as $appointment)
        {
            if( $appointment->isRecurring() && empty( $appointment->recurringAppointmentsList ) )
            {
                return $this->response(false, bkntc__('Please fill in all required fields correctly!'));
            }
        }


		do_action( 'bkntc_booking_step_confirmation_validation', $appointmentRequests );

		$paymentGateway = PaymentGatewayService::find( $appointmentRequests->paymentMethod );

		if ( ( ! $paymentGateway || ! $paymentGateway->isEnabled( $appointmentRequests ) ) && $appointmentRequests->paymentMethod !== 'local' )
		{
			return $this->response( false, bkntc__( 'Payment method is not supported' ) );
		}

        if ( $appointmentRequests->paymentMethod === 'local' && ! $paymentGateway->isEnabled( $appointmentRequests ) )
        {
            return $this->response( false, bkntc__( 'Payment method is not supported' ) );
        }

        foreach ($appointmentRequests->appointments as $appointment)
        {
            $appointment->registerNewCustomer();
        }

		AppointmentService::createAppointment( $appointmentRequests );

		$payment = $paymentGateway->doPayment( $appointmentRequests );

		$responseStatus = is_bool( $payment->status ) ? $payment->status : false;
		$responseData   = is_array( $payment->data ) ? $payment->data : [];

		$responseData['id']                     = $appointmentRequests->appointments[0]->getFirstAppointmentId();
		$responseData['google_calendar_url']    = AjaxHelper::addToGoogleCalendarURL( $appointmentRequests->appointments[0] );

		$responseData['payment_id']           = Appointment::get( $responseData['id'] )->payment_id;

		return $this->response( $responseStatus, $responseData );
	}

	public function delete_unpaid_appointment()
	{
		$paymentId                    = Helper::_post('payment_id', '', 'string');
        $appointmentList = Appointment::where('payment_id' , $paymentId )->where('payment_status' ,'<>','paid')->fetchAll();

		if( empty($appointmentList) )
		{
			return $this->response( true );
		}

        foreach ($appointmentList as $appointment)
        {
            AppointmentService::deleteAppointment( $appointment->id );
        }

		return $this->response( true );
	}

    // doit: bu evvel backendin ajaxin simulyasiya edirdi, baxaq umumi helpere cixaraq sonda
	public function get_available_times_all()
	{
        $appointmentRequests = AppointmentRequests::load();

        $appointmentObj = $appointmentRequests->currentRequest();

        $search		= Helper::_post('q', '', 'string');
        $service	= $appointmentObj->serviceId;
        $location	= $appointmentObj->locationId;
        $staff		= $appointmentObj->staffId;
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

	public function get_available_times()
	{
		$ajax = new \BookneticApp\Backend\Appointments\Ajax();
        return $ajax->get_available_times( false );
	}

    // doit: bu evvel backendin ajaxin simulyasiya edirdi, baxaq umumi helpere cixaraq sonda
	public function get_day_offs()
	{
        $appointmentRequests = AppointmentRequests::load();

        $appointmentObj = $appointmentRequests->currentRequest();

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

    private function handleCalendarServiceCartAppointments( AppointmentRequests  $appointmentRequests )
    {
        add_filter('bkntc_staff_appointments', function ($staffAppointments, CalendarService $calendarService) use ($appointmentRequests)
        {
            for ($i = 0; $i < count($appointmentRequests->appointments) - 1; $i++)
            {
                $appointmentRequest = $appointmentRequests->appointments[$i];

                // note: anystaff olduqda bütün stafflara əlavə olunur, digər halda staff_id eynidirsə
                if ($appointmentRequest->staffId != $calendarService->getStaffId() && $appointmentRequest->staffId > 0)
                    continue;

                foreach ($appointmentRequest->getAllTimeslots() as $timeslot)
                {
                    // add or merge $timeslot into $staffAppointments
                    $merged = false;
                    foreach ($staffAppointments as $staffAppointment)
                    {
                        if (
                            $staffAppointment->starts_at == $timeslot->getTimestamp() &&
                            $staffAppointment->service_id == $timeslot->getServiceId() &&
                            $staffAppointment->location_id == $timeslot->getLocationId()
                        )
                        {
                            $staffAppointment->total_weight += $appointmentRequest->weight;
                            $merged = true;
                            break;
                        }
                    }

                    if ($merged) continue;

                    $a = new Appointment();
                    $a->staff_id = $calendarService->getStaffId();
                    $a->location_id = $timeslot->getLocationId();
                    $a->service_id = $timeslot->getServiceId();
                    $a->starts_at = $timeslot->getTimestamp();
                    $a->ends_at = $timeslot->getTimestamp() + ((int) $appointmentRequest->serviceInf->duration + (int) $appointmentRequest->getExtrasDuration()) * 60;
                    $a->busy_from = $timeslot->getTimestamp() - ((int) $appointmentRequest->serviceInf->buffer_before) * 60;
                    $a->busy_to = $timeslot->getTimestamp() + ((int) $appointmentRequest->serviceInf->duration + (int) $appointmentRequest->getExtrasDuration() + (int) $appointmentRequest->serviceInf->buffer_after) * 60;
                    $a->total_weight = $appointmentRequest->weight;
                    $staffAppointments[] = $a;
                }
            }

            return $staffAppointments;
        }, 10, 2);
    }
}
