<?php
/**
 * Plugin Name: WebP Image Converter
 * Plugin URI: https://example.com/webp-converter
 * Description: Convert images to WebP, JPEG, PNG, GIF, and SVG formats directly in the browser using Canvas API.
 * Version: 1.0.0
 * Author: Developer
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: webp-converter
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Currently plugin version.
 */
define( 'WEBP_CONVERTER_VERSION', '1.0.0' );

/**
 * Plugin directory path.
 */
define( 'WEBP_CONVERTER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'WEBP_CONVERTER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Include required files.
 */
require_once WEBP_CONVERTER_PLUGIN_DIR . 'includes/class-converter-loader.php';
require_once WEBP_CONVERTER_PLUGIN_DIR . 'admin/class-converter-admin.php';
require_once WEBP_CONVERTER_PLUGIN_DIR . 'admin/class-converter-settings.php';
require_once WEBP_CONVERTER_PLUGIN_DIR . 'public/class-converter-public.php';

/**
 * Initialize the plugin.
 */
function webp_converter_init() {
    $loader = new WebP_Converter_Loader();
    $loader->run();
}
add_action( 'plugins_loaded', 'webp_converter_init' );

/**
 * Load plugin textdomain for internationalization.
 */
function webp_converter_load_textdomain() {
    load_plugin_textdomain(
        'webp-converter',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages/'
    );
}
add_action( 'plugins_loaded', 'webp_converter_load_textdomain' );

/**
 * Register activation/deactivation hooks.
 */
register_activation_hook( __FILE__, 'webp_converter_activate' );
register_deactivation_hook( __FILE__, 'webp_converter_deactivate' );

/**
 * Plugin activation.
 */
function webp_converter_activate() {
    // Set default options on activation
    $defaults = array(
        'webp_converter_formats' => array( 'webp', 'jpeg', 'png', 'gif', 'svg' ),
        'webp_converter_jpeg_quality' => 90,
        'webp_converter_png_quality' => 100,
        'webp_converter_webp_quality' => 90,
    );
    foreach ( $defaults as $key => $value ) {
        if ( false === get_option( $key ) ) {
            add_option( $key, $value );
        }
    }
    // Flush rewrite rules for shortcode
    flush_rewrite_rules();
}

/**
 * Plugin deactivation.
 */
function webp_converter_deactivate() {
    flush_rewrite_rules();
}