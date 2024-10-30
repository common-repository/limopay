<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              bdtask.com
 * @since             1.0.0
 * @package           Limopay
 *
 * @wordpress-plugin
 * Plugin Name:       limopay
 * Plugin URI:        https://www.bdtask.com
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Bdtask
 * Author URI:        bdtask.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       limopay
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'LIMOPAY_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-limupay-activator.php
 */
function limopay_activate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-limopay-activator.php';
	Limopay_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-limupay-deactivator.php
 */
function limopay_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-limopay-deactivator.php';
	Limopay_Deactivate::deactivate();
}

register_activation_hook( __FILE__, 'limopay_activate' );
register_deactivation_hook( __FILE__, 'limopay_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-limopay.php';

// check woocommerce  plugins active or not 
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;


// plugins loaded here 
add_filter( 'woocommerce_payment_gateways', 'limopay_add_gateway_class' );
function limopay_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Limopay_Gateway'; // your class name is here
	return $gateways;
}

require plugin_dir_path( __FILE__ ) . 'admin/partials/limopay-admin-display.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function limopay_run() {

	$plugin = new Limopay();
	$plugin->run();

}
limopay_run();
