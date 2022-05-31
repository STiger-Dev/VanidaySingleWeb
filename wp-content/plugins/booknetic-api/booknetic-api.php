<?php
/**
 * @package Booknetic-api
 * @version 1.0
 */
/*
Plugin Name: Booknetic-api
Description: This is customize rest api for booknetic plugin.
Author: FiveStarMobi
Version: 1.0
Author URI: http://fivestarsmobi.com
*/

use BookneticApp\Models\Customer;
use BookneticApp\Models\Location;

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
          )
        );

        register_rest_route( 'booknetic/customer', 'delete/id=(?P<id>\d+)', array(
            array(
              'methods'     => WP_REST_Server::DELETABLE,
              'callback'    => array( $this, 'deleteBookneticCustomer' )
            )
          )
        );

        register_rest_route( 'booknetic/location', 'update/id=(?P<id>\d+)', array(
            array(
              'methods'     => WP_REST_Server::EDITABLE,
              'callback'    => array( $this, 'updateBookneticLocation' )
            )
          )
        );

        register_rest_route( 'booknetic/location', 'hide_location/id=(?P<id>\d+)', array(
            array(
              'methods'     => WP_REST_Server::EDITABLE,
              'callback'    => array( $this, 'hideBookneticLocation' )
            )
          )
        );

        register_rest_route( 'booknetic/location', 'delete/id=(?P<id>\d+)', array(
            array(
              'methods'     => WP_REST_Server::DELETABLE,
              'callback'    => array( $this, 'deleteBookneticLocation' )
            )
          )
        );
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

      Customer::where('id', $id)->noTenant(true)->update( $params );
   
      return new WP_REST_Response( true, 200 );
    }

    /**
     * Get one item from the collection
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function deleteBookneticCustomer( $request ) {
      $params = $request->get_params();
      $id = $params['id'];

      if (empty($id)) {
        return new WP_Error( 'code', __( 'message', 'text-domain' ) );
      }

      Customer::where('id', $id)->noTenant(true)->delete( $params );
   
      return new WP_REST_Response( true, 200 );
    }

    /**
     * Get one item from the collection
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function updateBookneticLocation( $request ) {
      $params = $request->get_params();
      $id = $params['id'];

      if (empty($id)) {
        return new WP_Error( 'code', __( 'message', 'text-domain' ) );
      }

      $location = Location::where( 'id', $id )->noTenant(true)->update( $params );
   
      return new WP_REST_Response( $location, 200 );
    }

    /**
     * Get one item from the collection
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function hideBookneticLocation( $request ) {
      $params = $request->get_params();
      $id = $params['id'];

      if( !( $id > 0 ) )
      {
        return new WP_Error( 'code', __( 'message', 'text-domain' ) );
      }

      $location = Location::noTenant(true)->get( $id );

      if( !$location )
      {
        return new WP_Error( 'code', __( 'message', 'text-domain' ) );
      }

      $new_status = $location['is_active'] == 1 ? 0 : 1;

      $location = Location::where('id', $id)->noTenant(true)->update(['is_active' => $new_status]);

      if (empty($id)) {
        return new WP_Error( 'code', __( 'message', 'text-domain' ) );
      }
      
      return new WP_REST_Response( $location, 200 );
    }

    /**
     * Get one item from the collection
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function deleteBookneticLocation( $request ) {
      $params = $request->get_params();
      $id = $params['id'];

      if (empty($id)) {
        return new WP_Error( 'code', __( 'message', 'text-domain' ) );
      }

      Location::where('id', $id)->noTenant(true)->delete( $params );
   
      return new WP_REST_Response( true, 200 );
    }
  }

// Function to register our new routes from the controller.
function prefix_register_my_rest_routes() {
    $controller = new Booknetic_Custom_Route();
    $controller->register_routes();
}

add_action( 'rest_api_init', 'prefix_register_my_rest_routes' );