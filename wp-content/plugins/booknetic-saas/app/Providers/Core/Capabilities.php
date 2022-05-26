<?php

namespace BookneticSaaS\Providers\Core;

use BookneticSaaS\Providers\Helpers\Helper;

class Capabilities
{
    protected static $prefix = 'bkntcsaas_';

	protected static $userCapabilities = [];

	public static function getList()
	{
		$capabilitiesArr = self::$userCapabilities;

		foreach ( $capabilitiesArr as $key => $capability )
		{
			if( is_string( $capability['parent'] ) && ! empty( $capability['parent'] ) && array_key_exists( $capability['parent'], $capabilitiesArr ) )
			{
				if( empty( $capabilitiesArr[ $capability['parent'] ]['children'] ) )
				{
					$capabilitiesArr[ $capability['parent'] ]['children'] = [];
				}

				$capabilitiesArr[ $capability['parent'] ]['children'][ $key ] = $capability;

				unset( $capabilitiesArr[ $key ] );
			}
		}

		return $capabilitiesArr;
	}
}