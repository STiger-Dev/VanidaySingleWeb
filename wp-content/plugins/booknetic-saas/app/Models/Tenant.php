<?php

namespace BookneticSaaS\Models;

use BookneticApp\Models\Appearance;
use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\ServiceCategory;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Math;
use BookneticSaaS\Models\TenantBilling;
use BookneticSaaS\Models\Plan;
use BookneticSaaS\Integrations\PaymentGateways\Stripe;
use BookneticApp\Providers\DB\DB;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticApp\Providers\DB\Model;
use BookneticSaaS\Integrations\PaymentGateways\Paypal;

/**
 * @property-read int $id
 * @property int $user_id
 * @property string $email
 * @property string $full_name
 * @property string $domain
 * @property int $plan_id
 * @property string $expires_in
 * @property string $inserted_at
 * @property string $activation_token
 * @property string $activation_last_sent_time
 * @property string $active_subscription
 * @property string $logs
 * @property float $money_balance
 */
class Tenant extends Model
{

	public static $relations = [
		'billing'   => [ TenantBilling::class ],
		'plan'      => [ Plan::class, 'id', 'plan_id' ]
	];

	public static function billingStatusUpdate( $billing_id, $subscription )
	{
		$paymentInf = TenantBilling::noTenant()->get( $billing_id );

		if( $paymentInf->status == 'paid' )
			return;

		TenantBilling::noTenant()->where( 'id', $billing_id )->update([
			'status'        =>  'paid',
			'agreement_id'  =>  $subscription
		]);

		$paymentInf->status = 'paid';
		$paymentInf->agreement_id = $subscription;

		$tenantInf = Tenant::get( $paymentInf->tenant_id );
		if( !empty( $tenantInf->active_subscription ) )
		{
			$activeBillingInfo = TenantBilling::noTenant()->where( 'agreement_id', $tenantInf->active_subscription )->fetch();
			if( $activeBillingInfo )
			{
				if( $activeBillingInfo->payment_method == 'paypal' )
				{
					$payment = new Paypal();
					$payment->cancelSubscription( $activeBillingInfo->agreement_id );
				}
				else if( $activeBillingInfo->payment_method == 'stripe' )
				{
					$payment = new Stripe();
					$payment->cancelSubscription( $activeBillingInfo->agreement_id );
				}
			}
		}

		$newExpireDate = Date::dateSQL( $paymentInf->payment_cycle == 'monthly' ? '+1 month' : '+1 year' );

		Tenant::where( 'id', $paymentInf->tenant_id )->update([
			'expires_in'            =>  $newExpireDate,
			'plan_id'               =>  $paymentInf->plan_id,
			'active_subscription'   =>  $paymentInf->agreement_id
		]);

		do_action( 'bkntcsaas_tenant_subscribed', $paymentInf->tenant_id );
	}

	public static function paymentSucceded( $subscriptionId )
	{
		$paymentInf = TenantBilling::noTenant()->where( 'agreement_id', $subscriptionId )->fetch();
		if( !$paymentInf )
		{
			return false;
		}

		// avoid dublicate insert
		$lastBillingInvoice = TenantBilling::noTenant()
			->where( 'event_type', 'payment_received' )
			->where( 'agreement_id', $subscriptionId )
			->orderBy('`created_at` DESC')
			->limit(1)
			->fetch();

		if( $lastBillingInvoice && abs( Date::epoch() - Date::epoch( $lastBillingInvoice->created_at ) ) < 3 * 24 * 60 * 60 )
		{
			return true;
		}

		TenantBilling::noTenant()->insert([
			'event_type'            =>  'payment_received',
			'tenant_id'             =>  $paymentInf->tenant_id,
			'amount'                =>  $paymentInf->amount_per_cycle,
			'amount_per_cycle'      =>  $paymentInf->amount_per_cycle,
			'status'                =>  'paid',
			'created_at'            =>  Date::dateTimeSQL(),
			'plan_id'               =>  $paymentInf->plan_id,
			'payment_method'        =>  $paymentInf->payment_method,
			'payment_cycle'         =>  $paymentInf->payment_cycle,
			'error'                 =>  '',
			'agreement_id'          =>  $subscriptionId
		]);

		$newExpireDate = Date::dateSQL( $paymentInf->payment_cycle == 'monthly' ? '+1 month' : '+1 year' );

		Tenant::where( 'id', $paymentInf->tenant_id )->update([
			'expires_in'    =>  $newExpireDate,
			'plan_id'       =>  $paymentInf->plan_id
		]);

        do_action( 'bkntcsaas_tenant_paid', $paymentInf->tenant_id );

		return true;
	}

