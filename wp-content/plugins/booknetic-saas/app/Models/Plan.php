<?php

namespace BookneticSaaS\Models;

use BookneticSaaS\Models\TenantBilling;
use BookneticApp\Providers\DB\Model;

class Plan extends Model
{

	public static $relations = [
		'billing'     => [ TenantBilling::class ]
	];

}