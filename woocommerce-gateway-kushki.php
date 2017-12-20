<?php

/**
 * Plugin Name:       Kushki - WooCommerce Payment Gateway.
 * Plugin URI:        https://www.kushkipagos.com/
 * Description:       Kushki payment gateway for WooCommerce.
 * Version:           2.1.2
 * Author:            Kushki
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woocommerce-gateway-kushki
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function activate_kushki() {
	require_once dirname( __FILE__ ) . '/includes/class-kushki-activator.php';
	Kushki_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function deactivate_kushki() {
	require_once dirname( __FILE__ ) . '/includes/class-kushki-deactivator.php';
	Kushki_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_kushki' );
register_deactivation_hook( __FILE__, 'deactivate_kushki' );

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

//	if ( !class_exists( 'WC_Payment_Gateway' ) ) {
//		return;
//	}

	$plugin = new Kushki_WC();
	$plugin->run();

}
run_kushki();
