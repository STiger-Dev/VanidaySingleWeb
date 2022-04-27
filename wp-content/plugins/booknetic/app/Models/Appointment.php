<?php

namespace BookneticApp\Models;

use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\DB\MultiTenant;
use BookneticApp\Providers\DB\QueryBuilder;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\DB\Model;

/**
 * @property-read int $id
 * @property int $location_id
 * @property int $service_id
 * @property int $staff_id
 * @property string $date
 * @property string $start_time
 * @property int $duration
 * @property int $extras_duration
 * @property int $buffer_before
 * @property int $buffer_after
 * @property string $reminder_status
 * @property int $tenant_id
 */
class Appointment extends Model
{
	use MultiTenant {
		booted as private tenantBoot;
	}

	public static $relations = [
		'appointment_customers' => [ AppointmentCustomer::class, 'appointment_id', 'id' ],
		'extras'                => [ AppointmentExtra::class ],
		'location'              => [ Location::class, 'id', 'location_id' ],
		'service'               => [ Service::class, 'id', 'service_id' ],
		'staff'                 => [ Staff::class, 'id', 'staff_id' ]
	];

	public static function booted()
	{
		self::tenantBoot();

		self::addGlobalScope('staff_id', function ( QueryBuilder $builder, $queryType )
		{
			if( ! Permission::isBackEnd() || Permission::isAdministrator() )
				return;

			$builder->where('staff_id', Permission::myStaffId());
		});
	}

}
