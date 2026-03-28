<?php
/**
 * Plugin Name: Impact Websites - Roof Estimate and Quote
 * Plugin URI:  https://impactwebsites.co.nz/
 * Description: Displays an instant roof painting estimate calculator and contact form via shortcode [roof_estimate_quote]. Fully configurable from the admin settings page.
 * Version:     1.1.0
 * Author:      Impact Websites
 * Author URI:  https://impactwebsites.co.nz/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: impact-roof-estimate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'IRREQ_VERSION', '1.1.0' );
define( 'IRREQ_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IRREQ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'IRREQ_OPTION_KEY', 'irreq_settings' );

require_once IRREQ_PLUGIN_DIR . 'includes/class-admin-settings.php';
require_once IRREQ_PLUGIN_DIR . 'includes/class-shortcode.php';
require_once IRREQ_PLUGIN_DIR . 'includes/class-email-handler.php';

/**
 * Returns the plugin's default settings.
 *
 * @return array
 */
function irreq_default_settings() {
	return array(
		// General
		'form_title'                   => 'Roof Painting Instant Estimate',
		'form_subtitle'                => 'Get a ballpark price for roof painting in seconds',
		'submit_button_text'           => 'Confirm My Quote',
		'success_message'              => 'Thanks! We\'ll be in touch shortly to confirm your quote.',

		// Email
		'receiver_email'               => get_option( 'admin_email' ),
		'from_name'                    => get_bloginfo( 'name' ),
		'from_email'                   => get_option( 'admin_email' ),
		'email_subject_template'       => 'Roof Painting Online Estimate - {name}',

		// Estimate / pricing
		'min_job_total'                => 2000,
		'base_rate'                    => 30,
		'material_concrete_multiplier' => 1.00,
		'material_metal_tile_multiplier' => 1.05,
		'material_longrun_multiplier'  => 0.95,
		'condition_good_multiplier'    => 1.00,
		'condition_average_multiplier' => 1.15,
		'condition_poor_multiplier'    => 1.30,

		// Form field labels / placeholders
		'label_roof_size'              => 'Roof Size (m²)',
		'placeholder_roof_size'        => 'e.g. 120',
		'label_roof_material'          => 'Roof Material',
		'label_roof_condition'         => 'Roof Condition',
		'label_service'                => 'Service',
		'service_description'          => 'Roof Painting (incl. clean, moss, primer + 2 top coats)',
		'label_name'                   => 'Your Name',
		'placeholder_name'             => 'Your Name',
		'label_phone'                  => 'Phone Number',
		'placeholder_phone'            => 'Phone Number',
		'label_email'                  => 'Email Address',
		'placeholder_email'            => 'Email Address',

		// Cloudflare Turnstile
		'cf_site_key'                  => '',
		'cf_secret_key'                => '',
	);
}

/**
 * Returns current settings merged with defaults.
 *
 * @return array
 */
function irreq_get_settings() {
	$saved = get_option( IRREQ_OPTION_KEY, array() );
	return wp_parse_args( $saved, irreq_default_settings() );
}

/**
 * Plugin activation: store default settings if not already set.
 */
function irreq_activate() {
	if ( ! get_option( IRREQ_OPTION_KEY ) ) {
		update_option( IRREQ_OPTION_KEY, irreq_default_settings() );
	}
}
register_activation_hook( __FILE__, 'irreq_activate' );

/**
 * Plugin deactivation (no data removal – keep settings).
 */
function irreq_deactivate() {}
register_deactivation_hook( __FILE__, 'irreq_deactivate' );

// Bootstrap classes.
new IRREQ_Admin_Settings();
new IRREQ_Shortcode();
new IRREQ_Email_Handler();
