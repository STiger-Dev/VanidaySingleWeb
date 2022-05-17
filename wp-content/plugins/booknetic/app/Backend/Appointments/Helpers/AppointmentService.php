<?php

namespace BookneticApp\Backend\Appointments\Helpers;

use BookneticApp\Models\Appointment;
use BookneticApp\Models\AppointmentPrice;
use BookneticApp\Models\AppointmentExtra;
use BookneticApp\Models\Data;
use BookneticApp\Models\Service;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Math;
use BookneticApp\Backend\Appointments\Helpers\DexRequestObject;

class AppointmentService
{

	use RecurringAppointmentService;

	public static function createAppointment( AppointmentRequests $appointmentRequests )
	{
        $paymentId = md5(uniqid());
		$dexRequestObject = new DexRequestObject();
        $appointmentRequests->paymentId = $paymentId;
        foreach ($appointmentRequests->appointments as $appointmentData)
        {

            $recurringId = $appointmentData->isRecurring() ? md5(uniqid()) : null;

            $payableSlotsCount = $appointmentData->getPayableAppointmentsCount();

		    foreach ($appointmentData->getAllTimeslots() AS $appointment )
            {
//                do_action('bkntc_appointment_before_mutation', null);

                $paidAmount = $payableSlotsCount > 0 ? $appointmentData->getPayableToday() : 0;
                $paymentMethod = $payableSlotsCount > 0 ? $appointmentData->paymentMethod : 'local';
                $paymentStatus = $paymentMethod == 'local' ? 'not_paid' : 'pending';

                $appointmentInsertData = apply_filters( 'bkntc_appointment_insert_data', [
                    'location_id'				=>	$appointmentData->locationId,
                    'service_id'				=>	$appointmentData->serviceId,
                    'staff_id'					=>	$appointmentData->staffId,
                    'customer_id'               =>  $appointmentData->customerId,
                    'status'                    =>  $appointmentData->status,
                    'starts_at'                 =>  $appointment->getTimestamp(),
                    'ends_at'                   =>  $appointment->getTimestamp() + ((int) $appointmentData->serviceInf->duration + (int) $appointmentData->getExtrasDuration()) * 60,
                    'busy_from'                 =>  $appointment->getTimestamp() - ((int) $appointmentData->serviceInf->buffer_before) * 60,
                    'busy_to'                   =>  $appointment->getTimestamp() + ((int) $appointmentData->serviceInf->duration + (int) $appointmentData->getExtrasDuration() + (int) $appointmentData->serviceInf->buffer_after) * 60,
                    'weight'                    =>  $appointmentData->weight,
                    'paid_amount'			    =>	$paidAmount,
                    'payment_method'		    =>	$paymentMethod,
                    'payment_status'		    =>	$paymentStatus,
                    'payment_id'                =>  $paymentId,
                    'recurring_id'              =>  $recurringId,
                    'note'                      =>  $appointmentData->note,
                    'locale'                    =>  get_locale(),
                    'client_timezone'           =>  $appointmentData->clientTimezone,
                    'created_at'                =>  (new \DateTime())->getTimestamp()
                ], $appointmentData );

                $payableSlotsCount--;

                Appointment::insert( $appointmentInsertData );

                $appointmentId = DB::lastInsertedId();

                foreach ( $appointmentData->getServiceExtras() AS $extra )
                {
                    AppointmentExtra::insert([
                        'appointment_id'        =>  $appointmentId,
                        'extra_id'				=>	$extra['id'],
                        'quantity'				=>	$extra['quantity'],
                        'price'					=>	$extra['price'],
                        'duration'				=>	(int)$extra['duration']
                    ]);
                }

                foreach ( $appointmentData->getPrices( true) AS $priceKey => $priceInf )
                {
                    AppointmentPrice::insert([
                        'appointment_id'            =>  $appointmentId,
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
                    Appointment::setData( $appointmentId, 'customer_billing_data', $billingArray );

                }

                $appointmentData->createdAppointments[] = $appointmentId;

				$dexRequestObject->addSales(
					[
						'id'		=>	strval($appointmentId),
						'location_id'	=>	strval($appointmentData->locationId),
						'customer_id'	=>	strval($appointmentData->customerId),
						"status"	=>	 $appointmentData->status,
						"sales_item"	=>	[
							array(
								"product_id"	=>	strval($appointmentData->serviceId),
								"qty"	=>	1
							)
						]
					]
				);
                /**
                 * @doc bkntc_appointment_created Action triggered when an appointment created
                 * @var int $appointmentId Appointment ID
                 * @var AppointmentRequestData $appointmentData
                 */
                do_action( 'bkntc_appointment_created', $appointmentId, $appointmentData );
//                do_action( 'bkntc_appointment_after_mutation', $appointmentId );
            }
        }

	}

	public static function editAppointment( AppointmentRequestData $appointmentObj )
	{
        do_action('bkntc_appointment_before_mutation', $appointmentObj->appointmentId);

        $timeslot = $appointmentObj->getAllTimeslots()[0];

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
//			'date'						=>	$appointmentObj->date,
//			'start_time'				=>	$appointmentObj->time,
//			'duration'					=>	(int) $appointmentObj->serviceInf->duration,
//			'extras_duration'			=>	(int) $appointmentObj->getExtrasDuration(),
//			'buffer_before'				=>	(int) $appointmentObj->serviceInf->buffer_before,
//			'buffer_after'				=>	(int) $appointmentObj->serviceInf->buffer_after,
            'customer_id'               =>  $appointmentObj->customerId,
            'status'                    =>  $appointmentObj->status,
            'weight'                    =>  $appointmentObj->weight,
            'starts_at'                 =>  $timeslot->getTimestamp(),
            'ends_at'                   =>  $timeslot->getTimestamp() + ((int) $appointmentObj->serviceInf->duration + (int) $appointmentObj->getExtrasDuration()) * 60,
            'busy_from'                 =>  $timeslot->getTimestamp() - ((int) $appointmentObj->serviceInf->buffer_before) * 60,
            'busy_to'                   =>  $timeslot->getTimestamp()  + ((int) $appointmentObj->serviceInf->duration + (int) $appointmentObj->getExtrasDuration() + (int) $appointmentObj->serviceInf->buffer_after) * 60,
            'note'				        =>	$appointmentObj->note,
        ], $appointmentObj );

		Appointment::where( 'id', $appointmentObj->appointmentId )->update( $appointmentUpdateData );

        AppointmentPrice::where( 'appointment_id', $appointmentObj->appointmentId )->delete();

            AppointmentExtra::where( 'appointment_id', $appointmentObj->appointmentId )->delete();

        foreach ( $appointmentObj->getServiceExtras() AS $extra )
        {
            AppointmentExtra::insert([
                'appointment_id'        =>  $appointmentObj->appointmentId,
                'extra_id'				=>	$extra['id'],
                'quantity'				=>	$extra['quantity'],
                'price'					=>	$extra['price'],
                'duration'				=>	(int)$extra['duration']
            ]);
        }

        foreach ( $appointmentObj->getPrices(true) AS $priceKey => $priceInf )
        {
            AppointmentPrice::insert([
                'appointment_id'            =>  $appointmentObj->appointmentId,
                'unique_key'                =>  $priceKey,
                'price'                     =>  Math::abs( $priceInf->getPrice() ),
                'negative_or_positive'      =>  $priceInf->getNegativeOrPositive()
            ]);
        }

        do_action( 'bkntc_appointment_edited', $appointmentObj->appointmentId, $appointmentObj );
        do_action( 'bkntc_appointment_after_mutation', $appointmentObj->appointmentId );
    }

