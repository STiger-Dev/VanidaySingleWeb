<?php

namespace BookneticApp\Models;

use BookneticApp\Models\Appointment;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\DB\MultiTenant;
use BookneticApp\Providers\DB\QueryBuilder;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read int $user_id
 * @property-read string $email
 * @property-read string $phone_number
 * @property-read string $about
 * @property-read string $profile_image
 * @property-read string $locations
 * @property-read string $google_access_token
 * @property-read int $is_active
 * @property-read int $tenant_id
 * @property-read string $profession
 */
class Staff extends Model
{
	use MultiTenant {
		booted as private tenantBoot;
	}

	protected static $tableName = 'staff';

	public static $relations = [
		'appointments'  =>  [ Appointment::class, 'staff_id', 'id' ]
	];

	public static function booted()
	{
		self::tenantBoot();

		self::addGlobalScope('user_id', function ( QueryBuilder $builder, $queryType )
		{
			if( ! Permission::isBackEnd() || Permission::isAdministrator() )
				return;

			$builder->where('user_id', Permission::userId());
		});
	}

}