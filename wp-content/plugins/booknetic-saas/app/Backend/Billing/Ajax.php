<?php

namespace BookneticSaaS\Backend\Billing;


use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Core\Route;
use BookneticApp\Providers\Helpers\Math;
use BookneticSaaS\Integrations\PaymentGateways\WooCoommerce;
use BookneticSaaS\Models\Plan;
use BookneticSaaS\Models\Tenant;
use BookneticSaaS\Models\TenantBilling;
use BookneticSaaS\Providers\Helpers\Date;
use BookneticApp\Providers\DB\DB;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;
use BookneticSaaS\Integrations\PaymentGateways\Paypal;
use BookneticSaaS\Integrations\PaymentGateways\Stripe;

class Ajax extends \BookneticApp\Providers\Core\Controller
{

	public function create_invoice()
	{
		$plan_id        = Helper::_post('plan_id', '0', 'int');
		$payment_cycle  = Helper::_post('payment_cycle', '', 'string', ['monthly', 'annually']);
		$payment_method = Helper::_post('payment_method', '', 'string', ['credit_card', 'paypal', 'balance']);

		if( !( $plan_id > 0 && !empty( $payment_cycle ) && !empty( $payment_method ) ) )
		{
			return $this->response( false );
		}

		$planInf = Plan::where('id', $plan_id)->where('is_active', 1)->fetch();
		if( !$planInf )
		{
			return $this->response( false );
		}

		$amount                 = $payment_cycle == 'monthly' ? $planInf->monthly_price : $planInf->annually_price;
		$discount               = $payment_cycle == 'monthly' ? $planInf->monthly_price_discount : $planInf->annually_price_discount;
		$first_month_amount     = $discount > 0 && $discount <= 100 ? Helper::floor( ($amount * (100 - $discount) / 100), \BookneticSaaS\Providers\Helpers\Helper::getOption('price_number_of_decimals', '2') ) : $amount;

		if( $payment_method == 'balance' )
		{
			$currentBalance = Helper::floor( Permission::tenantInf()->money_balance );

			if( $currentBalance < $first_month_amount )
			{
				Helper::response( false, bkntcsaas__('You don\'t have enough balance!') );
			}
		}

		TenantBilling::insert([
			'event_type'        =>  'subscribed',
			'amount'            =>  $first_month_amount,
			'amount_per_cycle'  =>  $amount,
			'status'            =>  'canceled',
			'created_at'        =>  Date::dateTimeSQL(),
			'plan_id'           =>  $plan_id,
			'payment_method'    =>  $payment_method,
			'payment_cycle'     =>  $payment_cycle
		]);

		$billingId = DB::lastInsertedId();

		if( $payment_method == 'paypal' )
		{
			$checkout = new Paypal();

			$checkout->setAmount( $amount, $first_month_amount, Helper::getOption('currency', 'USD', false ) );
			$checkout->setId( $billingId );
			$checkout->setCycle( $payment_cycle );
			$checkout->setItem( $plan_id, $planInf->name, $planInf->description );
			$checkout->setSuccessURL( site_url() . '/?booknetic_saas_action=paypal_confirm&status=succes&billing_id=' . $billingId );
			$checkout->setCancelURL( site_url() . '/?booknetic_saas_action=paypal_confirm&status=cancel&billing_id=' . $billingId );

			$checkoutResult = $checkout->createRecurringPayment();

			if( $checkoutResult['status'] )
			{
				return $this->response( true, [ 'url' => $checkoutResult['url'] ] );
			}
			else
			{
				TenantBilling::where('id', $billingId)->delete();
				return $this->response( false, $checkoutResult['error'] );
			}
		}
		else if( $payment_method == 'credit_card' )
		{
			$checkout = new Stripe();

			$checkout->setAmount( $amount, $first_month_amount, Helper::getOption('currency', 'USD', false ) );
			$checkout->setId( $billingId );
			$checkout->setCycle( $payment_cycle );
			$checkout->setPlan( $planInf );
			$checkout->setEmail( Permission::tenantInf()->email );
			$checkout->setSuccessURL( site_url() . '/?booknetic_saas_action=stripe_confirm&status=succes&bkntc_session_id={CHECKOUT_SESSION_ID}' );
			$checkout->setCancelURL( site_url() . '/?booknetic_saas_action=stripe_confirm&status=cancel&bkntc_session_id={CHECKOUT_SESSION_ID}' );

			$checkoutResult = $checkout->createRecurringPayment();

			if( $checkoutResult['status'] )
			{
				return $this->response( true, [ 'id' => $checkoutResult['id'] ] );
			}
			else
			{
				TenantBilling::where('id', $billingId)->delete();
				return $this->response( false, $checkoutResult['error'] );
			}
		}
		else if( $payment_method == 'balance' )
		{
			Tenant::where('id', Permission::tenantId())->update([
				'money_balance' =>  Helper::floor( Permission::tenantInf()->money_balance - $first_month_amount )
			]);

			Tenant::billingStatusUpdate( $billingId, 'balance_' . uniqid() );

			return $this->response( true, [ 'url' => Route::getURL('billing') . '&payment_status=success' ] );
		}
	}

