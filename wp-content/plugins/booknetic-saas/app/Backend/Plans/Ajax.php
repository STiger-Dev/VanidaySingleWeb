<?php

namespace BookneticSaaS\Backend\Plans;

use BookneticApp\Providers\Core\Capabilities;
use BookneticSaaS\Models\Plan;
use BookneticSaaS\Integrations\PaymentGateways\Stripe;
use BookneticApp\Providers\DB\DB;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Providers\UI\TabUI;

class Ajax extends \BookneticApp\Providers\Core\Controller
{

	public function add_new()
	{
		$planId = Helper::_post('id', '0', 'integer');

		/*$permissions = [
			'receiving_appointments'	    => ['value' => 'on', 'title' => bkntcsaas__('Receiving appointments')],
			'upload_logo_to_booking_panel'	=> ['value' => 'on', 'title' => bkntcsaas__('Can upload a logo to the Booking panel')]
		];*/

		if( $planId > 0 )
		{
			$planInf = Plan::get( $planId );

			if( !$planInf )
			{
				return $this->response(false, bkntcsaas__('Plan not found!'));
			}

			$permissionsArr = json_decode( $planInf->permissions, true );
			$currentPlanCapabilities = isset( $permissionsArr['capabilities'] ) ? $permissionsArr['capabilities'] : [];
			$currentPlanLimits = isset( $permissionsArr['limits'] ) ? $permissionsArr['limits'] : [];
		}
		else
		{
			$planInf = [
				'id'				        =>  null,
				'name'				        =>  null,
				'color'				        =>  null,
				'description'		        =>  null,
				'monthly_price'		        =>  null,
				'annually_price'	        =>  null,
				'order_by'			        =>  null,
				'is_active'			        =>  null,
				'permissions'		        =>	null,
				'ribbon_text'               =>  null,
				'monthly_price_discount'    =>  null,
				'annually_price_discount'   =>  null,
				'stripe_product_data'       =>  null
			];

			$currentPlanCapabilities = [];
			$currentPlanLimits = [];
		}

        TabUI::get( 'plans_add' )
            ->item( 'details' )
            ->setTitle( bkntcsaas__( 'DETAILS' ) )
            ->addView( __DIR__ . '/view/tab/details.php' )
            ->setPriority( 1 );

        TabUI::get( 'plans_add' )
            ->item( 'capabilities' )
            ->setTitle( bkntcsaas__( 'PERMISSIONS' ) )
            ->addView( __DIR__ . '/view/tab/capabilities.php' )
            ->setPriority( 2 );

        TabUI::get( 'plans_add' )
            ->item( 'limits' )
            ->setTitle( bkntcsaas__( 'LIMITS' ) )
            ->addView( __DIR__ . '/view/tab/limits.php' )
            ->setPriority( 3 );

        return $this->modalView('add_new', [
			'id'		                =>	$planId,
			'plan'  	                =>	$planInf,
			'current_plan_capabilities' =>  $currentPlanCapabilities,
			'current_plan_limits'       =>  $currentPlanLimits,
			'capabilities_list'         =>  Capabilities::getTenantCapabilitiesList(),
			'limits_list'               =>  Capabilities::getLimitsList()
		]);
	}

	public function save_plan()
	{
		$id						        = Helper::_post('id', '0', 'integer');
		$name					        = Helper::_post('name', '', 'string');
		$ribbon_text			        = Helper::_post('ribbon_text', '', 'string');
		$color					        = Helper::_post('color', '', 'string');
		$order_by				        = Helper::_post('order_by', '', 'int');
		$is_active				        = Helper::_post('is_active', 'on', 'string', ['on', 'off']);
		$description			        = Helper::_post('description', '', 'string');
		$monthly_price			        = Helper::_post('monthly_price', '', 'float');
		$monthly_price_discount         = Helper::_post('monthly_price_discount', '', 'int');
		$annually_price			        = Helper::_post('annually_price', '', 'float');
		$annually_price_discount        = Helper::_post('annually_price_discount', '', 'int');
		$capabilities                   = Helper::_post('capabilities', [1], 'json');
		$limits                         = Helper::_post('limits', [], 'json');

		if( empty($name) || empty($color) )
		{
			return $this->response(false, bkntcsaas__('Please fill in all required fields correctly!'));
		}

		if( $id > 0 )
		{
			$getOldInf = Plan::get( $id );

			if( !$getOldInf )
			{
				return $this->response(false, bkntcsaas__('Plan not found or permission denied!'));
			}

			if( !empty( $getOldInf->stripe_product_data ) )
			{
				$monthly_price = $getOldInf->monthly_price;
				$monthly_price_discount = $getOldInf->monthly_price_discount;
				$annually_price = $getOldInf->annually_price;
				$annually_price_discount = $getOldInf->annually_price_discount;

				if( $name != $getOldInf->name )
				{
					$stripe_product_data = json_decode( $getOldInf->stripe_product_data, true );

					$stripe = new Stripe();
					$stripe->updateProductName( $stripe_product_data['id'], $name );
				}
			}
		}

		$sqlData = [
			'name'			            =>	$name,
			'ribbon_text'			    =>	$ribbon_text,
			'color'	                    =>	$color,
			'order_by'		            =>	$order_by,
			'is_active'	                =>	$is_active == 'on' ? 1 : 0,
			'description'	            =>	$description,
			'monthly_price'	            =>	$monthly_price,
			'monthly_price_discount'	=>	$monthly_price_discount,
			'annually_price'	        =>	$annually_price,
			'annually_price_discount'	=>	$annually_price_discount,
			'permissions'		        =>	json_encode( [
				'capabilities'   => $capabilities,
				'limits'         => $limits
			] )
		];

		if( $id > 0 )
		{
			Plan::where('id', $id)->update( $sqlData );
		}
		else
		{
			Plan::insert( $sqlData );
		}

		return $this->response(true );
	}

	public function set_as_default()
	{
		$plan_id = Helper::_post('id', '', 'int');

		if( !( $plan_id > 0 ) )
		{
			return $this->response( false );
		}

		$planInf = Plan::get( $plan_id );
		if( !$planInf )
		{
			return $this->response( false );
		}

		Plan::where('is_default', 1)->update(['is_default' => 0]);
		Plan::where('id', $plan_id)->update(['is_default' => 1]);

		return $this->response( true );
	}

}
