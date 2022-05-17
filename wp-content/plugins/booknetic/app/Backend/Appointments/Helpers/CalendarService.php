<?php

namespace BookneticApp\Backend\Appointments\Helpers;

use BookneticApp\Models\Appointment;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;
use DateTime;
use DateTimeZone;

class CalendarService extends ServiceDefaults
{

	public $dateFrom;
	public $dateTo;

    public $serverTimezone;
    public $clientTimezone;

    public $serviceTotalDuration;
    public $serviceMarginAfter;
    public $serviceMarginBefore;

    // doit: bunu general settings'e cixarmaq
    public $flexibleTimeslot = true;

	public function __construct( $dateFrom = null, $dateTo = null )
	{
		$this->dateFrom = $dateFrom;
		$this->dateTo   = is_null( $dateTo ) ? $dateFrom : $dateTo;

        $this->serverTimezone = Date::getTimeZone(false);
        $this->clientTimezone = Date::getTimeZone(true);

		/**
		 * Odenish edilmemish appointmentlerin statusunu cancel edek ki, orani da booking ede bilsin...
		 */
		AppointmentService::cancelUnpaidAppointments();
	}

	public function getCalendar( $groupBy = 'day' )
	{
		if( !( $this->staffId > 0 ) )
			return $this->getAllStaffCalendars();

		$calendarData   = [
			'dates'     =>  [],
            'fills'     =>  []
		];

		if( ! $this->calledFromBackEnd )
		{
			$min_time_req_prior_booking = Helper::getOption('min_time_req_prior_booking', 0);
			$min_time_req_prior_booking = $min_time_req_prior_booking > 0 ? $min_time_req_prior_booking * 60 : $min_time_req_prior_booking;
			$this->dateFrom = Date::epoch( $this->dateFrom ) < (Date::epoch() + $min_time_req_prior_booking) ? Date::dateSQL( Date::epoch() + $min_time_req_prior_booking ) : $this->dateFrom;

			if( Date::epoch( $this->dateTo ) < Date::epoch( $this->dateFrom ) )
			{
				return $calendarData;
			}
		}

        $this->serviceTotalDuration = $this->getServiceInf()->duration + ExtrasService::calcExtrasDuration( $this->serviceExtras );
        $this->serviceMarginBefore = (int) $this->getServiceInf()->buffer_before;
        $this->serviceMarginAfter = $this->serviceTotalDuration + $this->getServiceInf()->buffer_after;

        $rangesFromTimesheet = $this->rangesFromTimesheet();

        $staffRanges = $this->rangesFromStaff($rangesFromTimesheet, $groupBy);
        $calendarData['dates'] = $staffRanges['timeslots'];
        $calendarData['fills'] = $staffRanges['fills'];

        return $calendarData;
	}

    private function rangesFromTimesheet()
    {
        $dateFrom = (new DateTime($this->dateFrom, $this->clientTimezone))->setTimezone($this->serverTimezone);
        $dateFrom->modify('-' . $this->serviceMarginBefore . ' minutes');
        $dateFrom->setTime(0, 0, 0);

        $dateTo = (new DateTime($this->dateTo . " 24:00:00", $this->clientTimezone))->setTimezone($this->serverTimezone);
        $dateTo->modify("+" . $this->serviceMarginAfter . " minutes");
        $dateTo->setTime(24, 0, 0);

	    $timesheetService = new TimeSheetService();
	    $timesheetService->setDefaultsFrom( $this );
	    $busySlots = [];
	    $timesheetForAllDay = [];

		for ( $dateFromEpoch = $dateFrom->getTimestamp(); $dateFromEpoch <= $dateTo->getTimestamp(); $dateFromEpoch = Date::epoch( $dateFromEpoch, '+1 day' ) )
		{
			$timesheet = $timesheetService->getTimesheetByDate( Date::dateSQL( $dateFromEpoch ) );
			$timesheetForAllDay[ Date::dateSQL( $dateFromEpoch ) ] = $timesheet->toArr();

	        // Diqqet: isDayOff() ichinde isHoliday()-i de yoxlayir.
			if( $timesheet->isDayOff() )
			{
				$busySlots[] = [ $dateFromEpoch , Date::epoch( $dateFromEpoch, '+1 day' ) ];
			}
			else
			{
				if( $this->isDateBasedService() )
				{
					$timesheetForAllDay[ Date::dateSQL( $dateFromEpoch ) ]['start'] = '00:00';
					$timesheetForAllDay[ Date::dateSQL( $dateFromEpoch ) ]['end']   = '24:00';
				}
				else
				{
					foreach ( $timesheet->breaks() as $break )
					{
						$busySlots[] = [ Date::epoch( Date::dateSQL( $dateFromEpoch ) . " " . $break->startTime() ) , Date::epoch( Date::dateSQL( $dateFromEpoch ) . " " . $break->endTime() ) ];
					}

					if( $dateFromEpoch != Date::epoch( Date::dateSQL( $dateFromEpoch ) . " " . $timesheet->startTime() ) )
						$busySlots[] = [ $dateFromEpoch , Date::epoch( Date::dateSQL( $dateFromEpoch ) . " " . $timesheet->startTime() ) ];

					if( Date::epoch( Date::dateSQL( $dateFromEpoch ) . " " . $timesheet->endTime() ) != Date::epoch( $dateFromEpoch, '+1 day' ) )
						$busySlots[] = [ Date::epoch( Date::dateSQL( $dateFromEpoch ) . " " . $timesheet->endTime() ) , Date::epoch( $dateFromEpoch, '+1 day' ) ];
				}
			}
		}

        $busySlots = apply_filters( 'bkntc_busy_slots' , $busySlots , $this );

        return compact('timesheetForAllDay' , 'busySlots');
	}

