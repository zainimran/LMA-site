<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Template Widgetized
 * 
 * Access original fields: $mod_settings
 * @author Themify
 */

// Load styles and scripts registered in Themify_Builder::register_frontend_js_css()
$GLOBALS['ThemifyBuilder']->load_templates_js_css();

$fields_default = array(
	'mod_title_widgetized' => '',
	'sidebar_widgetized' => '',
	'custom_css_widgetized' => '',
	'background_repeat' => '',
	'animation_effect' => ''
);
$fields_args = wp_parse_args( $mod_settings, $fields_default );
extract( $fields_args, EXTR_SKIP );
$animation_effect = $this->parse_animation_effect( $animation_effect );

$container_class = implode(' ', 
	apply_filters( 'themify_builder_module_classes', array(
		'module', 'module-' . $mod_name, $module_ID, $custom_css_widgetized, $background_repeat, $animation_effect
	), $mod_name, $module_ID, $fields_args )
);
?>

<!-- module widgetized -->
<div id="<?php echo esc_attr( $module_ID ); ?>" class="<?php echo esc_attr( $container_class ); ?>">
	<?php
	if ( $mod_title_widgetized != '' )
		echo '<h3 class="module-title">'.$mod_title_widgetized.'</h3>';

	do_action( 'themify_builder_before_template_content_render' );

	if ( $sidebar_widgetized != '' ) {
		if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar( $sidebar_widgetized ) );
	}

	do_action( 'themify_builder_after_template_content_render' );
	?>
</div>
<!-- /module widgetized -->