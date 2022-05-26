<?php

namespace BookneticSaaS\Backend\Billing;

use BookneticApp\Providers\Helpers\Curl;
use BookneticSaaS\Models\Plan;
use BookneticSaaS\Models\TenantBilling;
use BookneticSaaS\Providers\UI\DataTableUI;
use BookneticSaaS\Providers\Helpers\Date;
use BookneticApp\Providers\Core\Permission;

class Controller extends \BookneticApp\Providers\Core\Controller
{

	public function index()
	{
		$dataTable = new DataTableUI(
			TenantBilling::leftJoin( 'plan', 'name' )
		);

		$dataTable->setTitle(bkntcsaas__('Billing'));

		$dataTable->searchBy(["created_at", 'status', 'payment_method', Plan::getField('name'), 'payment_cycle']);

		$dataTable->addColumns(bkntcsaas__('DATE'), 'created_at', ['type' => 'datetime']);
		$dataTable->addColumns(bkntcsaas__('EVENT'), function( $appointment )
		{
			if( $appointment['event_type'] == 'deposit_added' )
			{
				return bkntcsaas__('Deposit added');
			}
			else if( $appointment['event_type'] == 'payment_received' )
			{
				return bkntcsaas__('Payment received');
			}
			else if( $appointment['event_type'] == 'subscribed' )
			{
				return bkntcsaas__('Subscribed');
			}

			return htmlspecialchars( $appointment['event_type'] );
		}, [ 'order_by_field' => 'event_type' ]);
		$dataTable->addColumns(bkntcsaas__('Plan'), 'plan_name');
		$dataTable->addColumns(bkntcsaas__('AMOUNT'), 'amount', ['type' => 'price']);

		$dataTable->addColumns(bkntcsaas__('PAYMENT METHOD'), function ( $payment )
		{
			return \BookneticSaaS\Providers\Helpers\Helper::paymentMethod( $payment['payment_method'] );
		}, ['order_by_field' => 'payment_method']);

		$dataTable->addColumns(bkntcsaas__('STATUS'), function( $appointment )
		{
			if( $appointment['status'] == 'pending' )
			{
				$statusBtn = '<button type="button" class="btn btn-xs btn-light-warning">'.bkntcsaas__('Pending').'</button>';
			}
			else if( $appointment['status'] == 'paid' )
			{
				$statusBtn = '<button type="button" class="btn btn-xs btn-light-success">'.bkntcsaas__('OK').'</button>';
			}
			else
			{
				$statusBtn = '<button type="button" class="btn btn-xs btn-light-danger">'.bkntcsaas__('NOT OK').'</button>';
			}

			return $statusBtn;
		}, ['is_html' => true, 'order_by_field' => 'status']);

		$table = $dataTable->renderHTML();

		$tenantInf          = Permission::tenantInf();
		$currentPlanInf     = Plan::get( $tenantInf->plan_id );
		$currentPlanName    = $currentPlanInf ? $currentPlanInf->name : '-';
		$expiresIn          = $tenantInf->expires_in;
		$hasExpired         = empty( $expiresIn ) || Date::epoch( $expiresIn ) < Date::epoch( Date::dateSQL() );

		$this->view( 'index', [
			'table'                     =>  $table,
			'plans'                     =>  Plan::orderBy('order_by')->where('is_active', 1)->fetchAll(),
			'payment_gateways_order'    =>  explode( ',', \BookneticSaaS\Providers\Helpers\Helper::getOption('payment_gateways_order', 'stripe,paypal,woocommerce') ),
			'plan_name'                 =>  $currentPlanName,
			'has_expired'               =>  $hasExpired,
			'expires_in'                =>  $expiresIn,
			'active_subscription'       =>  $tenantInf->active_subscription,
			'money_balance'             =>  $tenantInf->money_balance
		]);
	}

	public function download_qr()
	{
		$qrData = Curl::getURL('https://chart.googleapis.com/chart?chs=540x540&cht=qr&choe=UTF-8&chl=' . urlencode( site_url() . '/' . htmlspecialchars(Permission::tenantInf()->domain) ) );

		header('Content-Disposition: Attachment;filename=QR.png');
		header("Content-type: image/png");

		echo $qrData;
	}

}