    public function rangesFromStaff($rangesFromTimesheet, $groupBy)
    {
        $serverTimezone = $this->serverTimezone;
        $clientTimezone = $this->clientTimezone;

        $busyStatuses = Helper::getBusyAppointmentStatuses();

        $staffAppointments = Appointment::where('busy_from', '<=', (new DateTime($this->dateTo . " 24:00:00", $this->clientTimezone))->setTimezone($this->serverTimezone)->modify("+" . $this->serviceMarginAfter . " minutes")->getTimestamp() )
            ->where( 'busy_to', '>=', ((new DateTime($this->dateFrom . " 00:00:00"))->setTimezone($this->serverTimezone)->modify("-" . $this->serviceMarginBefore . " minutes")->getTimestamp())  )
            ->where( 'staff_id', $this->staffId )
            ->where( 'status', 'in', $busyStatuses )
            ->select(['location_id', 'service_id', 'busy_from', 'busy_to', 'starts_at', 'ends_at'])
            ->select('SUM(weight) as total_weight')
            ->groupBy(['starts_at', 'staff_id', 'location_id', 'service_id']);

        if( is_numeric( $this->excludeAppointmentId ) && $this->excludeAppointmentId> 0 )
        {
            $staffAppointments->where( Appointment::getField( 'id' ), '<>', (int) $this->excludeAppointmentId );
        }

        $staffAppointments = apply_filters('bkntc_staff_appointments', $staffAppointments->fetchAll(), $this);

        $timeSlot = [];
        $dayFillPercents = [];

        $min_time_req_prior_booking = Helper::getOption('min_time_req_prior_booking', 0);
        $min_time_req_prior_booking = (new DateTime())->modify("+$min_time_req_prior_booking minutes");


        for ( $i = (new DateTime($this->dateFrom . " 00:00:00", $clientTimezone))->setTimezone($serverTimezone) ; $i < (new DateTime($this->dateTo . " 24:00:00", $clientTimezone))->setTimezone($serverTimezone)->modify('+1 day') ; $i->modify('+1 day') )
        {
            $dayStart = (clone $i)->format('Y-m-d 00:00:00');
            $dayStart = new DateTime($dayStart , $serverTimezone);
            $dayEnd = (clone $dayStart)->modify("+1 day");

            $timesheetForToday = $rangesFromTimesheet['timesheetForAllDay'][ $i->format('Y-m-d')] ;

            $j = new DateTime($dayStart->format('Y-m-d')." {$timesheetForToday['start']}" , $serverTimezone );
            while  ( $j < $dayEnd )
            {

                if ( $groupBy === 'day' && ! array_key_exists((clone $j)->setTimezone($clientTimezone)->format('Y-m-d') , $timeSlot ))
                {
                    $timeSlot[(clone $j)->setTimezone($clientTimezone)->format('Y-m-d')] = [];
                }

                if ( $j < $min_time_req_prior_booking && ! $this->calledFromBackEnd ){
                    $j->modify("+" . $this->getTimeSlotLength() . " minutes");
                    continue;
                }

                $collision = RangeService::range_overlap_with_ranges( $rangesFromTimesheet['busySlots'] , [ (clone $j)->modify("-" . $this->serviceMarginBefore . " minutes")->getTimestamp() , (clone $j)->modify("+" . ($this->serviceMarginAfter) . " minutes")->getTimestamp()]);
                if($collision !== false)
                {
                    if ($this->flexibleTimeslot)
                    {
                        $j = DateTime::createFromFormat('U', $collision[1])->setTimezone($serverTimezone)->modify("+" . $this->serviceMarginBefore . " minutes");
                        continue;
                    }

                    $j->modify("+" . $this->getTimeSlotLength() . " minutes");
                    continue;
                }

                $matchedAppointment = null;
                $matchedAppointmentCanBook = false;
                $matchedAppointmentCanBookForOtherSlots = false;
                foreach ($staffAppointments as $appointment)
                {
                    $appointmentStart = (new DateTime())->setTimezone($serverTimezone)->setTimestamp($appointment->starts_at);
                    $appointmentEnd = (new DateTime())->setTimezone($serverTimezone)->setTimestamp($appointment->ends_at);


                    $appointmentRealStart = (new DateTime())->setTimezone($serverTimezone)->setTimestamp($appointment->busy_from);
                    $appointmentRealEnd = (new DateTime())->setTimezone($serverTimezone)->setTimestamp($appointment->busy_to);
                    if (! ((clone $j)->modify('-' . $this->serviceMarginBefore . ' minutes') >= $appointmentRealEnd || ((clone $j)->modify('+' . $this->serviceMarginAfter . ' minutes') <= $appointmentRealStart)) )
                    {
                        $matchedAppointment = $appointment;
                        $matchedAppointmentCanBookForOtherSlots = (
                            $matchedAppointment->location_id == $this->getLocationId() &&
                            $matchedAppointment->service_id == $this->getServiceId() &&
                            $matchedAppointment->total_weight < $this->getServiceInf()->max_capacity
                        );

                        $matchedAppointmentCanBook = (
                            $matchedAppointment->location_id == $this->getLocationId() &&
                            $matchedAppointment->service_id == $this->getServiceId() &&
                            $matchedAppointment->total_weight < $this->getServiceInf()->max_capacity &&
                            $appointmentStart->getTimestamp() == $j->getTimestamp() &&
                            $appointmentEnd->getTimestamp() == ((clone $j)->modify('+' . $this->serviceTotalDuration . ' minutes'))->getTimestamp()
                        );

                        $matchedAppointment->realEndDT = $appointmentRealEnd;
                        break;
                    }
                }

                if( $matchedAppointment == null || $matchedAppointmentCanBook )
                {
                    $start = (clone $j);
                    $end = ((clone $j)->modify('+' . $this->serviceTotalDuration . ' minutes'));

					// doit: original timelar H:i ile yox da, birbasha epoch ile getmelidi ki, 0 bug qalsin DST-da. Meselen Berlinde saat chekilmesi geriye oldugda 02:00-03:00 2 defe tekrarlanacaq timeslot olacaq... ve original time-larida eyni qaydada tekrarlanacaq... cunki ora H:i gedir. amma full epoch getse (timestamp(Y-m-d H:i:s)) o halda 0 bug olacag...

                    $cSlot = [
                        'date' => $start->setTimezone($serverTimezone)->format('Y-m-d'),
                        'start_time' => $start->setTimezone($serverTimezone)->format('H:i'),
                        'end_time' => $end->setTimezone($serverTimezone)->format('H:i'),
                        'start_time_format' => $start->setTimezone($clientTimezone)->format(Date::formatTime()),
                        'end_time_format' => $end->setTimezone($clientTimezone)->format(Date::formatTime()),
                        'buffer_before' => '0',
                        'buffer_after' => '0',
                        'duration' => $this->serviceTotalDuration,
                        'max_capacity' => $this->getServiceInf()->max_capacity,
                        'weight' => empty($matchedAppointment) ? 0 : $matchedAppointment->total_weight,
                    ];

                    if ($groupBy === 'day')
                    {
                        $timeSlot[(clone $j)->setTimezone($clientTimezone)->format('Y-m-d')][] = $cSlot;
                    }
                    else if ($groupBy == 'timestamp')
                    {
                        $timeSlot[$start->getTimestamp()] = $cSlot;
                    }

                    $dayFillPercents[(clone $j)->setTimezone($clientTimezone)->format('Y-m-d')][] = 1;
                }
                else
                {
                    $dayFillPercents[(clone $j)->setTimezone($clientTimezone)->format('Y-m-d')][] = 0;
                }

                if ($matchedAppointment !== null && $this->flexibleTimeslot && !$matchedAppointmentCanBookForOtherSlots)
                {
                    $j = (clone $matchedAppointment->realEndDT)->modify("+" . $this->serviceMarginBefore . " minutes");;
                }
                else
                {
                    $j->modify("+" . $this->getTimeSlotLength() . " minutes");
                }

            }

        }

        $dayFillPercentsZoomed = [];
        foreach ($dayFillPercents as $k => $v)
        {
            $dayFillPercentsZoomed[$k] = RangeService::zoom($v, 17);
        }

        return [
            'timeslots' => $timeSlot,
            'fills'     => $dayFillPercentsZoomed
        ];
    }

