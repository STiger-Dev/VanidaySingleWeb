<?php

namespace BookneticSaaS\Models;

use BookneticApp\Providers\DB\MultiTenant;
use BookneticSaaS\Models\Plan;
use BookneticApp\Providers\DB\Model;
use BookneticSaaS\Models\Tenant;

class TenantBilling extends Model
{
	use MultiTenant;

	protected static $tableName = 'tenant_billing';

	public static $relations = [
		'tenant'    => [ Tenant::class, 'id', 'tenant_id' ],
		'plan'      => [ Plan::class, 'id', 'plan_id' ]
	];

}
