<?php
/**
 * Template for the public converter display.
 *
 * @package WebP_Converter
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$format_labels = array(
    'webp'  => 'WebP',
    'jpeg'  => 'JPEG',
    'png'   => 'PNG',
    'gif'   => 'GIF',
    'svg'   => 'SVG',
);
?>
<div class="webp-converter-wrapper">
    <h2 class="webp-converter-title"><?php esc_html_e( 'Convert image to any format', 'webp-converter' ); ?></h2>
    <div class="webp-converter-controls">
        <input type="file" multiple accept="image/*" class="webp-converter-file-input" />
        <select id="webpConverterFormat" class="webp-converter-format-select">
            <?php foreach ( $formats as $format ) : ?>
                <option value="<?php echo esc_attr( $format ); ?>"><?php echo esc_html( $format_labels[ $format ] ); ?></option>
            <?php endforeach; ?>
        </select>
        <button id="webpConverterDownloadAll" class="webp-converter-download-all-btn" style="display: none;">
            <?php esc_html_e( 'Download All', 'webp-converter' ); ?>
        </button>
    </div>
    <div id="webpConverterPreviews" class="webp-converter-previews"></div>
    <div class="webp-converter-drop-target"></div>
</div>