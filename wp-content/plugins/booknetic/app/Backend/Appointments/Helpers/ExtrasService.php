<?php

namespace BookneticApp\Backend\Appointments\Helpers;



use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;

class ExtrasService
{

	public static function calcExtrasPrice( $serviceExtras )
	{
		$extrasPrice = 0;

		foreach ( $serviceExtras AS $extraInf )
		{
			$extrasPrice += $extraInf['quantity'] * $extraInf['price'];
		}

		return $extrasPrice;
	}

	public static function calcExtrasDuration( $serviceExtras )
	{
		$extrasDuration = 0;

        if (empty($serviceExtras)) return 0;

		$uniqueByExtraId = [];
		foreach ( $serviceExtras AS $extra )
		{
			$id = $extra['id'];
			$duration = (int)$extra['duration'] * (int)$extra['quantity'];

			if( !isset( $uniqueByExtraId[ $id ]  ) )
				$uniqueByExtraId[ $id ] = 0;

			$uniqueByExtraId[ $id ] = $uniqueByExtraId[ $id ] > $duration ? $uniqueByExtraId[ $id ] : $duration;
		}

		foreach ( $uniqueByExtraId AS $duration )
		{
			$extrasDuration += $duration;
		}

		return $extrasDuration;
	}

    public static function updateAppointmentExtrasDuration($appointmentId)
    {
        $busyStatuses = "'" . implode( "','", Helper::getBusyAppointmentStatuses() ) . "'";

        DB::DB()->query( DB::DB()->prepare('UPDATE `'.DB::table('appointments').'` SET extras_duration=(SELECT MAX(duration*quantity) FROM `'.DB::table('appointment_extras').'` WHERE appointment_customer_id IN (SELECT id from ' . DB::table('appointment_customers') .  ' where appointment_id = %d and status in (' . $busyStatuses . '))) WHERE id=%d', $appointmentId, $appointmentId) );
    }

}