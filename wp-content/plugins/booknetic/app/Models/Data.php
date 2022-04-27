<?php

namespace BookneticApp\Models;

use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\DB\MultiTenant;

class Data extends Model
{
    use MultiTenant;

	protected static $tableName = 'data';

	public static $relations = [
		'appointments'          => [ Appointment::class, 'row_id', 'id' ],
		'appointment_customers' => [ AppointmentCustomer::class, 'row_id', 'id' ],
		'location'              => [ Location::class, 'row_id', 'id' ],
		'service'               => [ Service::class, 'row_id', 'id' ],
		'staff'                 => [ Staff::class, 'row_id', 'id' ],
		'customers'             => [ Customer::class, 'row_id', 'id' ]
	];
}
