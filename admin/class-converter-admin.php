<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and admin settings page.
 *
 * @package WebP_Converter
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The Admin class.
 */
class WebP_Converter_Admin {

    /**
     * Initialize the admin hooks.
     */
    public function init() {
        // Add admin menu
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        
        // Initialize settings
        $settings = new WebP_Converter_Settings();
        $settings->init();
    }

    /**
     * Add admin menu item.
     */
    public function add_admin_menu() {
        add_options_page(
            __( 'Image Converter Settings', 'webp-converter' ),
            __( 'Image Converter', 'webp-converter' ),
            'manage_options',
            'webp-converter',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'webp_converter_settings_group' );
                do_settings_sections( 'webp_converter' );
                submit_button( __( 'Save Settings', 'webp-converter' ) );
                ?>
            </form>
        </div>
        <?php
    }
}