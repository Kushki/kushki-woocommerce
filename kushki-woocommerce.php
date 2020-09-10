<?php

/*
Plugin Name: Kushki - WooCommerce Payment Gateway
Plugin URI: https://www.kushkipagos.com/
Description: Kushki payment gateway for WooCommerce.
Version: 2.2.4
Author: Kushki
Author URI: http://URI_Of_The_Plugin_Author
License: A "Slug" license name e.g. GPL2
License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain:       woocommerce-gateway-kushki
Domain Path:       /languages
*/


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require dirname( __FILE__ ) . '/includes/class-kushki.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_kushki() {

	$plugin = new Kushki_WC();
	$plugin->run();

}
run_kushki();




