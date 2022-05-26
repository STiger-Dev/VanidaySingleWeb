<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Models\Appointment;
use BookneticSaaS\Models\Tenant;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Providers\Helpers\Date;

?>
<link rel="stylesheet" type="text/css" href="<?php echo Helper::assets('css/dashboard.css', 'Dashboard')?>" />

<script type="application/javascript" src="<?php echo Helper::assets('js/dashboard.js', 'Dashboard')?>"></script>

<div class="m_header clearfix">
	<div class="m_head_title float-left"><?php echo bkntcsaas__('Dashboard')?></div>
</div>

<div id="statistic-boxes-area">
	<div class="row m-0">
		<div class="col-xl-3 col-lg-6 p-0 pr-lg-3 mb-4 mb-xl-0">
			<div class="statistic-boxes">
				<div class="box-icon-div"><img src="<?php echo Helper::icon('1.svg', 'Dashboard')?>"></div>
				<div class="box-number-div" data-stat="appointments"><?php echo Tenant::count()?></div>
				<div class="box-title-div"><?php echo bkntcsaas__('Tenants')?></div>
			</div>
		</div>
		<div class="col-xl-3 col-lg-6 p-0 pr-xl-3 mb-4 mb-xl-0">
			<div class="statistic-boxes">
				<div class="box-icon-div"><img src="<?php echo Helper::icon('2.svg', 'Dashboard')?>"></div>
				<div class="box-number-div" data-stat="duration"><?php echo Appointment::noTenant()->count()?></div>
				<div class="box-title-div"><?php echo bkntcsaas__('Appointments')?></div>
			</div>
		</div>
		<div class="col-xl-3 col-lg-6 p-0 pr-lg-3 mb-4 mb-lg-0">
			<div class="statistic-boxes">
				<div class="box-icon-div"><img src="<?php echo Helper::icon('3.svg', 'Dashboard')?>"></div>
				<div class="box-number-div" data-stat="revenue"><?php echo $parameters['this_month_earning'];?></div>
				<div class="box-title-div"><?php echo bkntcsaas__('Income of the month')?></div>
			</div>
		</div>
		<div class="col-xl-3 col-lg-6 p-0">
			<div class="statistic-boxes">
				<div class="box-icon-div"><img src="<?php echo Helper::icon('4.svg', 'Dashboard')?>"></div>
				<div class="box-number-div" data-stat="pending"><?php echo $parameters['last_month_earning'];?></div>
				<div class="box-title-div"><?php echo bkntcsaas__('Income of the previous month')?></div>
			</div>
		</div>
	</div>
</div>