	public static function unsubscribed( $agreementId )
	{
		$tenantInf = Tenant::where( 'active_subscription', $agreementId )->fetch();

		if( !$tenantInf )
			return false;

		Tenant::where( 'id', $tenantInf->id )->update( [ 'active_subscription' => null ] );

        do_action( 'bkntcsaas_tenant_unsubscribed', $tenantInf->id );
	}

	public static function createInitialData( $tenantId )
	{
		$tenantInf = Tenant::get( $tenantId );

		$appearances = Appearance::noTenant()->where('tenant_id', 'is', null)->fetchAll();
		foreach ( $appearances AS $appearance )
		{
			$appearance = $appearance->toArray();
			$appearance[ 'tenant_id' ] = $tenantId;
			unset( $appearance['id'] );

			Appearance::noTenant()->insert( $appearance );
        }

		$defaultCurrency = Helper::getOption('tenant_default_currency', Helper::getOption('currency' ,'USD') );
		$defaultCurrencySymbol = Helper::getOption('tenant_default_currency_symbol', Helper::getOption('currency_symbol' ,'$' ) );
		$defaultCurrencyFormat = Helper::getOption('tenant_default_currency_format', Helper::getOption('currency_format' ,'1' ) );

        Helper::setOption('currency', $defaultCurrency, $tenantId);
        Helper::setOption('currency_symbol', $defaultCurrencySymbol, $tenantId);
        Helper::setOption('currency_format', $defaultCurrencyFormat, $tenantId);

		// Create Location...
		Location::noTenant()->insert([
			'tenant_id'     =>  $tenantId,
			'name'          =>  Helper::getOption('company_name', bkntcsaas__('Location'), $tenantId),
			'address'       =>  Helper::getOption('company_address', '', $tenantId),
			'phone_number'  =>  Helper::getOption('company_phone', '', $tenantId),
			'is_active'     =>  1
		]);
		$locationId = DB::lastInsertedId();

		// Create Staff...
		Staff::noTenant()->insert([
			'tenant_id'     =>  $tenantId,
			'name'          =>  $tenantInf->full_name,
			'email'         =>  $tenantInf->email,
			'locations'     =>  $locationId,
			'is_active'     =>  1
		]);

		// Create Default Service Category
		ServiceCategory::noTenant()->insert([
			'tenant_id'     =>  $tenantId,
			'name'          =>  bkntcsaas__('Category'),
			'parent_id'     =>  0
		]);

		// Setup default Settings...
		Helper::setOption('show_step_location', 'off', $tenantId);
		Helper::setOption('show_step_staff', 'off', $tenantId);
		Helper::setOption('show_step_service_extras', 'off', $tenantId);
	}

	public static function haveEnoughBalanceToPay()
	{
		$tenantInf = Permission::tenantInf();
		if( strpos( $tenantInf->active_subscription, 'balance_' ) !== 0 )
		{
			return false;
		}

		$billingInf = TenantBilling::noTenant()->where('agreement_id', $tenantInf->active_subscription)->fetch();
		$amount = Helper::floor( $billingInf->amount_per_cycle );
		$currentBalance = Helper::floor( $tenantInf->money_balance );

		if( $amount > $currentBalance )
		{
			return false;
		}

		Tenant::where('id', Permission::tenantId())->update([
			'money_balance' => Helper::floor( $currentBalance - $amount )
		]);

		self::paymentSucceded( $tenantInf->active_subscription );

		return true;
	}

}
