<?php
/**
 * @package Vaniday-api
 * @version 1.0
 */
/*
Plugin Name: Vaniday-api
Description: This is customize rest api for booknetic plugin.
Author: FiveStarMobi
Version: 1.0
Author URI: http://fivestarsmobi.com
*/

use BookneticApp\Models\Customer;

class Booknetic_Custom_Route extends WP_REST_Controller {

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes() {
        register_rest_route( 'booknetic/customer', 'update/id=(?P<id>\d+)', array(
            array(
              'methods'     => WP_REST_Server::EDITABLE,
              'callback'    => array( $this, 'updateBookneticCustomer' )
            )
        ) );
    }

    /**
     * Get one item from the collection
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function updateBookneticCustomer( $request ) {
      $params = $request->get_params();
      $id = $params['id'];

      if (empty($id)) {
        return new WP_Error( 'code', __( 'message', 'text-domain' ) );
      }

      Customer::where('id', $id)->update( $params );
   
      return new WP_REST_Response( true, 200 );
    }
  }

// Function to register our new routes from the controller.
function prefix_register_my_rest_routes() {
    $controller = new Booknetic_Custom_Route();
    $controller->register_routes();
}

add_action( 'rest_api_init', 'prefix_register_my_rest_routes' );