	public function getCalendarByDayOfWeek( $dayOfWeek, $search = '' )
	{
		$calendarData       = [];
		$timesheetService   = new TimeSheetService();
		$timesheetService->setDefaultsFrom( $this );

		$weeklyTimeSheet    = $timesheetService->getWeeklyTimesheet();

		if( ! $weeklyTimeSheet->isCorrect() )
		{
			return $calendarData;
		}

		if( $dayOfWeek == -1 )
		{
			$tStart = $weeklyTimeSheet->minStartTime();
			$tEnd   = $weeklyTimeSheet->maxStartTime();

			if( Date::epoch( $tStart ) > Date::epoch( $tEnd ) )
			{
				$tStart   = '00:00';
				$tEnd     = '23:59';
			}

			$timesheetObj = new TimeSheetObject( [
				"day_off"   => 0,
				"start"     => $tStart,
				"end"       => $tEnd,
				"breaks"    => []
			] );
		}
		else
		{
			$timesheetObj = $weeklyTimeSheet->getDay( $dayOfWeek );
		}

		$tStart		= Date::epoch( $timesheetObj->startTime() ) + $this->getServiceInf()->buffer_before * 60;
		$tEnd		= Date::epoch( $timesheetObj->endTime() ) - ( $this->getServiceInf()->buffer_before + $this->getServiceInf()->buffer_after + $this->getServiceInf()->duration ) * 60;

		if( $timesheetObj->isDayOff() )
		{
			return $calendarData;
		}

		$timeslotLength = $this->getTimeSlotLength();
        $extrasDuration     = ExtrasService::calcExtrasDuration( $this->serviceExtras );

		while( $tStart <= $tEnd )
		{
            $fullTimeStart      = $tStart - $this->getServiceInf()->buffer_before * 60;
            $fullTimeEnd        = $fullTimeStart + ( $this->getServiceInf()->buffer_before + $this->getServiceInf()->duration + $this->getServiceInf()->buffer_after + $extrasDuration ) * 60;


            $timeId     = Date::timeSQL( $tStart );
			$timeText   = Date::time( $tStart );
			$tStart     += $timeslotLength * 60;



            if( !empty( $search ) && strpos( $timeText, $search ) === false )
			{
				continue;
			}

            $isBreakTime = false;

            foreach ( $timesheetObj->breaks() AS $break )
            {
                if( $break->isTheTimeslotABreakTime( $fullTimeStart, $fullTimeEnd) )
                {
                    $isBreakTime    = true;
                    $tStart     = Date::epoch(  $break->endTime() ) + $this->getServiceInf()->buffer_before * 60;
                    break;
                }
            }

            if ( $isBreakTime )
                continue;

			$calendarData[] = [
				'id'	=>	$timeId,
				'text'	=>	$timeText
			];
		}

		return $calendarData;
	}

