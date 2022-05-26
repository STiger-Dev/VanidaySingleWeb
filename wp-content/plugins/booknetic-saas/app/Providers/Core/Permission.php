<?php

namespace BookneticSaaS\Providers\Core;

use BookneticSaaS\Providers\Helpers\Helper;

class Permission
{

	private static $current_user_info;
	private static $is_back_end = false;


	public static function setAsBackEnd()
	{
		self::$is_back_end = true;

		$current_page = Helper::_get('page', '', 'string');

		if( in_array( 'booknetic_saas_tenant', self::userInfo()->roles ) && $current_page != \BookneticApp\Providers\Helpers\Helper::getSlugName() )
		{
			Helper::redirect( admin_url( 'admin.php?page=' . \BookneticApp\Providers\Helpers\Helper::getSlugName()) );
		}

		if( self::isAdministrator() && $current_page == \BookneticApp\Providers\Helpers\Helper::getSlugName() )
		{
			Helper::redirect( admin_url('admin.php?page=booknetic-saas') );
		}
	}

	public static function canUseBooknetic()
	{
		if( self::isAdministrator() )
			return true;

		return false;
	}

	public static function userInfo()
	{
		if( is_null( self::$current_user_info ) )
		{
			self::$current_user_info = wp_get_current_user();
		}

		return self::$current_user_info;
	}

	public static function userId()
	{
		return get_current_user_id();
	}

	public static function isAdministrator()
	{
		return (self::isDemoVersion() && !in_array( 'booknetic_saas_tenant', self::userInfo()->roles )) || in_array( 'administrator', self::userInfo()->roles );
	}

	public static function modelFilter( $table, &$where )
	{
		return;
	}

	public static function queryFilter( $table, $column = 'id', $joiner = 'AND' )
	{
		return '';
	}

	public static function isDemoVersion()
	{
		return defined('FS_CODE_DEMO_VERSION');
	}

}