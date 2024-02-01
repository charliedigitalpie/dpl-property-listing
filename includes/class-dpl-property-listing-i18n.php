<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://digitalpie.co.nz/custom-development/
 * @since      1.0.0
 *
 * @package    Dpl_Property_Listing
 * @subpackage Dpl_Property_Listing/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Dpl_Property_Listing
 * @subpackage Dpl_Property_Listing/includes
 * @author     Digital Pie <charlie@digitalpie.co.nz>
 */
class Dpl_Property_Listing_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'dpl-property-listing',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
