<?php

namespace BookneticApp\Backend\Appointments\Helpers;

use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;

class TimeSlotService extends ServiceDefaults implements \JsonSerializable
{

	private $date;
	private $time;

	private $isBookable;
	private $appointmentId;

	public function __construct( $date, $time )
	{
		$this->date = $date;
		$this->time = $time;
	}


	public function getDate( $formatDate = false )
	{
		return $formatDate ? Date::datee( $this->date ) : $this->date;
	}

	public function getTime( $formatTime = false )
	{
		return $formatTime ? Date::time( $this->time ) : $this->time;
	}

	public function isBookable()
	{
		if( is_null( $this->isBookable ) )
		{
			$this->isBookable           = true;
			$this->appointmentId        = 0;
			$dayDif                     = (int)( (Date::epoch( $this->date ) - Date::epoch()) / 60 / 60 / 24 );
			$availableDaysForBooking    = Helper::getOption('available_days_for_booking', '365');

			if( ! $this->calledFromBackEnd && $dayDif > $availableDaysForBooking )
			{
				$this->isBookable = false;
			}
			else
			{
				$selectedTimeSlotInfo = $this->getInfo();

				if( empty( $selectedTimeSlotInfo ) )
				{
					$this->isBookable = false;
				}
				else if( $selectedTimeSlotInfo['appointment_id'] > 0 )
				{

					if( ( $selectedTimeSlotInfo['number_of_customers'] + $this->totalCustomerCount ) > $selectedTimeSlotInfo['max_capacity'] )
					{
						$this->isBookable = false;
					}
					else
					{
						$this->appointmentId = $selectedTimeSlotInfo['appointment_id'];
					}
				}
				else
                {
                    if( ( $selectedTimeSlotInfo['number_of_customers'] + $this->totalCustomerCount ) > $selectedTimeSlotInfo['max_capacity'] )
                    {
                        $this->isBookable = false;
                    }
                }
			}
		}

		return $this->isBookable;
	}

	public function getAppointmentId()
	{
		if( is_null( $this->appointmentId ) )
		{
			$this->isBookable();
		}

		return $this->appointmentId;
	}

	public function getInfo()
	{
		$allTimeslotsForToday = new CalendarService( Date::dateSQL( $this->getDate(), '-1 days' ), Date::dateSQL( $this->getDate(), '+1 days' ) );
		$allTimeslotsForToday->setDefaultsFrom( $this );
		$allTimeslotsForToday = $allTimeslotsForToday->getCalendar();

        foreach ( $allTimeslotsForToday['dates'] as $dateKey=>$dateVal )
        {
            foreach ($dateVal as $key => $value )
            {
                if( $dateKey != $value['date'] )
                {
                    $allTimeslotsForToday['dates'][ $value['date'] ][] = $value;
                    unset( $allTimeslotsForToday['dates'][  $dateKey  ][$key]);
                }
            }
        }

		if( isset( $allTimeslotsForToday['dates'][ $this->getDate() ] ) )
		{
			$selectedTimeEpoch = Date::epoch( $this->getTime() );

			foreach( $allTimeslotsForToday['dates'][ $this->getDate() ] AS $timeSlotInfo )
			{
				if( Date::epoch( $timeSlotInfo['start_time'] ) == $selectedTimeEpoch )
				{
					return $timeSlotInfo;
				}
			}
		}

		return [];
	}

	public function toArr()
	{
		return [
			'date'              =>  $this->getDate(),
			'time'              =>  $this->getTime(),
			'date_format'       =>  $this->getDate( true ),
			'time_format'       =>  $this->getTime( true ),
			'is_bookable'       =>  $this->isBookable(),
			'appointment_id'    =>  $this->getAppointmentId()
		];
	}

	public function jsonSerialize()
	{
		return $this->toArr();
	}

}