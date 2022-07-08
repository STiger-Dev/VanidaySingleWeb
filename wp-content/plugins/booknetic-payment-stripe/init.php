<?php
/*
 * Plugin Name: Stripe payment gateway for Booknetic
 * Description: Take your payments with the Stripe - Credit Card method.
 * Version: 1.0.5
 * Author: FS Code
 * Author URI: https://www.booknetic.com
 * License: Commercial
 * Text Domain: booknetic-payment-stripe
 */

defined( 'ABSPATH' ) or exit;

require_once __DIR__ . '/vendor/autoload.php';

add_filter('bkntc_addons_load', function ($addons)
{
    $addons[ \BookneticAddon\StripePaymentGateway\StripeAddon::getAddonSlug() ] = new \BookneticAddon\StripePaymentGateway\StripeAddon();
    return $addons;
});
