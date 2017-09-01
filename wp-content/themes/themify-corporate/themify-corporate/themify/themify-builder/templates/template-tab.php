<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Template Tab
 * 
 * Access original fields: $mod_settings
 * @author Themify
 */

// Load styles and scripts registered in Themify_Builder::register_frontend_js_css()
$GLOBALS['ThemifyBuilder']->load_templates_js_css();

$fields_default = array(
	'mod_title_tab' => '',
	'layout_tab' => 'tab-top',
	'color_tab' => '',
	'tab_appearance_tab' => '',
	'tab_content_tab' => array(),
	'css_tab' => '',
	'animation_effect' => ''
);

if ( isset( $mod_settings['tab_appearance_tab'] ) ) 
	$mod_settings['tab_appearance_tab'] = $this->get_checkbox_data( $mod_settings['tab_appearance_tab'] );

$fields_args = wp_parse_args( $mod_settings, $fields_default );
extract( $fields_args, EXTR_SKIP );
$animation_effect = $this->parse_animation_effect( $animation_effect );

$tab_id = $module_ID . '-' . $builder_id;
$container_class = implode(' ', 
	apply_filters( 'themify_builder_module_classes', array(
		'module', 'module-' . $mod_name, $module_ID, 'ui', $layout_tab, $tab_appearance_tab, $color_tab, $css_tab, $animation_effect
	), $mod_name, $module_ID, $fields_args )
);
?>

<!-- module tab -->
<div id="<?php echo esc_attr( $tab_id ); ?>" class="<?php echo esc_attr( $container_class ); ?>">
	<?php if ( $mod_title_tab != '' ): ?>
	<h3 class="module-title"><?php echo wp_kses_post( $mod_title_tab ); ?></h3>
	<?php endif; ?>

	<?php do_action( 'themify_builder_before_template_content_render' ); ?>
	 
	<ul class="tab-nav">
		<?php foreach ( $tab_content_tab as $k => $tab ): ?>
		<li><a href="#tab-<?php echo esc_attr( $tab_id .'-'. $k ); ?>"><?php echo isset( $tab['title_tab'] ) ? $tab['title_tab'] : ''; ?></a></li>
		<?php endforeach; ?>
	</ul>

	<?php foreach ( $tab_content_tab as $k => $tab ): ?>
	<div id="tab-<?php echo esc_attr( $tab_id .'-'. $k ); ?>" class="tab-content">
		<?php
			if ( isset( $tab['text_tab'] ) ) {
				echo apply_filters( 'themify_builder_module_content', $tab['text_tab'] );
			} 
		?>
	</div>
	<?php endforeach; ?>

	<?php do_action( 'themify_builder_after_template_content_render' ); ?>
</div>
<!-- /module tab -->