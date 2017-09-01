<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Template Image
 * 
 * Access original fields: $mod_settings
 * @author Themify
 */

// Load styles and scripts registered in Themify_Builder::register_frontend_js_css()
$GLOBALS['ThemifyBuilder']->load_templates_js_css();

$fields_default = array(
	'mod_title_image' => '',
	'style_image' => '',
	'url_image' => '',
	'appearance_image' => '',
	'image_size_image' => '',
	'width_image' => '',
	'height_image' => '',
	'title_image' => '',
	'link_image' => '',
	'param_image' => array(),
	'alt_image' => '',
	'caption_image' => '',
	'css_image' => '',
	'animation_effect' => ''
);

if ( isset( $mod_settings['appearance_image'] ) ) 
	$mod_settings['appearance_image'] = $this->get_checkbox_data( $mod_settings['appearance_image'] );

if ( isset( $mod_settings['param_image'] ) ) 
	$mod_settings['param_image'] = explode( '|', $mod_settings['param_image'] );

$fields_args = wp_parse_args( $mod_settings, $fields_default );
extract( $fields_args, EXTR_SKIP );
$animation_effect = $this->parse_animation_effect( $animation_effect );

$container_class = implode(' ', 
	apply_filters( 'themify_builder_module_classes', array(
		'module', 'module-' . $mod_name, $module_ID, $appearance_image, $style_image, $css_image, $animation_effect
	), $mod_name, $module_ID, $fields_args )
);
$lightbox = in_array( 'lightbox', $param_image ) ? true : false;
$zoom = in_array( 'zoom', $param_image ) ? true : false;
$newtab = in_array( 'newtab', $param_image ) ? true : false;
$image_alt = '' != $alt_image ? esc_attr( $alt_image ) : wp_strip_all_tags( $caption_image );
$image_alt = '' != $image_alt ? $image_alt : esc_attr( $title_image );

$param_image_src = 'src='.esc_url($url_image).'&w='.$width_image .'&h='.$height_image.'&alt='.$image_alt.'&ignore=true';
if ( $this->is_img_php_disabled() ) {
	// get image preset
	$preset = $image_size_image != '' ? $image_size_image : themify_get('setting-global_feature_size');
	if ( isset( $_wp_additional_image_sizes[ $preset ]) && $image_size_image != '') {
		$width_image = intval( $_wp_additional_image_sizes[ $preset ]['width'] );
		$height_image = intval( $_wp_additional_image_sizes[ $preset ]['height'] );
	} else {
		$width_image = $width_image != '' ? $width_image : get_option($preset.'_size_w');
		$height_image = $height_image != '' ? $height_image : get_option($preset.'_size_h');
	}
	$image = '<img src="' . esc_url( $url_image ) . '" alt="' . esc_attr( $image_alt ) . '" width="' . esc_attr( $width_image ) . '" height="' . esc_attr( $height_image ) . '">';
} else {
	$image = themify_get_image($param_image_src);
}

// check whether link is image or url
if ( ! empty( $link_image ) ) {
	$check_img = $this->is_img_link( $link_image );
	if ( ! $check_img && $lightbox ) {
		$link_image = untrailingslashit( add_query_arg( array( 'iframe' => 'true', 'width' => '100%', 'height' => '100%' ), $link_image ) );
	}
}

?>
<!-- module image -->
<div id="<?php echo esc_attr( $module_ID ); ?>" class="<?php echo esc_attr( $container_class ); ?>">
	
	<?php if ( $mod_title_image != '' ): ?>
	<h3 class="module-title"><?php echo wp_kses_post( $mod_title_image ); ?></h3>
	<?php endif; ?>

	<?php do_action( 'themify_builder_before_template_content_render' ); ?>

	<div class="image-wrap">
		<?php if ( ! empty( $link_image ) ): ?>
		<a href="<?php echo esc_url( $link_image ); ?>" <?php if ( $lightbox ) : echo 'class="lightbox-builder lightbox"'; endif; ?> <?php if ( $newtab ) : echo 'target="_blank"'; endif; ?>>
			<?php if ( $zoom ): ?>
			<span class="zoom fa fa-search"></span>
			<?php endif; ?>
			<?php echo wp_kses_post( $image ); ?>
		</a>
		<?php else: ?>
			<?php echo wp_kses_post( $image ); ?>
		<?php endif; ?>
	
	<?php if( 'image-overlay' != $style_image ): ?>
	</div>
	<!-- /image-wrap -->
	<?php endif; ?>
	
	<?php if ( ! empty( $title_image ) || ! empty( $caption_image ) ): ?>
	<div class="image-content">
		<?php if ( ! empty( $title_image ) ): ?>
		<h3 class="image-title">
			<?php if ( ! empty( $link_image ) ): ?>
			<a href="<?php echo esc_url( $link_image ); ?>" <?php if ( $lightbox ) : echo 'class="lightbox-builder lightbox"'; endif; ?> <?php if ( $newtab ) : echo 'target="_blank"'; endif; ?>>
				<?php echo wp_kses_post( $title_image ); ?>
			</a>
			<?php else: ?>
				<?php echo wp_kses_post( $title_image ); ?>
			<?php endif; ?>
		</h3>
		<?php endif; ?>
		
		<?php if ( ! empty( $caption_image ) ): ?>
		<div class="image-caption">
			<?php echo apply_filters( 'themify_builder_module_content', $caption_image ); ?>
		</div>
		<!-- /image-caption -->
		<?php endif; ?>
	</div>
	<!-- /image-content -->
	<?php endif; ?>

	<?php if( 'image-overlay' == $style_image ): ?>
	</div>
	<!-- /image-wrap -->
	<?php endif; ?>

	<?php do_action( 'themify_builder_after_template_content_render' ); ?>
</div>
<!-- /module image -->