<?php

namespace BookneticApp\Backend\Appointments\Helpers;

use BookneticApp\Models\Appointment;
use BookneticApp\Models\AppointmentCustomer;
use BookneticApp\Models\AppointmentCustomerPrice;
use BookneticApp\Models\AppointmentExtra;
use BookneticApp\Models\Data;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Math;
use BookneticApp\Backend\Appointments\Helpers\DexRequestObject;

class AppointmentService
{

	use RecurringAppointmentService;

	public static function createAppointment( AppointmentRequestData $appointmentData )
	{
        $uniqueRecurringId = uniqid();

		foreach ( $appointmentData->getAllAppointments() AS $appointment )
		{
			$appointmentDate		= $appointment->getDate();
			$appointmentTime		= $appointment->getTime();
			$isExistingAppointment	= false;

			if( $appointment->getAppointmentId() > 0 )
			{
				$appointmentId = (int)$appointment->getAppointmentId();
				$isExistingAppointment = true;
			}
			else
			{
                /*doit add_filter*/
				$appointmentInsertData = apply_filters( 'bkntc_appointment_insert_data', [
					'location_id'				=>	$appointmentData->locationId,
					'service_id'				=>	$appointmentData->serviceId,
					'staff_id'					=>	$appointmentData->staffId,
					'date'						=>	$appointmentDate,
					'note'						=>	$appointmentData->note,
					'start_time'				=>	$appointmentTime,
					'duration'					=>	(int) $appointmentData->serviceInf->duration,
					'extras_duration'			=>	(int) $appointmentData->getExtrasDuration(),
					'buffer_before'				=>	(int) $appointmentData->serviceInf->buffer_before,
					'buffer_after'				=>	(int) $appointmentData->serviceInf->buffer_after,
				], $appointmentData );

				Appointment::insert( $appointmentInsertData );

				$appointmentId = DB::lastInsertedId();

				$dexRequestObject = new DexRequestObject();
				$dexRequestObject->addAppointment(
					[
						'id'		=>	$appointmentId,
						'staff_id'	=>	$appointmentData->staffId,
						'product_id'		=>	$appointmentData->serviceId,
						'booked_date'			=>	$appointmentDate,
						"slot_time"		=> $appointmentTime,
						"seller_id"		=> $appointmentData->locationId,
						"slot_duration"	=> (int) $appointmentData->serviceInf->duration,
					]
				);
			}

			$appointmentData->createdAppointments[ $appointmentId ] = [];

			foreach ( $appointmentData->customers AS $customer )
			{
				$customerId = $customer['id'];

				$checkIfCustomerAlreadyBooked = AppointmentCustomer::where('appointment_id', $appointmentId)->where('customer_id', $customerId )->fetch();
				if( $checkIfCustomerAlreadyBooked )
				{
					self::deleteAppointmentCustomer( $checkIfCustomerAlreadyBooked->id, false );
				}

				$appointmentCustomerInsertData = apply_filters( 'bkntc_appointment_customer_insert_data', [
					'customer_id'			=>	$customerId,
					'appointment_id'		=>	$appointmentId,
					'number_of_customers'	=>	$customer['number'],
					'status'				=>	$customer['status'],
					'paid_amount'			=>	$appointmentData->getPayableToday(),
					'payment_method'		=>	$appointmentData->paymentMethod,
					'payment_status'		=>	'pending',
					'created_at'            =>  Date::dateTimeSQL(),
					'locale'                =>  get_locale(),
					'client_timezone'       =>  $appointmentData->clientTimezone,
                    'recurring_id'          =>  $uniqueRecurringId,
                    'recurring_payment_type'	=>	$appointmentData->serviceInf->recurring_payment_type
				], $appointmentData );

				AppointmentCustomer::insert( $appointmentCustomerInsertData );

				$appointmentCustomerId = AppointmentCustomer::lastId();

                foreach ( $appointmentData->getServiceExtras( $customerId ) AS $extra )
                {
                    AppointmentExtra::insert([
                        'appointment_customer_id' => $appointmentCustomerId,
                        'extra_id'				=>	$extra['id'],
                        'quantity'				=>	$extra['quantity'],
                        'price'					=>	$extra['price'],
                        'duration'				=>	(int)$extra['duration']
                    ]);
                }

				foreach ( $appointmentData->getPrices( $customerId, true ) AS $priceKey => $priceInf )
				{
					AppointmentCustomerPrice::insert([
						'appointment_customer_id'   =>  $appointmentCustomerId,
						'unique_key'                =>  $priceKey,
						'price'                     =>  Math::abs( $priceInf->getPrice() ),
						'negative_or_positive'      =>  $priceInf->getNegativeOrPositive()
					]);
				}

                if( $appointmentData->setBillingData )
                {
                    $billingArray = [
                        "customer_first_name" => "",
                        "customer_last_name" => "",
                        "customer_phone" => ""
                    ];

                    if( ! empty($appointmentData->customerData['first_name']) )
                    {
                        $billingArray['customer_first_name'] = $appointmentData->customerData['first_name'];
                    }
                    if( ! empty($appointmentData->customerData['last_name']) )
                    {
                        $billingArray['customer_last_name'] = $appointmentData->customerData['last_name'];
                    }
                    if( ! empty($appointmentData->customerData['phone']) )
                    {
                        $billingArray['customer_phone'] = $appointmentData->customerData['phone'];
                    }
                    $billingArray = json_encode( $billingArray );
                    AppointmentCustomer::setData( $appointmentCustomerId, 'customer_billing_data', $billingArray );

                }

				/**
				 * @doc bkntc_appointment_customer_added Triggers event when customer added to appointment
				 * @var int $customerInf->id An ID of appointment customer
				 */
				do_action( 'bkntc_appointment_customer_added', $appointmentCustomerId, $appointmentData );

				$appointmentData->createdAppointments[ $appointmentId ][] = $appointmentCustomerId;
			}

			if( $isExistingAppointment )
			{
				// re-fix extras durations of appointment
                ExtrasService::updateAppointmentExtrasDuration($appointmentId);
			}

			/**
			 * @doc bkntc_appointment_created Action triggered when an appointment created
			 * @var int $appointmentId Appointment ID
			 * @var AppointmentRequestData $appointmentData
			 */
			do_action( 'bkntc_appointment_created', $appointmentId, $appointmentData );
		}

	}

