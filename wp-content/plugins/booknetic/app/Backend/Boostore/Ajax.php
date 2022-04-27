<?php

namespace BookneticApp\Backend\Boostore;

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Backend\Boostore\Helpers\BoostoreHelper;

class Ajax extends \BookneticApp\Providers\Core\Controller
{
    public function get_addons ()
    {
        $reqBody = [
            'category_ids' => Helper::_post( 'category_ids', null, 'string' ),
            'search'       => Helper::_post( 'search', null, 'string' ),
            'order_by'     => Helper::_post( 'order_by', null, 'string' ),
            'order_type'   => Helper::_post( 'order_type', null, 'string' ),
            'page'         => Helper::_post( 'page', null, 'int' ),
        ];

        $data = BoostoreHelper::get( 'addons', $reqBody, [
            'items' => [],
        ] );

        foreach ( $data[ 'items' ] as $i => $addon )
        {
            $data[ 'items' ][ $i ][ 'is_installed' ] = ! empty( BoostoreHelper::getAddonSlug( $addon[ 'slug' ] ) ) && file_exists( realpath( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . BoostoreHelper::getAddonSlug( $addon[ 'slug' ] ) ) );
        }

        return $this->modalView( 'addons', [
            'data'      => $data,
            'is_search' => $reqBody[ 'search' ],
        ] );
    }

    public function purchase ()
    {
        $addonSlug = Helper::_post( 'addon_slug', null, 'string' );

        if ( Permission::isDemoVersion() )
        {
            return $this->response( false, 'You can\'t purchase add-on on Demo version!' );
        }

        if ( empty( $addonSlug ) )
        {
            return $this->response( false, bkntc__( 'An error occurred, please try again later' ) );
        }

        $data = BoostoreHelper::get( 'generate_purchase_url/' . $addonSlug, [
            'domain'       => site_url(),
            'redirect_url' => admin_url( 'admin.php?page=' . Helper::getBackendSlug() . '&module=boostore&action=purchased' ),
        ] );

        if ( ! empty( $data[ 'purchase_url' ] ) )
        {
            return $this->response( true, [ 'purchase_url' => $data[ 'purchase_url' ] ] );
        }
        else if ( ! empty( $data[ 'error_message' ] ) )
        {
            return $this->response( false, htmlspecialchars( $data[ 'error_message' ] ) );
        }

        return $this->response( false, bkntc__( 'An error occurred, please try again later!' ) );
    }

    public function install ()
    {
        $addonSlug = Helper::_post( 'addon_slug', null, 'string' );

        if ( Permission::isDemoVersion() )
        {
            return $this->response( false, 'You can\'t install add-on on Demo version!' );
        }

        if ( empty( $addonSlug ) )
        {
            return $this->response( false, bkntc__( 'An error occurred, please try again later' ) );
        }

        $data = BoostoreHelper::get( 'generate_download_url/' . $addonSlug, [
            'domain' => site_url(),
        ] );

        if ( ! empty( $data[ 'download_url' ] ) && BoostoreHelper::installAddon( $addonSlug, $data[ 'download_url' ] ) )
        {
            return $this->response( true, [ 'message' => bkntc__( 'Installed successfully!' ) ] );
        }
        else if ( ! empty( $data[ 'error_message' ] ) )
        {
            return $this->response( false, htmlspecialchars( $data[ 'error_message' ] ) );
        }

        return $this->response( false, bkntc__( 'An error occurred, please try again later!' ) );
    }

    public function install_finished ()
    {
        if ( Permission::isDemoVersion() )
        {
            return $this->response( false );
        }

        Helper::deleteOption( 'migration_v3', false );

        return $this->response( true );
    }

    public function uninstall ()
    {
        $addon = Helper::_post( 'addon', false, 'string' );

        if ( Permission::isDemoVersion() )
        {
            return $this->response( false, 'You can\'t uninstall add-on on Demo version!' );
        }

        if ( empty( $addon ) )
        {
            return $this->response( false, bkntc__( 'Addon not found!' ) );
        }

        if ( BoostoreHelper::uninstallAddon( $addon ) )
        {
            return $this->response( true, [ 'message' => bkntc__( 'Addon uninstalled successfully!' ) ] );
        }

        return $this->response( false, bkntc__( 'Addon couldn\'t be uninstalled!' ) );
    }
}