	public function cancel_subscription()
	{
		$tenantInf      = Permission::tenantInf();
		$subscriptionId = $tenantInf->active_subscription;
		$billingInf     = TenantBilling::where( 'agreement_id', $subscriptionId )->fetch();

		if( !$billingInf )
		{
			return $this->response( false );
		}

		$paymentMethod = $billingInf->payment_method;

		if( $paymentMethod == 'paypal' )
		{
			$agreementId = $billingInf->agreement_id;

			$paypal = new Paypal();
			$cancelResult = $paypal->cancelSubscription( $agreementId );

			if( $cancelResult['status'] )
			{
				Tenant::unsubscribed( $agreementId );
				return $this->response( true );
			}
			else
			{
				return $this->response( false, $cancelResult['error'] );
			}
		}
		else if( $paymentMethod == 'credit_card' )
		{
			$agreementId = $billingInf->agreement_id;

			$stripe = new Stripe();
			$cancelResult = $stripe->cancelSubscription( $agreementId );

			if( $cancelResult['status'] )
			{
				Tenant::unsubscribed( $agreementId );
				return $this->response( true );
			}
			else
			{
				return $this->response( false, $cancelResult['error'] );
			}
		}

	}

	public function share_page()
	{
		return $this->modalView('share_page');
	}

	public function add_deposit()
	{
		$plans = Plan::orderBy('order_by')->where('is_active', 1)->fetchAll();

		return $this->modalView('add_deposit', [
			'plans' =>  $plans
		]);
	}

	public function add_deposit_save()
	{
		$deposit = Helper::_post('deposit', 0, 'float');

		if( $deposit <= 0 )
		{
			Helper::response( false, bkntcsaas__('Please enter a valid deposit!') );
		}
		$deposit = Helper::floor( $deposit );

		TenantBilling::insert([
			'event_type'        =>  'deposit_added',
			'amount'            =>  $deposit,
			'status'            =>  'canceled',
			'created_at'        =>  Date::dateTimeSQL(),
			'payment_method'    =>  'woocommerce'
		]);

		$billingId = TenantBilling::lastId();

		WooCoommerce::addToCart( $billingId );

		return $this->response( true, [ 'redirect_url' => WooCoommerce::redirect() ] );
	}

    public function get_current_plan()
    {
        $tenantInf          = Permission::tenantInf();
        $currentPlanInf     = Plan::get( $tenantInf->plan_id );
        $limits = json_decode($currentPlanInf->permissions,true)['limits'];

        $usageLimitsArr = [
            'location'  => ['title'=>'Location'  , 'current_usage' => Location::count() , 'max_usage'=>$limits['locations_allowed_max_number']  ],
            'service'   => ['title'=>'Service'   , 'current_usage' => Service::count()  , 'max_usage'=>$limits['services_allowed_max_number']   ],
            'staff'     => ['title'=>'Staff'     , 'current_usage' => Staff::count()    , 'max_usage'=>$limits['staff_allowed_max_number']      ],
        ];

        $usageLimitsArr = apply_filters('bkntc_tenant_limits' , $usageLimitsArr, $currentPlanInf );

        return $this->modalView('current_plan' , ['limits' => $usageLimitsArr ,'plan' =>$currentPlanInf ]);
    }

}