	public static function editAppointment( AppointmentRequestData $appointmentObj )
	{
		$appointmentChanged = ( (int) $appointmentObj->locationId   !==     (int) $appointmentObj->appointmentInf->location_id
		                        ||  (int) $appointmentObj->serviceId                   !==     (int) $appointmentObj->appointmentInf->service_id
		                        ||  (int) $appointmentObj->staffId                     !==     (int) $appointmentObj->appointmentInf->staff_id
		                        ||  Date::dateSQL( $appointmentObj->date )             !==     Date::dateSQL( $appointmentObj->appointmentInf->date )
		                        ||  Date::timeSQL( $appointmentObj->time )             !==     Date::timeSQL( $appointmentObj->appointmentInf->start_time )
		);

        /*doit add_filter()*/
		$appointmentUpdateData = apply_filters( 'bkntc_appointment_update_data', [
			'location_id'				=>	$appointmentObj->locationId,
			'service_id'				=>	$appointmentObj->serviceId,
			'staff_id'					=>	$appointmentObj->staffId,
			'date'						=>	$appointmentObj->date,
			'start_time'				=>	$appointmentObj->time,
			'note'				        =>	$appointmentObj->note,
			'duration'					=>	(int) $appointmentObj->serviceInf->duration,
			'extras_duration'			=>	(int) $appointmentObj->getExtrasDuration(),
			'buffer_before'				=>	(int) $appointmentObj->serviceInf->buffer_before,
			'buffer_after'				=>	(int) $appointmentObj->serviceInf->buffer_after,
		], $appointmentObj );

		Appointment::where( 'id', $appointmentObj->appointmentId )->update( $appointmentUpdateData );

		$doNotDeleteTheseAppointmentCustomers = [];

		foreach ( $appointmentObj->customers AS $customer )
		{
			$customerId             = $customer['id'];
			$appointmentCustomerId  = $customer['ac_id'];

			if( $appointmentCustomerId > 0 )
			{
				$appointmentCustomerInf = AppointmentCustomer::get( $appointmentCustomerId );

                /*doit add_filter()*/
				$appointmentCustomerInsertData = apply_filters( 'bkntc_appointment_customer_update_data', [
					'customer_id'			=>	$customerId,
					'number_of_customers'	=>	$customer['number'],
					'status'				=>	$customer['status']
				], $appointmentObj );

				AppointmentCustomer::where( 'id', $appointmentCustomerId )
				                   ->where( 'appointment_id', $appointmentObj->appointmentId )
				                   ->update( $appointmentCustomerInsertData );

				AppointmentCustomerPrice::where( 'appointment_customer_id', $appointmentCustomerId )->delete();

                do_action( 'bkntc_appointment_customer_edited', $appointmentCustomerId, $appointmentObj );
			}
			else
			{
				$appointmentCustomerInsertData = apply_filters( 'bkntc_appointment_customer_insert_data', [
					'customer_id'			=>	$customerId,
					'appointment_id'		=>	$appointmentObj->appointmentId,
					'number_of_customers'	=>	$customer['number'],
					'status'				=>	$customer['status'],
					'paid_amount'			=>	$appointmentObj->getPayableToday(),
					'payment_method'		=>	$appointmentObj->paymentMethod,
					'payment_status'		=>	'pending',
					'created_at'            =>  Date::dateTimeSQL(),
					'locale'                =>  get_locale(),
					'client_timezone'       =>  $appointmentObj->clientTimezone
				], $appointmentObj );

				AppointmentCustomer::insert( $appointmentCustomerInsertData );

				$appointmentCustomerId = DB::lastInsertedId();

				/**
				 * @doc bkntc_appointment_customer_added Triggers event when customer added to appointment
				 * @var int $appointmentCustomerId An ID of appointment customer
				 */
				do_action( 'bkntc_appointment_customer_added', $appointmentCustomerId, $appointmentObj );
			}

			$doNotDeleteTheseAppointmentCustomers[] = $appointmentCustomerId;

			AppointmentExtra::where( 'appointment_customer_id', $appointmentCustomerId )->delete();
			
            foreach ( $appointmentObj->getServiceExtras( $customerId ) AS $extra )
            {
                AppointmentExtra::insert([
                    'appointment_customer_id' => $appointmentCustomerId,
                    'extra_id'				=>	$extra['id'],
                    'quantity'				=>	$extra['quantity'],
                    'price'					=>	$extra['price'],
                    'duration'				=>	(int)$extra['duration']
                ]);
            }

			foreach ( $appointmentObj->getPrices( $customerId, true ) AS $priceKey => $priceInf )
			{
				AppointmentCustomerPrice::insert([
					'appointment_customer_id'   =>  $appointmentCustomerId,
					'unique_key'                =>  $priceKey,
					'price'                     =>  Math::abs( $priceInf->getPrice() ),
					'negative_or_positive'      =>  $priceInf->getNegativeOrPositive()
				]);
			}

            if( $appointmentCustomerId > 0 )
            {
                if( $appointmentCustomerInf->status != $customer['status'] )
                {
                    do_action( 'bkntc_appointment_customer_status_changed', $appointmentCustomerId, $customer['status'], $appointmentCustomerInf->status);
                }
            }


		}

        ExtrasService::updateAppointmentExtrasDuration($appointmentObj->appointmentId);

		$deletedAppointmentCustomers = AppointmentCustomer::where( 'appointment_id', $appointmentObj->appointmentId );

		if( ! empty( $doNotDeleteTheseAppointmentCustomers ) )
		{
			$deletedAppointmentCustomers = $deletedAppointmentCustomers->where( 'id', 'not in', $doNotDeleteTheseAppointmentCustomers );
		}

		$deletedAppointmentCustomers = $deletedAppointmentCustomers->fetchAll();

		foreach ( $deletedAppointmentCustomers AS $deletedAppointmentCustomerInf )
		{
			AppointmentService::deleteAppointmentCustomer( $deletedAppointmentCustomerInf->id );
		}

        if ( $appointmentChanged )
        {
            do_action('bkntc_appointment_rescheduled', $appointmentObj->appointmentId);
        }
	}

