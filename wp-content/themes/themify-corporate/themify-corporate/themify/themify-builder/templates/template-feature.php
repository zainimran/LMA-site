<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Template Image
 * 
 * Access original fields: $mod_settings
 * @author Themify
 */

// Load styles and scripts registered in Themify_Builder::register_frontend_js_css()
$GLOBALS['ThemifyBuilder']->load_templates_js_css( array( 'chart' => true ) );

$chart_vars = apply_filters('themify_chart_init_vars', array(
	'trackColor' => 'rgba(0,0,0,.1)',
	'scaleColor' => 0,
	'scaleLength' => 0,
	'lineCap' => 'butt',
	'rotate' => 0,
	'size' => 150,
	'lineWidth' => 3,
	'animate' => 2000
));

$fields_default = array(
	'mod_title_feature' => '',
	'title_feature' => '',
	'layout_feature' => 'icon-left',
	'content_feature' => '',
	'circle_percentage_feature' => '',
	'circle_color_feature' => 'de5d5d',
	'circle_stroke_feature' => $chart_vars['lineWidth'],
	'icon_type_feature' => 'icon',
	'image_feature' => '',
	'icon_feature' => '',
	'icon_color_feature' => '000000',
	'icon_bg_feature' => '',
	'circle_size_feature' => 'medium',
	'link_feature' => '',
	'param_feature' => array(),
	'css_feature' => '',
	'animation_effect' => ''
);

if ( isset( $mod_settings['param_feature'] ) )
	$mod_settings['param_feature'] = explode( '|', $mod_settings['param_feature'] );

$fields_args = wp_parse_args( $mod_settings, $fields_default );
extract( $fields_args, EXTR_SKIP );
$animation_effect = $this->parse_animation_effect( $animation_effect );

/* configure the chart size based on the option */
if( $circle_size_feature == 'large' ) {
	$chart_vars['size'] = 200;
} elseif( $circle_size_feature == 'small' ) {
	$chart_vars['size'] = 100;
}

$chart_class = ( $circle_percentage_feature == '' ) ? 'no-chart' : 'with-chart';
if( '' == $circle_percentage_feature ) {
	$circle_percentage_feature = '0';
	$chart_vars['trackColor'] = 'rgba(0,0,0,0)'; // transparent
}
$link_type = '';
if( '' != $link_feature ) {
	if( in_array( 'lightbox', $param_feature ) ) {
		$link_type = 'lightbox';
	} elseif( in_array( 'newtab', $param_feature ) ) {
		$link_type = 'newtab';
	}
}

$container_class = implode(' ', 
	apply_filters( 'themify_builder_module_classes', array(
		'module', 'module-' . $mod_name, $module_ID, $chart_class, 'layout-' . $layout_feature, 'size-' . $circle_size_feature, $css_feature, $animation_effect
	), $mod_name, $module_ID, $fields_args )
);

?>
<!-- module feature -->
<div id="<?php echo esc_attr( $module_ID ); ?>" class="<?php echo esc_attr( $container_class ); ?>">

	<?php if ( $mod_title_feature != '' ): ?>
	<h3 class="module-title"><?php echo wp_kses_post( $mod_title_feature ); ?></h3>
	<?php endif; ?>

	<?php do_action( 'themify_builder_before_template_content_render' ); ?>

	<figure class="module-feature-image">

		<?php if( '' != $link_feature ) : ?>
			<a href="<?php echo esc_url( 'lightbox' == $link_type ? themify_get_lightbox_iframe_link( $link_feature ) : $link_feature ); ?>" <?php if ( 'lightbox' == $link_type ) : echo 'class="lightbox"'; endif; if ( 'newtab' == $link_type ) : echo 'target="_blank"'; endif; ?>>
		<?php endif; ?>

		<?php if( '' != $circle_percentage_feature ) : ?>
			<div class="module-feature-chart" data-percent="<?php echo esc_attr( $circle_percentage_feature ); ?>" data-color="<?php echo esc_attr( $this->get_rgba_color( $circle_color_feature ) ); ?>" data-trackcolor="<?php echo esc_attr( $chart_vars['trackColor'] ); ?>" data-linecap="<?php echo esc_attr( $chart_vars['lineCap'] ); ?>" data-scalelength="<?php echo esc_attr( $chart_vars['scaleLength'] ); ?>" data-rotate="<?php echo esc_attr( $chart_vars['rotate'] ); ?>" data-size="<?php echo esc_attr( $chart_vars['size'] ); ?>" data-linewidth="<?php echo esc_attr( $circle_stroke_feature ); ?>" data-animate="<?php echo esc_attr( $chart_vars['animate'] ); ?>">
		<?php endif; ?>

			<?php if( 'image' == $icon_type_feature && ! empty( $image_feature ) ) : ?>
				<?php $alt = ( $alt_text = get_post_meta( TB_Feature_Module::get_attachment_id_by_url( $image_feature ), '_wp_attachment_image_alt', true ) ) ? $alt_text : $title_feature; ?>
				<img src="<?php echo esc_url( $image_feature ); ?>" alt="<?php echo esc_attr( $alt ); ?>" />
			<?php else : ?>
				<?php if( '' != $icon_bg_feature ) : ?><div class="module-feature-background" style="background: <?php echo esc_attr( $this->get_rgba_color( $icon_bg_feature ) ); ?>"></div><?php endif; ?>
				<?php if( '' != $icon_feature ) : ?><i class="module-feature-icon fa <?php echo esc_attr( themify_get_fa_icon_classname( $icon_feature ) ); ?>" style="color: <?php echo esc_attr( $this->get_rgba_color( $icon_color_feature ) ); ?>"></i><?php endif; ?>
			<?php endif; ?>

		<?php if( '' != $circle_percentage_feature ) : ?>
			</div><!-- .chart -->
		<?php endif; ?>

		<?php if( '' != $link_feature ) : ?>
			</a>
		<?php endif; ?>

	</figure>

	<div class="module-feature-content">
		<?php if( '' != $title_feature ) : ?>
			<h3 class="module-feature-title">
			<?php if( '' != $link_feature ) : ?>
				<a href="<?php echo esc_url( 'lightbox' == $link_type ? themify_get_lightbox_iframe_link( $link_feature ) : $link_feature ); ?>" <?php if ( 'lightbox' == $link_type ) : echo 'class="lightbox"'; endif; if ( 'newtab' == $link_type ) : echo 'target="_blank"'; endif; ?>>
			<?php endif; ?>

			<?php echo wp_kses_post( $title_feature ); ?>

			<?php if( '' != $link_feature ) : ?>
				</a>
			<?php endif; ?>
			</h3>
		<?php endif; ?>

		<?php echo apply_filters( 'themify_builder_module_content', $content_feature ); ?>
	</div>

	<?php do_action( 'themify_builder_after_template_content_render' ); ?>
</div>
<!-- /module feature -->