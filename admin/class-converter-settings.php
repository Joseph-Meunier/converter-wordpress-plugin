<?php
/**
 * Settings API implementation for WebP Converter plugin.
 *
 * @package WebP_Converter
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Settings class handling WordPress Settings API.
 */
class WebP_Converter_Settings {

    /**
     * Available image formats.
     *
     * @var array
     */
    private $formats = array( 'webp', 'jpeg', 'png', 'gif', 'svg' );

    /**
     * Initialize settings.
     */
    public function init() {
        // Register settings
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    /**
     * Register all settings using WordPress Settings API.
     */
    public function register_settings() {
        // Register the settings
        register_setting(
            'webp_converter_settings_group',
            'webp_converter_formats',
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_formats' ),
                'default'           => $this->formats,
            )
        );

        register_setting(
            'webp_converter_settings_group',
            'webp_converter_jpeg_quality',
            array(
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'default'           => 90,
            )
        );

        register_setting(
            'webp_converter_settings_group',
            'webp_converter_png_quality',
            array(
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'default'           => 100,
            )
        );

        register_setting(
            'webp_converter_settings_group',
            'webp_converter_webp_quality',
            array(
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'default'           => 90,
            )
        );

        // Add settings section
        add_settings_section(
            'webp_converter_main_section',
            __( 'Image Format Settings', 'webp-converter' ),
            array( $this, 'render_section' ),
            'webp_converter'
        );

        // Add settings fields
        add_settings_field(
            'webp_converter_formats',
            __( 'Enabled Formats', 'webp-converter' ),
            array( $this, 'render_formats_field' ),
            'webp_converter',
            'webp_converter_main_section'
        );

        add_settings_field(
            'webp_converter_jpeg_quality',
            __( 'JPEG Quality', 'webp-converter' ),
            array( $this, 'render_jpeg_quality_field' ),
            'webp_converter',
            'webp_converter_main_section'
        );

        add_settings_field(
            'webp_converter_png_quality',
            __( 'PNG Quality', 'webp-converter' ),
            array( $this, 'render_png_quality_field' ),
            'webp_converter',
            'webp_converter_main_section'
        );

        add_settings_field(
            'webp_converter_webp_quality',
            __( 'WebP Quality', 'webp-converter' ),
            array( $this, 'render_webp_quality_field' ),
            'webp_converter',
            'webp_converter_main_section'
        );
    }

    /**
     * Sanitize formats array.
     *
     * @param array $formats The formats to sanitize.
     * @return array
     */
    public function sanitize_formats( $formats ) {
        if ( ! is_array( $formats ) ) {
            return $this->formats;
        }
        return array_intersect( $formats, $this->formats );
    }

    /**
     * Render the settings section description.
     */
    public function render_section() {
        echo '<p>' . esc_html__( 'Configure the image converter plugin settings.', 'webp-converter' ) . '</p>';
    }

    /**
     * Render formats checkboxes field.
     */
    public function render_formats_field() {
        $enabled_formats = get_option( 'webp_converter_formats', $this->formats );
        if ( ! is_array( $enabled_formats ) ) {
            $enabled_formats = $this->formats;
        }

        $format_labels = array(
            'webp' => 'WebP',
            'jpeg' => 'JPEG',
            'png'  => 'PNG',
            'gif'  => 'GIF',
            'svg'  => 'SVG',
        );

        foreach ( $this->formats as $format ) :
            $checked = in_array( $format, $enabled_formats, true ) ? 'checked' : '';
            ?>
            <label for="format_<?php echo esc_attr( $format ); ?>">
                <input
                    type="checkbox"
                    id="format_<?php echo esc_attr( $format ); ?>"
                    name="webp_converter_formats[]"
                    value="<?php echo esc_attr( $format ); ?>"
                    <?php echo $checked; ?>
                />
                <?php echo esc_html( $format_labels[ $format ] ); ?>
            </label>
            &nbsp;&nbsp;
            <?php
        endforeach;
        echo '<p class="description">' . esc_html__( 'Select which formats users can convert images to.', 'webp-converter' ) . '</p>';
    }

    /**
     * Render JPEG quality slider field.
     */
    public function render_jpeg_quality_field() {
        $value = (int) get_option( 'webp_converter_jpeg_quality', 90 );
        ?>
        <input
            type="range"
            id="webp_converter_jpeg_quality"
            name="webp_converter_jpeg_quality"
            min="1"
            max="100"
            value="<?php echo esc_attr( $value ); ?>"
        />
        <span id="jpeg_quality_value"><?php echo esc_html( $value ); ?></span>%
        <script>
            (function() {
                var slider = document.getElementById('webp_converter_jpeg_quality');
                var output = document.getElementById('jpeg_quality_value');
                slider.addEventListener('input', function() {
                    output.textContent = this.value;
                });
            })();
        </script>
        <p class="description"><?php esc_html_e( 'Quality for JPEG conversion (1-100). Higher values mean better quality but larger files.', 'webp-converter' ); ?></p>
        <?php
    }

    /**
     * Render PNG quality slider field.
     */
    public function render_png_quality_field() {
        $value = (int) get_option( 'webp_converter_png_quality', 100 );
        ?>
        <input
            type="range"
            id="webp_converter_png_quality"
            name="webp_converter_png_quality"
            min="1"
            max="100"
            value="<?php echo esc_attr( $value ); ?>"
        />
        <span id="png_quality_value"><?php echo esc_html( $value ); ?></span>%
        <script>
            (function() {
                var slider = document.getElementById('webp_converter_png_quality');
                var output = document.getElementById('png_quality_value');
                slider.addEventListener('input', function() {
                    output.textContent = this.value;
                });
            })();
        </script>
        <p class="description"><?php esc_html_e( 'Quality for PNG conversion (1-100). Note: PNG is lossless, but some browsers apply compression.', 'webp-converter' ); ?></p>
        <?php
    }

    /**
     * Render WebP quality slider field.
     */
    public function render_webp_quality_field() {
        $value = (int) get_option( 'webp_converter_webp_quality', 90 );
        ?>
        <input
            type="range"
            id="webp_converter_webp_quality"
            name="webp_converter_webp_quality"
            min="1"
            max="100"
            value="<?php echo esc_attr( $value ); ?>"
        />
        <span id="webp_quality_value"><?php echo esc_html( $value ); ?></span>%
        <script>
            (function() {
                var slider = document.getElementById('webp_converter_webp_quality');
                var output = document.getElementById('webp_quality_value');
                slider.addEventListener('input', function() {
                    output.textContent = this.value;
                });
            })();
        </script>
        <p class="description"><?php esc_html_e( 'Quality for WebP conversion (1-100). Higher values mean better quality but larger files.', 'webp-converter' ); ?></p>
        <?php
    }
}