	public static function deleteAppointment( $appointmentsIDs )
	{
		$appointmentsIDs = is_array( $appointmentsIDs ) ? $appointmentsIDs : [ $appointmentsIDs ];

		foreach ( $appointmentsIDs as $appointmentId )
		{
		    do_action('bkntc_appointment_deleted', $appointmentId );

			$appointmentCustomers = AppointmentCustomer::where( 'appointment_id', $appointmentId )->fetchAll();

		    foreach ( $appointmentCustomers AS $appointmentCustomerInf )
		    {
		    	self::deleteAppointmentCustomer( $appointmentCustomerInf->id );
		    }

			Appointment::where('id', $appointmentId)->delete();
		    Data::where('row_id', $appointmentId )->where('table_name', 'appointments')->delete();
        }
	}

	public static function deleteAppointmentCustomer( $appointmentCustomerId, $deleteAppointmentIfEmpty = true )
	{
		$appointmentCustomerInf = AppointmentCustomer::get( $appointmentCustomerId );
        $appointmentId = $appointmentCustomerInf->appointment_id;

		if( ! $appointmentCustomerInf )
			return;

		$appointmentId  = $appointmentCustomerInf->appointment_id;
		$customerId     = $appointmentCustomerInf->customer_id;

		do_action('bkntc_appointment_customer_deleted', $appointmentCustomerId );

		AppointmentExtra::where( 'appointment_customer_id', $appointmentCustomerId )->delete();
		AppointmentCustomerPrice::where('appointment_customer_id', $appointmentCustomerId)->delete();
		AppointmentCustomer::where( 'id', $appointmentCustomerId )->delete();
        Data::where('row_id', $appointmentCustomerId )->where('table_name', 'appointment_customers')->delete();

        if ($deleteAppointmentIfEmpty && AppointmentCustomer::where('appointment_id', $appointmentId)->count() == 0)
        {
            self::deleteAppointment( $appointmentId );
        }
	}

