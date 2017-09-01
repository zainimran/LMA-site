<?php
/**
 * Class Builder Plugin Compatibility
 * @package themify-builder
 */
class Themify_Builder_Plugin_Compat {
	
	/**
	 * Constructor
	 */
	function __construct() {
		global $ThemifyBuilder;

		// Hooks
		add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_scripts' ), 10 );

		// WooCommerce
		if ( $this->is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			add_action( 'woocommerce_after_single_product_summary', array( $this, 'show_builder_below_tabs'), 12 );
			add_action( 'woocommerce_archive_description', array( $this, 'wc_builder_shop_page' ), 11 );
		}

		// WPSEO live preview
		if ( $this->is_plugin_active( 'wordpress-seo/wp-seo.php' ) ) {
			add_action( 'wp_ajax_wpseo_get_html_builder', array( &$this, 'wpseo_get_html_builder_ajaxify' ), 10 );
			add_filter( 'wpseo_pre_analysis_post_content', array( $this, 'wpseo_pre_analysis_post_content' ), 10, 2 );
		}
	}

	function show_builder_below_tabs() {
		global $post, $ThemifyBuilder;
		if ( ! is_singular( 'product' ) && 'product' != get_post_type() ) return;
		
		$builder_data = get_post_meta( $post->ID, '_themify_builder_settings', true );
		$builder_data = stripslashes_deep( maybe_unserialize( $builder_data ) );

		if ( ! is_array( $builder_data ) ) {
			$builder_data = array();
		}

		$ThemifyBuilder->retrieve_template( 'builder-output.php', array( 'builder_output' => $builder_data, 'builder_id' => $post->ID ), '', '', true );
	}

	function load_admin_scripts( $hook ) {
		global $version, $pagenow, $current_screen;

		if ( in_array( $hook, array( 'post-new.php', 'post.php' ) ) && in_array( get_post_type(), themify_post_types() ) ) {
			wp_enqueue_script( 'themify-builder-plugin-compat', THEMIFY_BUILDER_URI .'/js/themify.builder.plugin.compat.js', array('jquery'), $version, true );
			wp_localize_script( 'themify-builder-plugin-compat', 'TBuilderPluginCompat', apply_filters( 'themify_builder_plugin_compat_vars', array(
				'wpseo_active' => $this->is_plugin_active( 'wordpress-seo/wp-seo.php' ),
				'wpseo_builder_content_text' => __( 'Themify Builder: ', 'themify')
			)) );
		}
	}

	/**
	 * Echo builder on description tab
	 * @return void
	 */
	function echo_builder_on_description_tabs() {
		global $post;
		echo apply_filters( 'the_content', $post->post_content );
	}

	/**
	 * Plugin Active checking
	 * @param string $plugin 
	 * @return bool
	 */
	function is_plugin_active( $plugin ) {
		return in_array( $plugin, apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
	}

	/**
	 * Get html data builder
	 */
	function wpseo_get_html_builder_ajaxify(){
		check_ajax_referer( 'tfb_load_nonce', 'nonce' );
		global $ThemifyBuilder;
		$post_id = (int) $_POST['post_id'];
		$meta_key = apply_filters( 'themify_builder_meta_key', '_themify_builder_settings' );

		$builder_data = get_post_meta( $post_id, $meta_key, true );
		$builder_data = stripslashes_deep( maybe_unserialize( $builder_data ) );
		$string = $this->_get_all_builder_text_content( $builder_data );

		$response = array(
			'text_str' => ' ' . $string // prefix with a space
		);

		echo json_encode( $response );

		die();
	}

	/**
	 * Add filter to wpseo Analysis Post Content
	 * @param string $post_content 
	 * @param object $post 
	 * @return string
	 */
	function wpseo_pre_analysis_post_content( $post_content, $post ) {
		global $post;
		$temp_post = $post;
		$meta_key = apply_filters( 'themify_builder_meta_key', '_themify_builder_settings' );
		
		$builder_data = get_post_meta( $post->ID, $meta_key, true );
		$builder_data = stripslashes_deep( maybe_unserialize( $builder_data ) );
		$extends = array( 
			'image' => 'url_image', 'post' => 'mod_title_post', 
			'portfolio' => 'mod_title_portfolio', 'gallery' => 'mod_title_gallery',
			'slider' => 'mod_title_slider', 'testimonial' => 'mod_title_testimonial', 'highlight' => 'mod_title_highlight'
		);
		$string = $this->_get_all_builder_text_content( $builder_data, false, $extends, $post->ID );
		$post_content .= ' ' . $string; // add prefix with a space
		$post = $temp_post;

		return $post_content;
	}

	/**
	 * Get all builder text content from module which contain text
	 * @param array $data 
	 * @return string
	 */
	function _get_all_builder_text_content( $data, $return_text = true, $args = array(), $builder_id = 0 ) {
		global $ThemifyBuilder;

		$defaults = array(
			'box' => 'content_box',
			'text' => 'content_text',
			'callout' => 'text_callout'
		);
		$string_modules = wp_parse_args( $args, $defaults );
		$text_arr = array();
		if ( is_array( $data ) && count( $data ) > 0 ) {
			foreach( $data as $row ) {
				if ( isset( $row['cols'] ) && count( $row['cols'] ) > 0 ) {
					foreach( $row['cols'] as $col ) {
						if ( isset( $col['modules'] ) && count( $col['modules'] ) > 0 ) {
							foreach( $col['modules'] as $module ) {
								if ( isset( $module['mod_name'] ) && in_array( $module['mod_name'], array_keys( $string_modules ) ) ) {
									
									if ( $return_text ) {
										$text = $module['mod_settings'][ $string_modules[ $module['mod_name'] ] ];
									} else {
										$text = $ThemifyBuilder->get_template_module( $module, $builder_id, false );	
									}
									array_push( $text_arr, $text );
								}
							}
						}
					}
				}
			}
		}
		$return_text = implode( ' ', $text_arr );
		$return_text = strip_tags( $return_text, '<img>' );

		return $return_text;
	}

	/**
	 * Show builder on Shop page
	 */
	function wc_builder_shop_page() {
		global $ThemifyBuilder;

		if ( is_post_type_archive( 'product' ) ) {
			$shop_page   = get_post( wc_get_page_id( 'shop' ) );
			if ( $shop_page ) {
				$builder_data = get_post_meta( $shop_page->ID, $ThemifyBuilder->get_meta_key(), true );
				$builder_data = stripslashes_deep( maybe_unserialize( $builder_data ) );

				if ( ! is_array( $builder_data ) ) {
					$builder_data = array();
				}

				$ThemifyBuilder->retrieve_template( 'builder-output.php', array( 'builder_output' => $builder_data, 'builder_id' => $shop_page->ID ), '', '', true );
			}
		}
	}
}