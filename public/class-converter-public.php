<?php
/**
 * The public-facing functionality of the plugin.
 *
 * Defines the shortcode and frontend rendering.
 *
 * @package WebP_Converter
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The Public class.
 */
class WebP_Converter_Public {

    /**
     * Available image formats.
     *
     * @var array
     */
    private $all_formats = array( 'webp', 'jpeg', 'png', 'gif', 'svg' );

    /**
     * Initialize the public hooks.
     */
    public function init() {
        // Register shortcode
        add_shortcode( 'webp_converter', array( $this, 'render_shortcode' ) );

        // Enqueue frontend scripts and styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Enqueue frontend scripts and styles.
     */
    public function enqueue_assets() {
        // Only load if shortcode is present
        global $post;
        if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'webp_converter' ) ) {
            $this->load_assets();
        }
    }

    /**
     * Actually load the assets.
     */
    private function load_assets() {
        // Enqueue JSZip from CDN
        wp_enqueue_script(
            'jszip',
            'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js',
            array(),
            '3.10.1',
            true
        );

        // Enqueue public CSS
        wp_enqueue_style(
            'webp-converter-public',
            WEBP_CONVERTER_PLUGIN_URL . 'public/css/converter-public.css',
            array(),
            WEBP_CONVERTER_VERSION
        );

        // Enqueue public JS
        wp_enqueue_script(
            'webp-converter-public',
            WEBP_CONVERTER_PLUGIN_URL . 'public/js/converter-public.js',
            array( 'jszip' ),
            WEBP_CONVERTER_VERSION,
            true
        );

        // Get settings for JS
        $formats = get_option( 'webp_converter_formats', $this->all_formats );
        if ( ! is_array( $formats ) ) {
            $formats = $this->all_formats;
        }

        $jpeg_quality = (int) get_option( 'webp_converter_jpeg_quality', 90 );
        $png_quality  = (int) get_option( 'webp_converter_png_quality', 100 );
        $webp_quality = (int) get_option( 'webp_converter_webp_quality', 90 );

        // Localize script with settings
        wp_localize_script(
            'webp-converter-public',
            'webpConverterSettings',
            array(
                'formats'       => $formats,
                'jpegQuality'   => $jpeg_quality,
                'pngQuality'    => $png_quality,
                'webpQuality'   => $webp_quality,
                'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
                'nonce'         => wp_create_nonce( 'webp_converter_nonce' ),
            )
        );
    }

    /**
     * Render the shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_shortcode( $atts ) {
        // Parse shortcode attributes
        $atts = shortcode_atts(
            array(
                'formats' => implode( ',', $this->all_formats ),
            ),
            $atts,
            'webp_converter'
        );

        // Parse formats attribute
        $requested_formats = array_map( 'trim', explode( ',', strtolower( $atts['formats'] ) ) );
        $allowed_formats   = get_option( 'webp_converter_formats', $this->all_formats );
        if ( ! is_array( $allowed_formats ) ) {
            $allowed_formats = $this->all_formats;
        }

        // Use requested formats if valid, otherwise use allowed formats
        $formats = array_intersect( $requested_formats, $allowed_formats );
        if ( empty( $formats ) ) {
            $formats = $allowed_formats;
        }

        // Ensure formats are valid
        $formats = array_intersect( $formats, $this->all_formats );
        if ( empty( $formats ) ) {
            $formats = $this->all_formats;
        }

        // Enqueue assets for this shortcode
        $this->load_assets();

        // Load template
        ob_start();
        include WEBP_CONVERTER_PLUGIN_DIR . 'public/partials/converter-public-display.php';
        return ob_get_clean();
    }
}