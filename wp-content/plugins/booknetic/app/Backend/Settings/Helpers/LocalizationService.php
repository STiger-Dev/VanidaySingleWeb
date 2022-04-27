<?php

namespace BookneticApp\Backend\Settings\Helpers;

use BookneticApp\Providers\Core\Backend;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;
use Gettext\Translations;

class LocalizationService
{

	public static function getPoFile( $language, $is_save_action = false )
	{
		return self::languagesPath( 'booknetic-' . $language . '.po', $is_save_action );
	}

	public static function getMoFile( $language, $is_save_action = false )
	{
		return self::languagesPath( 'booknetic-' . $language . '.mo', $is_save_action );
	}

	public static function getPotFile( )
	{
		return Backend::MODULES_DIR . '/../../languages/booknetic.pot';
	}

	public static function saveFiles( $language, $array )
	{
		if( file_exists( self::getPoFile( $language ) ) )
		{
			$translations = Translations::fromPoFile( self::getPoFile( $language ) );
		}
		else
		{
			$translations = Translations::fromPoFile( self::getPotFile() );
		}

		foreach ( $array AS $msgId => $msgStr )
		{
			$find = $translations->find( null, $msgId );

			if( $find )
			{
				$find->setTranslation( $msgStr );
			}
		}

		$translations->toPoFile( self::getPoFile( $language, true ) );
		$translations->toMoFile( self::getMoFile( $language, true ) );

		return true;
	}

	public static function availableLanguages()
	{
		require_once ABSPATH . 'wp-admin/includes/translation-install.php';
		return wp_get_available_translations();
	}

	public static function isLngCorrect( $lng_name )
	{
		if( $lng_name == 'en_US' )
			return true;

		$available_translations = self::availableLanguages();

		return isset( $available_translations[ $lng_name ] );
	}

	public static function getLanguageName( $lng )
	{
		if( $lng == 'en_US' )
			return 'English';

		$available_translations = self::availableLanguages();

		return isset( $available_translations[ $lng ] ) ? $available_translations[ $lng ]['native_name'] : $lng;
	}

	public static function languagesPath( $lang_name, $is_save_action )
	{
		$path = Backend::MODULES_DIR . '/../../languages/';

		if( Helper::isSaaSVersion() )
		{
			if( ( $is_save_action || file_exists( $path . Permission::tenantId() . '/' . $lang_name ) ) && Permission::tenantId() > 0 )
			{
				$path .= Permission::tenantId() . '/';
			}
		}

		if( ! file_exists( $path ) )
		{
			mkdir( $path, 0777 );
		}

		return $path . $lang_name;
	}

	public static function changeLanguageIfNeed()
	{
		if( !Helper::isSaaSVersion() )
			return;

		$defaultLng = Helper::getOption('default_language', '');

		if( $defaultLng == '' || !self::isLngCorrect( $defaultLng ) )
			return;

		global $l10n;
		if(isset($l10n['booknetic']))
		{
			unset($l10n['booknetic']);
		}

		load_textdomain( 'booknetic', self::getMoFile( $defaultLng ) );
	}

	public static function setLanguage( $language )
	{
		if( empty( $language ) || !self::isLngCorrect( $language ) )
			return;

		global $l10n;
		if(isset($l10n['booknetic']))
		{
			unset($l10n['booknetic']);
		}

		load_textdomain( 'booknetic', self::getMoFile( $language ) );
	}

}
