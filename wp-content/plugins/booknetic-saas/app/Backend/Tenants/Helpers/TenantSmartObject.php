<?php

namespace BookneticSaaS\Backend\Tenants\Helpers;

use BookneticSaaS\Models\Plan;
use BookneticSaaS\Models\Tenant;
use BookneticSaaS\Models\TenantBilling;

class TenantSmartObject
{

	private static $tenantDataById = [];

	private $tenantID;

	/**
	 * @var Tenant
	 */
	private $tenantInfo;

	/**
	 * @var Plan
	 */
	private $planInfo;

	/**
	 * @var TenantBilling
	 */
	private $billingInfo;

	public function __construct ( $tenantID )
	{
		$this->tenantID = $tenantID;
	}

	public static function load ( $tenantID )
	{
		self::$tenantDataById[ $tenantID ] = new TenantSmartObject( $tenantID );

		return self::$tenantDataById[ $tenantID ];
	}

	public function getInfo ()
	{
		if( is_null( $this->tenantInfo ) )
		{
			$this->tenantInfo = Tenant::get( $this->getId() );
		}

		return $this->tenantInfo;
	}

	public function getId ()
	{
		return $this->tenantID;
	}

	public function getPlanInfo ()
	{
		if( is_null( $this->planInfo ) )
		{
			$this->planInfo = $this->getInfo() ? $this->getInfo()->plan()->fetch() : false;
		}

		return $this->planInfo;
	}

	public function getBillingInfo ()
	{
		if( is_null( $this->billingInfo ) )
		{
			$this->billingInfo = $this->getInfo() ? $this->getInfo()->billing()->fetch() : false;
		}

		return $this->billingInfo;
	}

}