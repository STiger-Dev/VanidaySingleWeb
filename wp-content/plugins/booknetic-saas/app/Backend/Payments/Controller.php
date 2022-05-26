<?php

namespace BookneticSaaS\Backend\Payments;

use BookneticSaaS\Models\Plan;
use BookneticSaaS\Models\Tenant;
use BookneticSaaS\Providers\UI\DataTableUI;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Models\TenantBilling;

class Controller extends \BookneticApp\Providers\Core\Controller
{

	public function index()
	{
		$tenantFilter = Helper::_get('tenant_id', '', 'int');

		$query = TenantBilling::noTenant()->leftJoin( 'plan', 'name' )->leftJoin( 'tenant', ['full_name', 'domain'] );

		if( $tenantFilter > 0 )
		{
			$query->where('tenant_id', $tenantFilter);
		}

		$dataTable = new DataTableUI( $query );

        $dataTable->addAction('edit', bkntcsaas__('Edit'));
        $dataTable->addAction('delete', bkntcsaas__('Delete'), function ($ids)
        {
            TenantBilling::noTenant()->where('id', $ids)->delete();
        }, DataTableUI::ACTION_FLAG_BULK_SINGLE);

		$dataTable->addNewBtn(bkntcsaas__('ADD PAYMENT'));

		$dataTable->setTitle(bkntcsaas__('Payments'));

		$dataTable->searchBy(["created_at", 'status', 'payment_method', Plan::getField('name'), 'payment_cycle', Tenant::getField('full_name'), Tenant::getField('domain')]);

		$dataTable->addFilter( 'status', 'select', bkntcsaas__('Status'), '=', [
			'list'	=>	[
				'paid'	    => bkntcsaas__('Paid'),
				'pending'	=> bkntcsaas__('Pending')
			]
		]);

		$dataTable->addFilter( 'payment_method', 'select', bkntcsaas__('Payment method'), '=', [
			'list'	=>	[
				'paypal'	    => bkntcsaas__('Paypal'),
				'credit_card'	=> bkntcsaas__('Credit card'),
				'offline'	    => bkntcsaas__('Offline')
			]
		]);

		$dataTable->addFilter( 'payment_cycle', 'select', bkntcsaas__('Payment cycle'), '=', [
			'list'	=>	[
				'monthly'	    => bkntcsaas__('Monthly'),
				'annually'	    => bkntcsaas__('Annually')
			]
		]);

		$dataTable->addColumns(bkntcsaas__('DATE TIME'), 'created_at', ['type' => 'datetime']);

		$dataTable->addColumns(bkntcsaas__('TENANT'), function ( $payment )
		{
			return htmlspecialchars($payment['tenant_full_name']) . '<br/><small>/'.htmlspecialchars($payment['tenant_domain']).'</small>';
		}, ['order_by_field' => 'payment_method', 'is_html' => true]);

		$dataTable->addColumns(bkntcsaas__('PLAN'), 'plan_name');
		$dataTable->addColumns(bkntcsaas__('AMOUNT'), 'amount', ['type' => 'price']);

		$dataTable->addColumns(bkntcsaas__('PAYMENT METHOD'), function ( $payment )
		{
			return Helper::paymentMethod( $payment['payment_method'] );
		}, ['order_by_field' => 'payment_method']);

		$dataTable->addColumns(bkntcsaas__('PAYMENT CYCLE'), function ( $payment )
		{
			return ( $payment['payment_cycle'] == 'monthly' ? bkntcsaas__('Monthly') : bkntcsaas__('Annually') );
		}, ['order_by_field' => 'payment_cycle']);

		$dataTable->addColumns(bkntcsaas__('STATUS'), function( $appointment )
		{
			if( $appointment['status'] == 'pending' )
			{
				$statusBtn = '<button type="button" class="btn btn-xs btn-light-warning">'.bkntcsaas__('Pending').'</button>';
			}
			else if( $appointment['status'] == 'paid' )
			{
				$statusBtn = '<button type="button" class="btn btn-xs btn-light-success">'.bkntcsaas__('Paid').'</button>';
			}
			else
			{
				$statusBtn = '<button type="button" class="btn btn-xs btn-light-danger">'.bkntcsaas__('Canceled').'</button>';
			}

			return $statusBtn;
		}, ['is_html' => true, 'order_by_field' => 'status']);

		$table = $dataTable->renderHTML();

		$this->view( 'index', [
			'table'                     =>  $table
		]);
	}

}
