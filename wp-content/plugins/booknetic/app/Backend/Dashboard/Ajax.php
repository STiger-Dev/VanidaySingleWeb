<?php

namespace BookneticApp\Backend\Dashboard;


use BookneticApp\Models\Appointment;
use BookneticApp\Models\AppointmentCustomer;
use BookneticApp\Models\Customer;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;

class Ajax extends Controller
{

	public function get_stat()
	{
		Capabilities::must( 'dashboard' );

		$type	= Helper::_post('type', 'today', 'string');
		$start	= Helper::_post('start', '', 'string');
		$end	= Helper::_post('end', '', 'string');

		switch( $type )
		{

			case 'today':
				$start = Date::dateSQL();
				$end = Date::dateSQL();
				break;

			case 'yesterday':
				$start = Date::dateSQL( 'yesterday' );
				$end = Date::dateSQL('yesterday' );
				break;

			case 'tomorrow':
				$start = Date::dateSQL('tomorrow' );
				$end = Date::dateSQL('tomorrow' );
				break;

			case 'this_week':
				$start = Date::dateSQL('monday this week' );
				$end = Date::dateSQL('sunday this week' );
				break;

			case 'last_week':
				$start = Date::dateSQL('monday previous week' );
				$end = Date::dateSQL('sunday previous week' );
				break;

			case 'this_month':
				$start = Date::format( 'Y-m-01' );
				$end = Date::format( 'Y-m-t' );
				break;

			case 'this_year':
				$start = Date::format( 'Y-01-01' );
				$end = Date::format( 'Y-12-31' );
				break;

			case 'custom':
				$start = Date::dateSQL( Date::reformatDateFromCustomFormat( $start ) );
				$end = Date::dateSQL( Date::reformatDateFromCustomFormat( $end ) );
				break;

		}

        $result = Appointment::select( [
            'count(0) AS appointments',
            'sum( (SELECT sum(`price`*`negative_or_positive`) FROM `' . DB::table( 'appointment_customer_prices' ) . '` WHERE `appointment_customer_id`=' . AppointmentCustomer::getField( 'id' ) . ' ) ) AS `revenue`',
            'sum(' . Appointment::getField( 'duration' ) . ' + ' . Appointment::getField( 'extras_duration' ) . ' ) AS duration',
        ] )->innerJoin( 'appointment_customers', [] )
                                     ->where( AppointmentCustomer::getField( 'status' ), 'IN', Helper::getBusyAppointmentStatuses() )
                                     ->where( Appointment::getField( 'date' ), 'BETWEEN', DB::field( "'$start' and '$end'" ) )
                                     ->fetch();

		$customers = Customer::where(Customer::getField('created_at') , 'BETWEEN' , DB::field("'$start' and '$end'"))->count();

        $totalAccordingToStatus = Appointment::innerJoin('appointment_customers', [])
            ->select(['count(status) as count' , 'status'] , true )
            ->where( Appointment::getField('date') , 'between' , DB::field("'$start' and '$end'"))
            ->groupBy(['status'])
            ->fetchAll();

        $totalAccordingToStatus = Helper::assocByKey($totalAccordingToStatus,'status');

		return $this->response(true, [
			'appointments'	    => $result['appointments'],
			'revenue'		    => Helper::price( $result['revenue'] ),
			'duration'		    => Helper::secFormat( (int)$result['duration'] * 60 ),
            'count_by_status'   => $totalAccordingToStatus,
            'customers'         => $customers
		]);
	}

}