	public static function deleteAppointment( $appointmentsIDs )
	{
		$appointmentsIDs = is_array( $appointmentsIDs ) ? $appointmentsIDs : [ $appointmentsIDs ];

		foreach ( $appointmentsIDs as $appointmentId )
		{
            do_action('bkntc_appointment_before_mutation', $appointmentId);
            do_action('bkntc_appointment_after_mutation', null);

		    do_action('bkntc_appointment_deleted', $appointmentId );

            AppointmentExtra::where( 'appointment_id', $appointmentId )->delete();
            AppointmentPrice::where('appointment_id', $appointmentId)->delete();
			Appointment::where('id', $appointmentId)->delete();
		    Data::where('row_id', $appointmentId )->where('table_name', Appointment::getTableName())->delete();
        }
	}

	public static function reschedule( $appointmentId, $date, $time, $send_notifications = true )
	{
        $appointmentInfo			= Appointment::get( $appointmentId );
		$customer_id				= $appointmentInfo->customer_id;

		if( !$appointmentInfo )
		{
			throw new \Exception('');
		}

		$serviceInf		= Service::get($appointmentInfo->service_id);
		$staff			= $appointmentInfo->staff_id;
		$getStaffInfo	= Staff::get( $staff );

		$extras_arr = [];
		$appointmentExtras = AppointmentExtra::where('appointment_id', $appointmentId)->fetchAll();

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
			->setServiceId( $serviceInf->id )
			->setServiceExtras( $extras_arr )
            ->setLocationId( $appointmentInfo->location_id )
			->setExcludeAppointmentId( $appointmentInfo->id )
			->setCalledFromBackEnd( false )
			->setShowExistingTimeSlots( true );

		if( ! $selectedTimeSlotInfo->isBookable() )
		{
			throw new \Exception( bkntc__('Please select a valid time! ( %s %s is busy! )', [$date, $time]) );
		}

		$appointmentStatus = Helper::getDefaultAppointmentStatus();

        $duration = ($serviceInf->duration + ExtrasService::calcExtrasDuration( $extras_arr )) * 60;

        do_action('bkntc_appointment_before_mutation', $appointmentId);

        Appointment::where( 'id', $appointmentId )->update([
			'status'     =>  $appointmentStatus,
            'starts_at'  =>  $selectedTimeSlotInfo->getTimestamp(),
            'ends_at'    =>  $selectedTimeSlotInfo->getTimestamp() + $duration,
            'busy_from'  =>  $selectedTimeSlotInfo->getTimestamp(),
            'busy_to'    =>  $selectedTimeSlotInfo->getTimestamp() + $duration,
        ]);

        do_action('bkntc_appointment_after_mutation', $appointmentId);

        return [
            'appointment_status'    =>  $appointmentStatus
        ];
	}

	public static function setStatus( $appointmentId, $status )
	{
        $appointmentInf = Appointment::get($appointmentId);

        if (empty($appointmentInf) || $appointmentInf->status == $status)
            return true;

        do_action('bkntc_appointment_before_mutation', $appointmentId);

		Appointment::where('id', $appointmentId)->update([
			'status'	=>	$status
		]);

        do_action('bkntc_appointment_after_mutation', $appointmentId);

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
		$compareTimestamp   = Date::epoch('-' . $timeLimit . ' minutes');

		DB::DB()->query(
			DB::DB()->prepare("UPDATE `" . DB::table(Appointment::getTableName()) . "` SET `status`='$failedStatus' WHERE `payment_method` <> 'local' AND `payment_status` = 'pending' AND `created_at` < %s", [ $compareTimestamp ])
		);
	}


}