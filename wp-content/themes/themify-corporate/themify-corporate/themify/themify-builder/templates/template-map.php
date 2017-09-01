<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Template Map
 * 
 * Access original fields: $mod_settings
 * @author Themify
 */

// Load styles and scripts registered in Themify_Builder::register_frontend_js_css()
$GLOBALS['ThemifyBuilder']->load_templates_js_css( array( 'map' => true ) );

$fields_default = array(
	'mod_title_map' => '',
	'address_map' => '',
	'latlong_map' => '',
	'zoom_map' => 15,
	'w_map' => '100%',
	'unit_w' => '',
	'h_map' => '300px',
	'unit_h' => '',
	'b_style_map' => '',
	'b_width_map' => '',
	'b_color_map' => '',
	'type_map' => 'ROADMAP',
	'scrollwheel_map' => 'disable',
	'draggable_map' => 'enable',
	'draggable_disable_mobile_map' => 'yes',
	'info_window_map' => '',
	'css_map' => '',
	'animation_effect' => ''
);

if ( isset( $mod_settings['address_map'] ) ) 
	$mod_settings['address_map'] = preg_replace( '/\s+/', ' ', trim( $mod_settings['address_map'] ) );

$fields_args = wp_parse_args( $mod_settings, $fields_default );
extract( $fields_args, EXTR_SKIP );
$animation_effect = $this->parse_animation_effect( $animation_effect );
$info_window_map = empty( $info_window_map ) ? sprintf( '<b>%s</b><br/><p>%s</p>', __('Address', 'themify'), $address_map ) : $info_window_map;

// Check if draggable should be disabled on mobile devices
if ( 'enable' == $draggable_map && 'yes' == $draggable_disable_mobile_map && wp_is_mobile() ) 
	$draggable_map = 'disable';

$container_class = implode(' ', 
	apply_filters( 'themify_builder_module_classes', array(
		'module', 'module-' . $mod_name, $module_ID, $css_map, $animation_effect
	), $mod_name, $module_ID, $fields_args )
);
$style = '';

// specify border
if ( isset( $mod_settings['b_width_map'] ) ) {
	$style .= 'border: ';
	$style .= ( isset($mod_settings['b_style_map'] ) ) ? $mod_settings['b_style_map'] : '';
	$style .= ( isset($mod_settings['b_width_map'] ) ) ? ' '.$mod_settings['b_width_map'].'px' : '';
	$style .= ( isset($mod_settings['b_color_map'] ) ) ? ' '.$this->get_rgba_color( $mod_settings['b_color_map'] ) : '';
	$style .= ';';
}

$style .= 'width:';
$style .= ( isset( $mod_settings['w_map'] ) ) ? $mod_settings['w_map'].$mod_settings['unit_w'] : '100%';
$style .= ';';
$style .= 'height:';
$style .= ( isset( $mod_settings['h_map'] ) ) ? $mod_settings['h_map'].$mod_settings['unit_h'] : '300px';
$style .= ';';
?>
<!-- module map -->
<div id="<?php echo esc_attr( $module_ID ); ?>" class="<?php echo esc_attr( $container_class ); ?>">
	<?php if( $mod_title_map != '' ): ?>
	<h3 class="module-title"><?php echo wp_kses_post( $mod_title_map ); ?></h3>
	<?php endif; ?>
	<?php 
	if ( ! empty( $address_map ) || ! empty( $latlong_map ) ) {
		$geo_address = ! empty( $address_map ) ? $address_map : $latlong_map;
		// enqueue map script
		if ( ! wp_script_is( 'themify-builder-map-script' ) ) {
			wp_enqueue_script('themify-builder-map-script');
		}
	?>
	<?php $num = rand(0,10000); ?>
		<script type="text/javascript"> 
			function themify_builder_create_map() {
				ThemifyBuilderModuleJs.initialize("<?php echo esc_js( $geo_address ); ?>", <?php echo esc_js( $num ); ?>, <?php echo esc_js( $zoom_map ); ?>, "<?php echo esc_js( $type_map ); ?>", <?php echo 'enable' != $scrollwheel_map ? 'false' : 'true' ; ?>, <?php echo 'enable' != $draggable_map ? 'false' : 'true' ; ?>);
			}
			jQuery(document).ready(function() {
				if( typeof google === 'undefined' ) {
					var script = document.createElement("script");
					script.type = "text/javascript";
					script.src = "<?php echo themify_https_esc( 'http://maps.google.com/maps/api/js' ) . '?sensor=false&callback=themify_builder_create_map'; ?>";
					document.body.appendChild(script);
				} else {
					ThemifyBuilderModuleJs.initialize("<?php echo esc_js( $geo_address ); ?>", <?php echo esc_js( $num ); ?>, <?php echo esc_js( $zoom_map ); ?>, "<?php echo esc_js( $type_map ); ?>", <?php echo 'enable' != $scrollwheel_map ? 'false' : 'true' ; ?>, <?php echo 'enable' != $draggable_map ? 'false' : 'true' ; ?>);
				}
			});
		</script>
		<div id="themify_map_canvas_<?php echo esc_attr( $num ); ?>" style="<?php echo esc_attr( $style ); ?>" class="map-container" data-info-window="<?php echo esc_attr( $info_window_map ); ?>" data-reverse-geocoding="<?php echo ( empty( $address_map ) && ! empty( $latlong_map ) ) ? true: false; ?>">&nbsp;</div>
	<?php
	}
	?>
</div>
<!-- /module map -->