	public function getDayOffs()
	{
		$cursor 				= Date::epoch( $this->dateFrom );
		$endDate 				= Date::epoch( $this->dateTo );
		$dayOffsArr 			= [];
		$disabledDaysOfWeek 	= [ true, true, true, true, true, true, true ];

		$timesheetService = new TimeSheetService();
		$timesheetService->setDefaultsFrom( $this );

		while( $cursor <= $endDate )
		{
			$curDate		= Date::dateSQL( $cursor );
			$curDayOfWeek	= Date::dayOfWeek( $cursor ) - 1;

			$timesheetOfDay = $timesheetService->getTimesheetByDate( $curDate );

			if( ! $timesheetOfDay->isDayOff() )
			{
				$disabledDaysOfWeek[ $curDayOfWeek ] = false;
			}

			if( $timesheetOfDay->isHoliday() )
			{
				$dayOffsArr[ $curDate ] = 1;
			}
			else if( $timesheetOfDay->isSpecialTimesheet() && $timesheetOfDay->isDayOff() )
			{
				$dayOffsArr[ $curDate ] = 1;
			}

			$cursor = Date::epoch( $cursor, '+1 days' );
		}

		return [
			'day_offs'				=> $dayOffsArr,
			'disabled_days_of_week'	=> $disabledDaysOfWeek,
			'timesheet'				=> $timesheetService->getWeeklyTimesheet()
		];
	}

