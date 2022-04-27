(function ( $ )
{
    'use strict';

    $( document ).ready( function ()
    {
        // Handle hover over installed button
        $( document ).on( 'click', '.btn-purchase', function ()
        {
            let w = window.open( 'about:blank', 'bkntc_boostore_purchase_window', 'width=750,height=550' );

            booknetic.ajax( 'boostore.purchase', { addon_slug: $( this ).attr( 'data-addon' ) }, function ( res )
            {
                w.location.href = res[ 'purchase_url' ];
            } );
        } ).on( 'click', '.btn-install', function ()
        {
            let _this = $( this );

            booknetic.ajax( 'boostore.install', { addon_slug: _this.attr( 'data-addon' ) }, function ( res )
            {
                booknetic.toast( res[ 'message' ] );

                booknetic.boostore.onInstall( _this, res );
            } );
        } ).on( 'click', '.btn-uninstall', function ()
        {
            let _this = $( this );

            booknetic.confirm( booknetic.__( 'are_you_sure_want_to_delete' ), 'danger', 'trash', function ()
            {
                let addon = _this.attr( 'data-addon' );

                booknetic.ajax( 'boostore.uninstall', { addon }, function ( res )
                {
                    booknetic.toast( res[ 'message' ] );

                    booknetic.boostore.onUninstall( _this, res );
                } );
            } );
        } );

        booknetic.boostore = {};

    } );

})( jQuery );