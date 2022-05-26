<?php

namespace BookneticSaaS\Backend\Dashboard;

use BookneticSaaS\Models\TenantBilling;
use BookneticSaaS\Providers\Helpers\Date;
use BookneticSaaS\Providers\Helpers\Helper;

class Controller extends \BookneticApp\Providers\Core\Controller
{

	public function index()
	{
		$thisMonthEarning = TenantBilling::noTenant()->where('status', 'paid')->where('created_at', '>=', Date::format('Y-m-01 00:00'))->where('created_at', '<=', Date::format('Y-m-t 23:59:59'))->sum('amount');
		$lastMonthEarning = TenantBilling::noTenant()->where('status', 'paid')->where('created_at', '>=', Date::format('Y-m-01 00:00', '-1 month'))->where('created_at', '<=', Date::format('Y-m-t 23:59:59', '-1 month'))->sum('amount');

		$this->view( 'index', [
			'this_month_earning'    =>  Helper::price( $thisMonthEarning ),
			'last_month_earning'    =>  Helper::price( $lastMonthEarning )
		] );
	}

}
