<?php

namespace BookneticSaaS\Models;

use BookneticApp\Providers\DB\Model;

class TenantFormInput extends Model
{

	public static $relations = [
		'choices'    =>  [ TenantFormInputChoice::class ]
	];

}