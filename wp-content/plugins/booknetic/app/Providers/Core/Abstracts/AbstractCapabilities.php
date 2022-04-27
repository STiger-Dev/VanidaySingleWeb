<?php

namespace BookneticApp\Providers\Core\Abstracts;

use BookneticApp\Providers\Core\CapabilitiesException;

abstract class AbstractCapabilities
{
    public static function register( $capability, $title, $parent = false )
    {
        static::$userCapabilities[ $capability ] = [
            'title'     =>  $title,
            'parent'    =>  $parent
        ];
    }

    public static function get( $capability )
    {
        return isset( static::$userCapabilities[ $capability ] ) ? static::$userCapabilities[ $capability ] : false;
    }

    public static function must( $capability )
    {
        if( ! static::userCan( $capability ) )
        {
            throw new CapabilitiesException( bkntc__('Permission denied!') );
        }
    }

    public static function userCan( $capability )
    {
        $capabilityInf = static::get( $capability );

        if( $capabilityInf === false )
        {
            throw new \Exception( bkntc__('Capability %s not found', [ $capability ]) );
        }

        if( ! empty( $capabilityInf['parent'] ) && ! static::userCan( $capabilityInf['parent'] ) )
        {
            return false;
        }

        return apply_filters( static::$prefix.'user_capability_filter', true, $capability );
    }
}