<?php

namespace BookneticSaaS\Backend\Plans;

use BookneticSaaS\Providers\UI\DataTableUI;
use BookneticSaaS\Models\Plan;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Models\Tenant;

class Controller extends \BookneticApp\Providers\Core\Controller
{

	public function index()
	{
		$dataTable = new DataTableUI( new Plan() );

        $dataTable->addAction('set_as_default', bkntcsaas__('Set as trial plan') );
        $dataTable->addAction('edit', bkntcsaas__('Edit') );
        $dataTable->addAction('delete', bkntcsaas__('Delete'), [static::class , '_validate_delete'], DataTableUI::ACTION_FLAG_BULK_SINGLE );

		$dataTable->setTitle( bkntcsaas__('Plans') );

		$dataTable->addNewBtn( bkntcsaas__('ADD PLAN') );

		$dataTable->searchBy(['name', 'description', 'monthly_price', 'annually_price']);

		$dataTable->addColumns(bkntcsaas__('ID'), 'id');
		$dataTable->addColumns(bkntcsaas__('NAME'), function( $planInf )
		{
			return htmlspecialchars($planInf['name']) . ( $planInf['is_default'] ? '<i class="fa fa-star is_default" title="'.bkntcsaas__('Default plan').'"></i>' : '' );
		}, ['order_by_field' => 'name', 'is_html' => true]);
		$dataTable->addColumns(bkntcsaas__('PRICE'), function ( $plan )
		{
			return ($plan['monthly_price'] == 0 && $plan['annually_price'] == 0) ? bkntcsaas__('Free') : Helper::price( $plan['monthly_price'] ) . ' / ' . Helper::price( $plan['annually_price'] );
		}, [ 'order_by_field' => 'monthly_price' ]);
		$dataTable->addColumns(bkntcsaas__('ORDER NUMBER'), 'order_by');

		$table = $dataTable->renderHTML();

		$this->view( 'index', [
			'table' => $table
		] );
	}

	public static function _validate_delete( $ids )
	{

		foreach( $ids as $id )
		{
			$plan = Plan::get( $id );

			if( $plan->is_default )
			{
				Helper::response(false, bkntcsaas__("You can not delete plans that is assigned as a default plan"));
				return false;
			}

			if( $plan->expire_plan )
			{
				Helper::response(false, bkntcsaas__("You can not delete plans that is assigned as an expire plan"));
				return false;
			}

			$tenant_count = Tenant::where('plan_id', $id)->count();

			if( $tenant_count > 0 )
			{
				Helper::response(false, bkntcsaas__("You can not delete this plan, because it has registered tenants"));
				return false;
			}


			$all_plans_count = Plan::count();

			if( $all_plans_count < 2 )
			{
				Helper::response(false, bkntcsaas__("You can not delete this plan, because there is only one plan"));
				return false;
			}

		}

        Plan::where('id', $ids)->delete();
        return true;
	}

}
