<?php

namespace BookneticApp\Backend\Boostore;

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Backend\Boostore\Helpers\BoostoreHelper;

class Controller extends \BookneticApp\Providers\Core\Controller
{
    public function index ()
    {
        $this->view(  'index', [
            'categories' => BoostoreHelper::get( 'categories', [], [] ),
        ] );
    }

    public function details ()
    {
        $addonSlug = Helper::_get( 'slug', '', 'string' );

        $addon = BoostoreHelper::get( 'addons/' . $addonSlug, [], [] );

        $addon[ 'is_installed' ] = ! empty( BoostoreHelper::getAddonSlug( $addon[ 'slug' ] ) ) && file_exists( realpath( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . BoostoreHelper::getAddonSlug( $addon[ 'slug' ] ) ) );

        $this->view(  'details', [
            'addon' => $addon,
        ] );
    }

    public function purchased ()
    {
        $this->view( 'purchased', [], false );
    }

    public function my_purchases ()
    {
        $myPurchases = BoostoreHelper::get( 'my_purchases', [], [
            'items' => [],
        ] );

        foreach ( $myPurchases[ 'items' ] as $i => $addon )
        {
            $myPurchases[ 'items' ][ $i ][ 'is_installed' ] = ! empty( BoostoreHelper::getAddonSlug( $addon[ 'slug' ] ) ) && file_exists( realpath( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . BoostoreHelper::getAddonSlug( $addon[ 'slug' ] ) ) );
        }

        $this->view( 'my_purchases', [
            'items' => $myPurchases[ 'items' ],
            'is_migration' => ! empty( Helper::getOption( 'migration_v3', false, false ) )
        ] );
    }
}
