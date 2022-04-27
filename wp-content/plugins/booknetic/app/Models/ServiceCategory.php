<?php

namespace BookneticApp\Models;

use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\DB\MultiTenant;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read int $parent_id
 * @property-read int $tenant_id
 */
class ServiceCategory extends Model
{
	use MultiTenant;

	protected static $tableName = 'service_categories';

}