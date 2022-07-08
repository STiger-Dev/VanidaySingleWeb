(function ( $ )
{
    'use strict';

    $( document ).ready( function ()
    {
        booknetic.addFilter( 'ajax_settings.save_payment_gateways_settings', function ( params )
        {
            params[ 'stripe_client_id' ]     = $( '#input_stripe_client_id' ).val();
            params[ 'stripe_client_secret' ] = $( '#input_stripe_client_secret' ).val();

            return params;
        } );
    } );
} )( jQuery );