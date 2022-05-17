<?php

namespace BookneticApp\Backend\Appointments\Helpers;

use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;

class TimeSlotService extends ServiceDefaults implements \JsonSerializable
{

	private $date;
	private $time;

	private $isBookable;

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

    public function getTimestamp()
    {
        return Date::epoch( $this->date . ' ' . $this->time );
    }

	public function isBookable()
	{
		if( is_null( $this->isBookable ) )
		{
			$this->isBookable           = true;
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
				else
                {
                    if( ( $selectedTimeSlotInfo['weight'] + $this->totalCustomerCount ) > $selectedTimeSlotInfo['max_capacity'] )
                    {
                        $this->isBookable = false;
                    }
                }
			}
		}

		return $this->isBookable;
	}

	public function getInfo()
	{
		$allTimeslotsForToday = new CalendarService( Date::dateSQL( $this->getDate(), '-1 days' ), Date::dateSQL( $this->getDate(), '+1 days' ) );
		$allTimeslotsForToday->setDefaultsFrom( $this );
		$slots = $allTimeslotsForToday->getCalendar('timestamp');

        if (array_key_exists($this->getTimestamp(), $slots['dates']))
        {
            return $slots['dates'][$this->getTimestamp()];
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
			'is_bookable'       =>  $this->isBookable()
		];
	}

	public function jsonSerialize()
	{
		return $this->toArr();
	}

}