<?php

namespace BookneticApp\Backend\Calendar;

use BookneticAddon\Googlecalendar\Integration\GoogleCalendarService;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;

class Ajax extends \BookneticApp\Providers\Core\Controller
{

	public function get_calendar()
	{
		Capabilities::must( 'calendar' );

		$startTime			= Helper::_post('start', '', 'string');
		$endTime			= Helper::_post('end', '', 'string');

		$startTime			= Date::dateSQL( $startTime );
		$endTime			= Date::dateSQL( $endTime );

		$stafFilter			= Helper::_post('staff', [], 'array');
		$locationFilter		= Helper::_post('location', '0', 'int');
		$servicesFilter		= Helper::_post('service', '0', 'int');

		$stafFilterSanitized = [];
		foreach ( $stafFilter AS $staffId )
		{
			if( is_numeric( $staffId ) && $staffId > 0 )
			{
				$stafFilterSanitized[] = (int)$staffId;
			}
		}

		$stafFilter = !empty( $stafFilterSanitized ) ? " AND tb1.staff_id IN ('" . implode("','", $stafFilterSanitized) . "')" : '';
		$locationFilter = !empty( $locationFilter ) ? " AND location_id='$locationFilter'" : '';
		$servicesFilter = !empty( $servicesFilter ) ? " AND service_id='$servicesFilter'" : '';

		$dataTable = DB::DB()->get_results(
			DB::DB()->prepare( "
				SELECT 
					tb1.*, 
					(SELECT `name` FROM `" . DB::table('locations') . "` WHERE id=tb1.location_id) AS location_name,
					tb3.name AS service_name, tb3.duration AS service_duration, tb3.color AS service_color,
					tb2.name AS staff_name,tb2.id as staff_id, tb2.profile_image AS staff_profile_image,
					(SELECT group_concat( (SELECT concat(`first_name`, ' ', `last_name`) FROM `" . DB::table('customers') . "` WHERE `id`=subtb1.`customer_id`), '::', `status` ) FROM `" . DB::table('appointment_customers') . "` subtb1 WHERE `appointment_id`=tb1.`id`) AS customers
				FROM `" . DB::table('appointments') . "` tb1
				LEFT JOIN `" . DB::table('staff') . "` tb2 ON tb2.id=tb1.staff_id
				LEFT JOIN `" . DB::table('services') . "` tb3 ON tb3.id=tb1.service_id
				WHERE tb1.date>=%s AND tb1.date<=%s {$stafFilter} {$locationFilter} {$servicesFilter}" . Permission::queryFilter('appointments', 'tb1.staff_id', 'AND', 'tb1.tenant_id')
			, [ $startTime, $endTime ] ),
			ARRAY_A
		);

		$events = [];
		foreach( $dataTable AS $dataInfo )
		{
		    $appointmentEventId = Appointment::getData($dataInfo['id'], 'google_event_id');

			$customers = explode(',', $dataInfo['customers']);
			$customersCount = count($customers);
			if( $customersCount == 1 )
			{
			    $customer	= explode('::', reset($customers));
                if( !empty($customer) && count($customer) == 2 )
                {
                    $status		= htmlspecialchars($customer[1]);
                    $customer	= htmlspecialchars($customer[0]);
                }

			}
			else
			{
				$customer	= '';
				$status		= '';
			}

			$additionalDays = (int)( ($dataInfo['duration'] + $dataInfo['extras_duration']) / 60 / 24 );
			$additionalDays = ( $additionalDays >= 2 ) ? $additionalDays : 0;

			$events[] = [
				'appointment_id'		=>	(int)$dataInfo['id'],
				'title'					=>	htmlspecialchars( $dataInfo['service_name'] ),
                'event_title'			=>	'',
				'color'					=>	empty($dataInfo['service_color']) ? '#ff7675' : $dataInfo['service_color'],
				'text_color'			=>	static::getContrastColor( empty($dataInfo['service_color']) ? '#ff7675' : $dataInfo['service_color'] ),
				'location_name'			=>	htmlspecialchars( $dataInfo['location_name'] ),
				'service_name'			=>	htmlspecialchars( $dataInfo['service_name'] ),
				'staff_name'			=>	htmlspecialchars( $dataInfo['staff_name'] ),
				'staff_id'			    =>	$dataInfo['staff_id'] ,
				'resourceId'			=>	$dataInfo['staff_id'] ,
				'staff_profile_image'	=>	Helper::profileImage( $dataInfo['staff_profile_image'], 'Staff' ),
				'start_time'			=>	Date::time( $dataInfo['start_time'] ),
				'end_time'				=>	Date::time( Date::epoch($dataInfo['start_time']) + ($dataInfo['duration'] + $dataInfo['extras_duration']) * 60 ),
				'start'					=>	Date::dateSQL( $dataInfo['date'] ) . 'T' . Date::format( 'H:i:s', $dataInfo['start_time'] ),
				'end'                   =>  Date::format( 'Y-m-d\TH:i:s', Date::epoch( $dataInfo['date'] . ' ' . $dataInfo['start_time'] ) + ( $dataInfo[ 'duration' ] + $dataInfo[ 'extras_duration' ] ) * 60 ),
				'customer'				=>	$customer,
				'customers_count'		=>	$customersCount,
				'status'				=>	Helper::appointmentStatus( $status )
			];
		}

		$events = apply_filters('bkntc_calendar_events', $events, $startTime, $endTime);

		return $this->response( true, [
			'data'	=>	$events
		] );
	}

	private static function getContrastColor( $hexcolor )
	{
		Capabilities::must( 'calendar' );

		$r = hexdec(substr($hexcolor, 1, 2));
		$g = hexdec(substr($hexcolor, 3, 2));
		$b = hexdec(substr($hexcolor, 5, 2));
		$yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

		return ($yiq >= 185) ? '#292D32' : '#FFF';
	}

}
