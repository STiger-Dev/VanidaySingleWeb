<?php

namespace BookneticApp\Models;

use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\DB\MultiTenant;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string $image
 * @property-read string $address
 * @property-read string $phone_number
 * @property-read string $notes
 * @property-read string $latitude
 * @property-read string $longitude
 * @property-read int $is_active
 * @property-read int $tenant_id
 */
class Location extends Model
{
	use MultiTenant;

}