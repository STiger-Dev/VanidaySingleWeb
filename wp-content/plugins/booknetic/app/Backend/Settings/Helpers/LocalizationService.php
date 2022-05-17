<?php

namespace BookneticApp\Backend\Settings\Helpers;

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;
use Gettext\Translation;
use Gettext\Translations;

class LocalizationService
{

    public static function getPoFile( $language, $is_save_action = false , $slug = 'booknetic' )
    {
        return self::languagesPath( $slug . '-' . $language . '.po', $is_save_action , $slug );
    }

    public static function getMoFile( $language, $is_save_action = false , $slug = 'booknetic' )
    {
        return self::languagesPath( $slug . '-' . $language . '.mo', $is_save_action , $slug);
    }

    public static function getPotFile( $slug )
    {
        return WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . $slug . '.pot';
    }

    public static function saveFiles( $language, $array , $slug = 'booknetic' )
    {
        if( file_exists( self::getPoFile( $language ,false , $slug ) ) )
        {
            $translations = Translations::fromPoFile( self::getPoFile( $language,false , $slug ) );
        }
        else
        {
            $translations = Translations::fromPoFile( self::getPotFile( $slug ) );
        }

        foreach ( $array AS $msgId => $msgStr )
        {
            $find = $translations->find( null, $msgId );

            if( $find )
            {
                $find->setTranslation( $msgStr );
            }else{
                $translation = Translation::create(null, $msgId);
                $translation->setTranslation( $msgStr );
                $translations->offsetSet( $translation->getId() , $translation);
            }
        }

        $translations->toPoFile( self::getPoFile( $language, true , $slug ) );
        $translations->toMoFile( self::getMoFile( $language, true , $slug ) );

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

    public static function languagesPath( $lang_name, $is_save_action , $slug )
    {
        $path = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR;

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