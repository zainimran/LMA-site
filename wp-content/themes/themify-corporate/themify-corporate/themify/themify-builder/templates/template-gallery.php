<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Template Gallery
 * 
 * Access original fields: $mod_settings
 * @author Themify
 */

// Load styles and scripts registered in Themify_Builder::register_frontend_js_css()
$GLOBALS['ThemifyBuilder']->load_templates_js_css();

$fields_default = array(
	'mod_title_gallery' => '',
	'layout_gallery' => 'grid',
	'image_size_gallery' => 'thumbnail',
	'shortcode_gallery' => '',
	'thumb_w_gallery' => '',
	'thumb_h_gallery' => '',
	'appearance_gallery' => '',
	'css_gallery' => '',
	'gallery_images' => array(),
	'link_opt' => '',
	'rands' => '',
	'animation_effect' => ''
);

if ( isset( $mod_settings['appearance_gallery'] ) ) 
	$mod_settings['appearance_gallery'] = $this->get_checkbox_data( $mod_settings['appearance_gallery'] );

if ( isset( $mod_settings['shortcode_gallery'] ) ) {
	$mod_settings['gallery_images'] = $this->get_images_from_gallery_shortcode( $mod_settings['shortcode_gallery'] );
	$mod_settings['link_opt'] = $this->get_gallery_param_option( $mod_settings['shortcode_gallery'] );
}

$fields_args = wp_parse_args( $mod_settings, $fields_default );
extract( $fields_args, EXTR_SKIP );
$animation_effect = $this->parse_animation_effect( $animation_effect );

$columns = ( $shortcode_gallery != '' ) ? $this->get_gallery_param_option( $shortcode_gallery, 'columns' ) : '';
$columns = ( $columns == '' ) ? 3 : $columns;
$columns = intval( $columns );

$container_class = implode(' ', 
	apply_filters( 'themify_builder_module_classes', array(
		'module', 'module-' . $mod_name, $module_ID, 'gallery', 'gallery-columns-' . $columns, 'layout-' . $layout_gallery, $appearance_gallery, $css_gallery, $animation_effect
	), $mod_name, $module_ID, $fields_args )
);
?>
<!-- module gallery -->
<div id="<?php echo esc_attr( $module_ID ); ?>" class="<?php echo esc_attr( $container_class ); ?>">

	<?php if ( $mod_title_gallery != '' ): ?>
	<h3 class="module-title"><?php echo wp_kses_post( $mod_title_gallery ); ?></h3>
	<?php endif; ?>

	<?php
	// render the template
	$this->retrieve_template( 'template-'.$mod_name.'-'.$layout_gallery.'.php', array(
		'module_ID' => $module_ID,
		'mod_name' => $mod_name,
		'gallery_images' => $gallery_images,
		'columns' => $columns,
		'settings' => ( isset( $fields_args ) ? $fields_args : array() )
	), '', '', true );
	?>

</div>
<!-- /module gallery -->