	public static function reschedule( $appointmentCustomerId, $date, $time, $send_notifications = true )
	{
		$appointmentCustomerInfo	= AppointmentCustomer::get( $appointmentCustomerId );
		$customer_id				= $appointmentCustomerInfo->customer_id;
		$appointmentInfo			= Appointment::get( $appointmentCustomerInfo->appointment_id );

		if( !$appointmentInfo )
		{
			throw new \Exception('');
		}

		$service		= $appointmentInfo->service_id;
		$staff			= $appointmentInfo->staff_id;
		$getStaffInfo	= Staff::get( $staff );

        do_action( 'bkntc_appointment_before_edit', $appointmentInfo->id );

		$extras_arr = [];
		$appointmentExtras = AppointmentExtra::where('appointment_customer_id', $appointmentCustomerId)->fetchAll();

		foreach ( $appointmentExtras AS $extra )
		{
			$extra_inf = $extra->extra()->fetch();
			$extra_inf['quantity'] = $extra['quantity'];
			$extra_inf['customer'] = $customer_id;

			$extras_arr[] = $extra_inf;
		}

		$date = Date::dateSQL( $date );
		$time = Date::timeSQL( $time );

		$selectedTimeSlotInfo = new TimeSlotService( $date, $time );

		$selectedTimeSlotInfo->setStaffId( $staff )
			->setServiceId( $service )
			->setServiceExtras( $extras_arr )
            ->setLocationId( $appointmentInfo->location_id )
			->setExcludeAppointmentId( $appointmentCustomerInfo->appointment_id )
			->setCalledFromBackEnd( false )
			->setShowExistingTimeSlots( true );

		if( ! $selectedTimeSlotInfo->isBookable() )
		{
			throw new \Exception( bkntc__('Please select a valid time! ( %s %s is busy! )', [$date, $time]) );
		}

		$appointmentStatus = Helper::getDefaultAppointmentStatus();

		AppointmentCustomer::where( 'id', $appointmentCustomerId )->update([
			'status'	=>	$appointmentStatus
		]);

		$isGroupAppointment = (AppointmentCustomer::where('appointment_id', $appointmentCustomerInfo->appointment_id)->count() > 1);

		if( $isGroupAppointment )
		{
			if( $selectedTimeSlotInfo->getAppointmentId() > 0 )
			{
				$newAppointmentId = $selectedTimeSlotInfo->getAppointmentId();
				$needToRecalculateExtrasDuration = true;
			}
			else
			{
				Appointment::insert([
					'location_id'				=>	$appointmentInfo->location_id,
					'service_id'				=>	$appointmentInfo->service_id,
					'staff_id'					=>	$appointmentInfo->staff_id,
					'date'						=>	$date,
					'start_time'				=>	$time,
					'duration'					=>	$appointmentInfo->duration,
					'extras_duration'			=>	ExtrasService::calcExtrasDuration( $extras_arr ),
					'buffer_before'				=>	$appointmentInfo->buffer_before,
					'buffer_after'				=>	$appointmentInfo->buffer_after,
				]);

				$newAppointmentId = DB::lastInsertedId();
			}
		}
		else
		{
			if( $selectedTimeSlotInfo->getAppointmentId() > 0 )
			{
				$deleteAppointment = true;
				$newAppointmentId = $selectedTimeSlotInfo->getAppointmentId();
				$needToRecalculateExtrasDuration = true;
			}
			else
			{
				Appointment::where('id', $appointmentCustomerInfo->appointment_id)->update([
					'date' => $date,
					'start_time' => $time
				]);
			}
		}

		if( isset($newAppointmentId) )
		{
			AppointmentCustomer::where('customer_id', $customer_id)->where('appointment_id', $appointmentInfo->id)->update([
				'appointment_id'	=>	$newAppointmentId
			]);

            do_action( 'bkntc_appointment_before_edit', $newAppointmentId );
            do_action( 'bkntc_appointment_after_edit', $newAppointmentId );
		}


		/**
		 * @doc bkntc_appointment_customer_rescheduled Triggers event when appointment rescheduled
		 * @var int $appointmentCustomerId ID of rescheduled appointment
		 */
		do_action( 'bkntc_appointment_customer_rescheduled', $appointmentCustomerId, $appointmentInfo );

        do_action( 'bkntc_appointment_after_edit', $appointmentInfo->id );

		if( isset( $deleteAppointment ) )
		{
            self::deleteAppointment($appointmentCustomerInfo->appointment_id);
		}

		return [
			'appointment_status'    =>  $appointmentStatus
		];
	}

