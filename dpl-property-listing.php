<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://digitalpie.co.nz/custom-development/
 * @since             1.0.1
 * @package           Dpl_Property_Listing
 *
 * @wordpress-plugin
 * Plugin Name:       DPL Property Listing
 * Plugin URI:        https://digitalpie.co.nz/custom-development
 * Description:       This is a description of the plugin.
 * Version:           1.0.1
 * Author:            Digital Pie
 * Author URI:        https://digitalpie.co.nz/custom-development//
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       dpl-property-listing
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
define( 'DPL_PROPERTY_LISTING_VERSION', '1.0.1' );

// Define constants
define( 'DPL_PROPERTY_LISTING_PATH', realpath( plugin_dir_path( __FILE__ ) ) . '/' );
define( 'DPL_PROPERTY_LISTING_URL', plugin_dir_url( __FILE__ )  );
define( 'DPL_PROPERTY_LISTING_INV','sandbox'); // set sandbox or live
define( 'DPL_PROPERTY_LISTING_CALLBACK_URL', admin_url('admin.php?page=dpl-property-listing-settings') );

// Set Environment
if (DPL_PROPERTY_LISTING_INV == 'sandbox') {
   define('DPL_PROPERTY_LISTING_API_DOMAIN','tmsandbox');
} else {
   define('DPL_PROPERTY_LISTING_API_DOMAIN','trademe');
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-dpl-property-listing-activator.php
 */
function activate_dpl_property_listing() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-dpl-property-listing-activator.php';
	Dpl_Property_Listing_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-dpl-property-listing-deactivator.php
 */
function deactivate_dpl_property_listing() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-dpl-property-listing-deactivator.php';
	Dpl_Property_Listing_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_dpl_property_listing' );
register_deactivation_hook( __FILE__, 'deactivate_dpl_property_listing' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-dpl-property-listing.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_dpl_property_listing() {

	$plugin = new Dpl_Property_Listing();
	$plugin->run();

}
run_dpl_property_listing();
