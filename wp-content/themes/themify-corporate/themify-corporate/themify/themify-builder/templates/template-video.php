<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Template Video
 * 
 * Access original fields: $mod_settings
 * @author Themify
 */

// Load styles and scripts registered in Themify_Builder::register_frontend_js_css()
$GLOBALS['ThemifyBuilder']->load_templates_js_css();

$fields_default = array(
	'mod_title_video' => '',
	'style_video' => 'video-top',
	'url_video' => '',
	'width_video' => '',
	'unit_video' => '',
	'title_video' => '',
	'title_link_video' => false,
	'caption_video' => '',
	'css_video' => '',
	'animation_effect' => ''
);

$fields_args = wp_parse_args( $mod_settings, $fields_default );
extract( $fields_args, EXTR_SKIP );
$animation_effect = $this->parse_animation_effect( $animation_effect );

$video_maxwidth = ( empty( $width_video ) ) ? '' : $width_video . $unit_video;
$container_class = implode(' ', 
	apply_filters( 'themify_builder_module_classes', array(
		'module', 'module-' . $mod_name, $module_ID, $style_video, $css_video, $animation_effect
	), $mod_name, $module_ID, $fields_args )
);
?>

<!-- module video -->
<div id="<?php echo esc_attr( $module_ID ); ?>" class="<?php echo esc_attr( $container_class ); ?>">
	
	<?php if ( $mod_title_video != '' ): ?>
	<h3 class="module-title"><?php echo wp_kses_post( $mod_title_video ); ?></h3>
	<?php endif; ?>

	<?php do_action( 'themify_builder_before_template_content_render' ); ?>

	<div class="video-wrap" <?php echo '' != $video_maxwidth ? 'style="max-width:' . esc_attr( $video_maxwidth ) . ';"' : ''; ?>>
		<?php echo themify_parse_video_embed_vars( wp_oembed_get( esc_url( $url_video ) ), esc_url( $url_video ) ); ?>
	</div>
	<!-- /video-wrap -->
	
	<?php if ( '' != $title_video || '' != $caption_video ): ?>
	<div class="video-content">
		<?php if( '' != $title_video ): ?>
		<h3 class="video-title">
			<?php if ( $title_link_video ) : ?>
			<a href="<?php echo esc_url( $title_link_video ); ?>"><?php echo wp_kses_post( $title_video ); ?></a>
			<?php else: ?>
			<?php echo wp_kses_post( $title_video ); ?>
			<?php endif; ?>
		</h3>
		<?php endif; ?>
		
		<?php if( '' != $caption_video ): ?>
		<div class="video-caption">
			<?php echo apply_filters( 'themify_builder_module_content', $caption_video); ?>
		</div>
		<!-- /video-caption -->
		<?php endif; ?>
	</div>
	<!-- /video-content -->
	<?php endif; ?>
	
	<?php do_action( 'themify_builder_after_template_content_render' ); ?>
</div>
<!-- /module video -->