	private function getAllStaffCalendars()
	{
		$allStaffIDs = AnyStaffService::staffByService( $this->serviceId, $this->locationId );

		$dates      = [];
        $fills      = [];

		foreach ( $allStaffIDs AS $staffID )
		{
			$getStafsCalendar = new CalendarService( $this->dateFrom, $this->dateTo );
			$getStafsCalendar->setDefaultsFrom( $this )->setStaffId( $staffID );
			$getStafsCalendar = $getStafsCalendar->getCalendar();

			$dates[]        = $getStafsCalendar['dates'];
            foreach ($getStafsCalendar['fills'] as $k => $v)
            {
                if (!array_key_exists($k, $fills))
                {
                    $fills[$k] = RangeService::zoom([0], 17);
                }
                $fills[$k] = RangeService::orArr($fills[$k], $v);
            }
		}

		return [
			'dates'		=>	$this->sortTimeslotsAtoZ( $this->mergeDates( $dates ) ),
            'fills'     =>  $fills
		];
	}

	private function mergeDates( $allStaffDates )
	{
		$mergedDatesArr = array_shift( $allStaffDates );

		foreach ( $allStaffDates AS $perStaffDates )
		{
			foreach ( $perStaffDates AS $dateKey => $datesValue )
			{
				if( !isset( $mergedDatesArr[ $dateKey ] ) )
				{
					$mergedDatesArr[ $dateKey ] = $datesValue;
				}
				else
				{
					foreach ( $datesValue AS $dateValueInfo )
					{
						$hasSameTimeSlot = false;

						foreach ( $mergedDatesArr[ $dateKey ] AS $savedDates )
						{
							if( $savedDates['start_time'] == $dateValueInfo['start_time'] )
							{
								$hasSameTimeSlot = true;
								break;
							}
						}

						if( !$hasSameTimeSlot )
						{
							$mergedDatesArr[ $dateKey ][] = $dateValueInfo;
						}
					}
				}
			}
		}

		return $mergedDatesArr;
	}

	private function sortTimeslotsAtoZ( $dates )
	{
		foreach ( $dates AS $dateKey => $timesValue )
		{
			$sortByKey = $this->calledFromBackEnd ? 'start_time' : 'start_time_format';

			usort($timesValue, function ($a, $b) use ( $sortByKey )
			{
				if ( strtotime( $a[ $sortByKey ] ) == strtotime( $b[ $sortByKey ] ) )
				{
					return 0;
				}

				return ( strtotime( $a[ $sortByKey ] ) < strtotime( $b[ $sortByKey ] ) ) ? -1 : 1;
			});

			$dates[ $dateKey ] = $timesValue;
		}

		return $dates;
	}

	private function isDateBasedService()
	{
		return $this->getServiceInf()->duration >= 24 * 60;
	}

	private function getTimeSlotLength()
	{
		if( $this->getServiceInf()->timeslot_length == 0 )
		{
			$slot_length_as_service_duration = Helper::getOption('slot_length_as_service_duration', '0');

			$timeslotLength = $slot_length_as_service_duration ? $this->getServiceInf()->duration : Helper::getOption('timeslot_length', 5);
		}
		else if( $this->getServiceInf()->timeslot_length == -1 )
		{
			$timeslotLength = $this->getServiceInf()->duration;
		}
		else
		{
			$timeslotLength = (int)$this->getServiceInf()->timeslot_length;

			$timeslotLength = $timeslotLength > 0 && $timeslotLength <= 300 ? $timeslotLength : 5;
		}

		if( $this->isDateBasedService() && $timeslotLength < 24*60 )
		{
			$timeslotLength = 24*60;
		}

		return $timeslotLength;
	}

}