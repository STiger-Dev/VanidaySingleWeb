<?php

namespace BookneticSaaS\Backend\Payments;

use BookneticSaaS\Models\Tenant;
use BookneticSaaS\Models\TenantBilling;
use BookneticSaaS\Providers\Helpers\Date;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Providers\UI\TabUI;

class Ajax extends \BookneticApp\Providers\Core\Controller
{

	public function add_new()
	{
		$billingId = Helper::_post('id', '0', 'integer');

		if( $billingId > 0 )
		{
			$billingInfo = TenantBilling::noTenant()->get( $billingId );
			if( !$billingInfo )
			{
				return $this->response(false, bkntcsaas__('Tenant not found!'));
			}

			$tenantInf = $billingInfo->tenant()->fetch();
		}
		else
		{
			$tenant_id = Helper::_post('tenant_id', '', 'int');
			if( $tenant_id > 0 )
			{
				$tenantInf = Tenant::get( $tenant_id );
			}

			$billingInfo = [
				'id'                =>  null,
				'tenant_id'         =>  $tenant_id > 0 ? $tenant_id : null,
				'amount'            =>  null,
				'created_at'        =>  null,
				'plan_id'           =>  null,
				'payment_method'    =>  null,
				'payment_cycle'     =>  null
			];
		}

        TabUI::get( 'payments_add' )
            ->item( 'details' )
            ->setTitle( bkntcsaas__( 'DETAILS' ) )
            ->addView( __DIR__ . '/view/tab/details.php' )
            ->setPriority( 1 );

        return $this->modalView('add_new', [
			'id'		=>	$billingId,
			'billing'	=>	$billingInfo,
			'tenant'    =>  isset( $tenantInf ) ? htmlspecialchars($tenantInf->full_name) . ' /' . htmlspecialchars($tenantInf->domain) : ''
		]);
	}

	public function save_payment()
	{
		$id						    = Helper::_post('id', '0', 'integer');
		$tenant_id				    = Helper::_post('tenant_id', '0', 'integer');
		$plan_id				    = Helper::_post('plan_id', '0', 'integer');
		$amount		                = Helper::_post('amount', '', 'float');
		$payment_method				= Helper::_post('payment_method', '', 'string', ['offline', 'paypal', 'credit_card']);
		$payment_cycle				= Helper::_post('payment_cycle', '', 'string', ['monthly', 'annually']);
		$created_at					= Helper::_post('created_at', '', 'string');

		if( empty( $tenant_id ) || empty( $plan_id ) || !( $amount >= 0 ) || empty( $payment_method ) || empty( $payment_cycle ) || !Date::isValid( $created_at ) )
		{
			return $this->response(false, bkntcsaas__('Please fill in all required fields correctly!'));
		}

		$isEdit = $id > 0;

		if( $isEdit )
		{
			$getOldInf = TenantBilling::noTenant()->get( $id );

			if( !$getOldInf )
			{
				return $this->response(false, bkntcsaas__('Payment not found or permission denied!'));
			}
		}

		$sqlData = [
			'tenant_id'		    =>	$tenant_id,
			'plan_id'		    =>	$plan_id,
			'amount'		    =>	$amount,
			'payment_method'	=>	$payment_method,
			'payment_cycle'		=>	$payment_cycle,
			'created_at'	    =>	$created_at
		];

		if( $id > 0 )
		{
			TenantBilling::noTenant()->where( 'id', $id )->update( $sqlData );
		}
		else
		{
			$sqlData['event_type'] = 'payment_received';
			$sqlData['status'] = 'paid';

			TenantBilling::noTenant()->insert( $sqlData );
		}

		return $this->response(true );
	}

	public function get_tenants()
	{
		$search     = Helper::_post('q', '', 'string');
		$tenants    = Tenant::where('concat(`full_name`,\' /\', `domain`)', 'LIKE', '%' . $search . '%')->limit( 50 )->fetchAll();
		$data       = [];

		foreach ( $tenants AS $tenant )
		{
			$lastBillingInfo = TenantBilling::noTenant()->where('tenant_id', $tenant['id'])->orderBy('id DESC')->fetch();

			$data[] = [
				'id'	        => (int)$tenant['id'],
				'text'	        => sprintf('%s ( /%s )', htmlspecialchars($tenant['full_name']), htmlspecialchars($tenant['domain']) ),
				'plan_id'       => (int)$tenant['plan_id'],
				'last_amount'   => Helper::floor( $lastBillingInfo ? $lastBillingInfo->amount : 0 ),
				'last_cycle'    => $lastBillingInfo ? $lastBillingInfo->payment_cycle : 'monthly'
			];
		}

		return $this->response(true, [ 'results' => $data ]);
	}

}
