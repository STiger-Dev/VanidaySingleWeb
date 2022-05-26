<?php
/*
 * Plugin Name: Booknetic SaaS
 * Description: WordPress Appointment Booking and Scheduling system ( SaaS )
 * Version: 2.1.2
 * Author: FS-Code
 * Author URI: https://www.booknetic.com
 * License: Commercial
 * Text Domain: booknetic-saas
 */

defined( 'ABSPATH' ) or exit;

require_once __DIR__ . '/vendor/autoload.php';

new \BookneticSaaS\Providers\Core\Bootstrap();
