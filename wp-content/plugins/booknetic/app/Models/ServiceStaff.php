<?php

namespace BookneticApp\Models;

use BookneticApp\Models\Staff;
use BookneticApp\Models\Service;
use BookneticApp\Providers\DB\Model;

class ServiceStaff extends Model
{

	protected static $tableName = 'service_staff';

	public static $relations = [
		'service'   =>  [ Service::class, 'id', 'service_id' ],
		'staff'     =>  [ Staff::class, 'id', 'staff_id' ]
	];

}