	public static function setStatus( $appointmentCustomerId, $status )
	{
        $appointmentCustomer = AppointmentCustomer::get($appointmentCustomerId);

        if (empty($appointmentCustomer) || $appointmentCustomer->status == $status)
            return true;

		AppointmentCustomer::where('id', $appointmentCustomerId)->update([
			'status'	=>	$status
		]);

        do_action( 'bkntc_appointment_customer_status_changed', $appointmentCustomerId, $status, $appointmentCustomer->status);

		return true;
	}

	/**
	 * Mushterilere odenish etmeleri uchun 10 deqiqe vaxt verilir.
	 * 10 deqiqe erzinde sechdiyi timeslot busy olacaq ki, odenish zamani diger mushteri bu timeslotu seche bilmesin.
	 * Eger 10 deqiqeden chox kechib ve odenish helede olunmayibsa o zaman avtomatik bu appointmente cancel statusu verir.
	 */
	public static function cancelUnpaidAppointments()
	{
        $failedStatus = Helper::getOption('failed_payment_status');
        if (empty($failedStatus))
            return;

		$timeLimit          = Helper::getOption( 'max_time_limit_for_payment', '10' );
		$compareTimestamp   = Date::dateTimeSQL('-' . $timeLimit . ' minutes');

		DB::DB()->query(
			DB::DB()->prepare("UPDATE `" . DB::table('appointment_customers') . "` SET `status`='$failedStatus' WHERE `payment_method` <> 'local' AND `payment_status` = 'pending' AND `created_at` < %s", [ $compareTimestamp ])
		);
	}


}