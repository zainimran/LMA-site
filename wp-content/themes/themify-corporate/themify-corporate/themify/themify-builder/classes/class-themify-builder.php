<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Themify_Builder' ) ) {

	/**
	 * Main Themify Builder class
	 * 
	 * @package default
	 */
	class Themify_Builder {

		/**
		 * @var string
		 */
		private $meta_key;

		/**
		 * @var string
		 */
		private $meta_key_transient;

		/**
		 * @var array
		 */
		var $builder_settings = array();

		/**
		 * @var array
		 */
		var $module_settings = array();

		/**
		 * @var array
		 */
		var $registered_post_types = array();

		/**
		 * Define builder grid active or not
		 * @var bool
		 */
		var $frontedit_active = false;

		/**
		 * Define load form
		 * @var string
		 */
		var $load_form = 'module';

		/**
		 * Directory Registry
		 */
		var $directory_registry = array();

		/**
		 * Array of classnames to add to post objects
		 */
		var $_post_classes = array();

		/**
		 * Get status of builder content whether inside builder content or not
		 */
		public $in_the_loop = false;

		/* active custom post types registered by Builder */
		var $builder_cpt = array();

		/**
		 * Themify Builder Constructor
		 */
		function __construct() {}

		/**
		 * Class Init
		 */
		function init() {
			// Include required files
			$this->includes();
			$this->setup_default_directories();

			/* git #1862 */
			$this->builder_cpt_check();

			do_action( 'themify_builder_setup_modules', $this );

			// Init
			Themify_Builder_Model::load_general_metabox(); // setup metabox fields
			$this->load_modules(); // load builder modules

			// Builder write panel
			add_filter( 'themify_do_metaboxes', array( &$this, 'builder_write_panels' ), 11 );

			// Filtered post types
			add_filter( 'themify_post_types', array( &$this, 'extend_post_types' ) );
			add_filter( 'themify_builder_module_content', 'wptexturize' );
			add_filter( 'themify_builder_module_content', 'convert_smilies' );
			add_filter( 'themify_builder_module_content', 'convert_chars' );
			add_filter( 'themify_builder_module_content', array( &$this, 'the_module_content' ) );

			// Actions
			add_action( 'init', array( &$this, 'setup' ), 10 );
			add_action( 'themify_builder_metabox', array( &$this, 'add_builder_metabox' ), 10 );
			//add_action( 'media_buttons_context', array( &$this, 'add_custom_switch_btn' ), 10 );
			add_action( 'admin_enqueue_scripts', array( &$this, 'load_admin_interface' ), 10 );

			// Asynchronous Loader
			add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_js_css' ), 9 );
			if ( Themify_Builder_Model::is_frontend_editor_page() ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'async_load_builder_js' ), 9 );
				add_action( 'wp_footer', array( $this, 'async_load_assets_loaded' ), 99 );
				add_action( 'wp_ajax_themify_builder_loader', array( $this, 'async_load_builder' ) );
				add_action( 'wp_ajax_nopriv_themify_builder_loader', array( $this, 'async_load_builder' ) );
				// load module panel frontend
				add_action( 'wp_footer', array( $this, 'builder_module_panel_frontedit' ), 10 );
				add_action( 'wp_footer', array( $this, 'load_javascript_template' ), 10 );
				add_action( 'wp_footer', 'themify_font_icons_dialog', 10 );
			}

			// Google Fonts
			add_action( 'wp_footer', array( $this, 'load_builder_google_fonts' ), 10 );

			// Ajax Actions
			add_action( 'wp_ajax_tfb_add_element', array( &$this, 'add_element_ajaxify' ), 10 );
			add_action( 'wp_ajax_tfb_lightbox_options', array( &$this, 'module_lightbox_options_ajaxify' ), 10 );
			add_action( 'wp_ajax_tfb_add_wp_editor', array( &$this, 'add_wp_editor_ajaxify' ), 10 );
			add_action( 'wp_ajax_builder_import', array( &$this, 'builder_import_ajaxify' ), 10 );
			add_action( 'wp_ajax_builder_import_submit', array( &$this, 'builder_import_submit_ajaxify' ), 10 );
			add_action( 'wp_ajax_row_lightbox_options', array( &$this, 'row_lightbox_options_ajaxify' ), 10 );
			add_action( 'wp_ajax_builder_render_duplicate_row', array( &$this, 'render_duplicate_row_ajaxify' ), 10 );

			// Builder Save Data
			add_action( 'wp_ajax_tfb_save_data', array( &$this, 'save_data_builder' ), 10 );

			// Duplicate page / post action
			add_action( 'wp_ajax_tfb_duplicate_page', array( &$this, 'duplicate_page_ajaxify' ), 10 );

			// Hook to frontend
			add_action( 'wp_head', array( &$this, 'load_inline_js_script' ), 10 );
			add_filter( 'the_content', array( &$this, 'builder_show_on_front' ), 11 );
			add_action( 'wp_ajax_tfb_toggle_frontend', array( &$this, 'load_toggle_frontend_ajaxify' ), 10 );
			add_action( 'wp_ajax_tfb_load_module_partial', array( &$this, 'load_module_partial_ajaxify' ), 10 );
			add_action( 'wp_ajax_tfb_load_row_partial', array( &$this, 'load_row_partial_ajaxify' ), 10 );
			add_filter( 'body_class', array( &$this, 'body_class'), 10 );

			// Shortcode
			add_shortcode( 'themify_builder_render_content', array( &$this, 'do_shortcode_builder_render_content' ) );

			// Plupload Action
			add_action( 'admin_enqueue_scripts', array( &$this, 'plupload_admin_head' ), 10 );
			// elioader
			//add_action( 'wp_head', array( &$this, 'plupload_front_head' ), 10 );

			add_action( 'wp_ajax_themify_builder_plupload_action', array( &$this, 'builder_plupload' ), 10 );

			add_action( 'admin_bar_menu', array( &$this, 'builder_admin_bar_menu' ), 100 );

			// Frontend editor
			add_action( 'themify_builder_edit_module_panel', array( &$this, 'module_edit_panel_front'), 10, 2 );

			// Switch to frontend
			add_action( 'save_post', array( &$this, 'switch_frontend' ), 999, 1 );

			// Reset Builder Filter
			// Non active at the moment
			//add_action( 'themify_builder_before_template_content_render', array( &$this, 'do_reset_before_template_content_render' ) );
			//add_action( 'themify_builder_after_template_content_render', array( &$this, 'do_reset_after_template_content_render' ) );

			// WordPress Search
			add_filter( 'posts_where', array( &$this, 'do_search' ) );

			// Row Styling
			add_action( 'themify_builder_row_start', array( &$this, 'render_row_styling' ), 10, 2 );

			add_filter( 'post_class', array( $this, 'filter_post_class' ) );

			if ( Themify_Builder_Model::is_animation_active() ) {
				add_filter( 'themify_builder_animation_inview_selectors', array( $this, 'add_inview_selectors' ) );
			}

			// Render any js classname
			add_action( 'wp_head', array( $this, 'render_javascript_classes' ) );
		}

		/**
		 * Load JS and CSs for async loader.
		 *
		 * @since 2.1.9
		 */
		public function async_load_builder_js() {

			wp_enqueue_style( 'themify-builder-loader', THEMIFY_BUILDER_URI . '/css/themify.builder.loader.css' );
			wp_enqueue_script( 'themify-builder-loader', THEMIFY_BUILDER_URI . '/js/themify.builder.loader' . $this->minified() . '.js', array( 'jquery' ) );
			wp_localize_script( 'themify-builder-loader', 'tbLoaderVars', array(
				'ajaxurl' => admin_url( 'admin-ajax.php', 'relative' ),
				'assets' => array(
					'scripts' => array(),
					'styles'  => array(),
				),
				'post_ID' => get_the_ID(),
				'progress' => '<div id="builder_progress"><div></div></div>',
			) );

			if( function_exists( 'wp_enqueue_media' ) ) {
				wp_enqueue_media();
			}
		}

		/**
		 * Called by AJAX action themify_builder_loader.
		 * 1. Hooks the load_front_js_css function to wp_footer
		 * 2. Saves scripts and styles already loaded in page
		 * 3. Executes wp_head and wp_footer to load new scripts from load_front_js_css. Dismisses output
		 * 4. Compiles list of new styles and scripts to load and js vars to pass
		 * 5. Echoes list
		 *
		 * @since 2.1.9
		 */
		public function async_load_builder() {
			add_action( 'wp_footer', array( $this, 'load_frontend_interface' ) );

			global $wp_scripts, $wp_styles;

			$done_styles = isset( $_POST['styles'] ) ? ( $_POST['styles'] ) : array();
			$done_scripts = isset( $_POST['scripts'] ) ? ( $_POST['scripts'] ) : array();

			ob_start();
			wp_head();
			wp_footer();
			ob_end_clean();

			$results = array();

			$new_styles = array_diff( $wp_styles->done, $done_styles );
			$new_scripts = array_diff( $wp_scripts->done, $done_scripts );

			if ( ! empty( $new_styles ) ) {
				$results['styles'] = array();

				foreach ( $new_styles as $handle ) {
					// Abort if somehow the handle doesn't correspond to a registered stylesheet
					if ( ! isset( $wp_styles->registered[ $handle ] ) )
						continue;

					// Provide basic style data
					$style_data = array(
						'handle' => $handle,
						'media'  => 'all'
					);

					// Base source
					$src = $wp_styles->registered[ $handle ]->src;

					// Take base_url into account
					if ( strpos( $src, 'http' ) !== 0 )
						$src = $wp_styles->base_url . $src;

					// Version and additional arguments
					if ( null === $wp_styles->registered[ $handle ]->ver )
						$ver = '';
					else
						$ver = $wp_styles->registered[ $handle ]->ver ? $wp_styles->registered[ $handle ]->ver : $wp_styles->default_version;

					if ( isset($wp_styles->args[ $handle ] ) )
						$ver = $ver ? $ver . '&amp;' . $wp_styles->args[$handle] : $wp_styles->args[$handle];

					// Full stylesheet source with version info
					$style_data['src'] = add_query_arg( 'ver', $ver, $src );

					// Parse stylesheet's conditional comments if present, converting to logic executable in JS
					if ( isset( $wp_styles->registered[ $handle ]->extra['conditional'] ) && $wp_styles->registered[ $handle ]->extra['conditional'] ) {
						// First, convert conditional comment operators to standard logical operators. %ver is replaced in JS with the IE version
						$style_data['conditional'] = str_replace( array(
							'lte',
							'lt',
							'gte',
							'gt'
						), array(
							'%ver <=',
							'%ver <',
							'%ver >=',
							'%ver >',
						), $wp_styles->registered[ $handle ]->extra['conditional'] );

						// Next, replace any !IE checks. These shouldn't be present since WP's conditional stylesheet implementation doesn't support them, but someone could be _doing_it_wrong().
						$style_data['conditional'] = preg_replace( '#!\s*IE(\s*\d+){0}#i', '1==2', $style_data['conditional'] );

						// Lastly, remove the IE strings
						$style_data['conditional'] = str_replace( 'IE', '', $style_data['conditional'] );
					}

					// Parse requested media context for stylesheet
					if ( isset( $wp_styles->registered[ $handle ]->args ) )
						$style_data['media'] = esc_attr( $wp_styles->registered[ $handle ]->args );

					// Add stylesheet to data that will be returned to IS JS
					array_push( $results['styles'], $style_data );
				}
			}

			if ( ! empty( $new_scripts ) ) {
				$results['scripts'] = array();

				foreach ( $new_scripts as $handle ) {
					// Abort if somehow the handle doesn't correspond to a registered script
					if ( ! isset( $wp_scripts->registered[ $handle ] ) ) {
						continue;
					}

					// Provide basic script data
					$script_data = array(
						'handle'     => $handle,
						'footer'     => ( is_array( $wp_scripts->in_footer ) && in_array( $handle, $wp_scripts->in_footer ) ),
						'jsVars' => $wp_scripts->print_extra_script( $handle, false )
					);

					// Base source
					$src = $wp_scripts->registered[ $handle ]->src;

					// Take base_url into account
					if ( strpos( $src, 'http' ) !== 0 ) {
						$src = $wp_scripts->base_url . $src;
					}

					// Version and additional arguments
					if ( null === $wp_scripts->registered[ $handle ]->ver ) {
						$ver = '';
					} else {
						$ver = $wp_scripts->registered[ $handle ]->ver ? $wp_scripts->registered[ $handle ]->ver : $wp_scripts->default_version;
					}

					if ( isset( $wp_scripts->args[ $handle ] ) ) {
						$ver = $ver ? $ver . '&amp;' . $wp_scripts->args[ $handle ] : $wp_scripts->args[ $handle ];
					}

					// Full script source with version info
					$script_data['src'] = add_query_arg( 'ver', $ver, $src );

					// Add script to data that will be returned to IS JS
					array_push( $results['scripts'], $script_data );
				}
			}

			echo json_encode( $results );

			die();
		}

		/**
		 * Print scripts that are already loaded.
		 *
		 * @since 2.1.9
		 *
		 * @global $wp_scripts, $wp_styles
		 * @action wp_footer
		 * @return string
		 */
		function async_load_assets_loaded() {
			global $wp_scripts, $wp_styles;

			wp_editor('', '');

			$scripts = is_a( $wp_scripts, 'WP_Scripts' ) ? $wp_scripts->done : array();
			$styles = is_a( $wp_styles, 'WP_Styles' ) ? $wp_styles->done : array();

			?><script type="text/javascript">
				jQuery.extend( tbLoaderVars.assets.scripts, <?php echo json_encode( $scripts ); ?> );
				jQuery.extend( tbLoaderVars.assets.styles, <?php echo json_encode( $styles ); ?> );
			</script><?php
		}

		public function builder_cpt_check() {
			$post_types = get_option( 'builder_cpt', null );
			if( ! is_array( $post_types ) ) {
				global $wpdb;
				foreach( array( 'slider', 'highlight', 'testimonial', 'portfolio' ) as $post_type ) {
					$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = '%s'", $post_type ) );
					if( $count > 0 ) {
						$this->builder_cpt[] = $post_type;
					}
				}
				update_option( 'builder_cpt', $this->builder_cpt );
			} else {
				$this->builder_cpt = $post_types;
			}
		}

		public function is_cpt_active( $post_type ) {
			$active = false;
			if( in_array( $post_type, $this->builder_cpt ) ) {
				$active = true;
			}

			return apply_filters( "builder_is_{$post_type}_active", $active );
		}

		/**
		 * Register default directories used to load modules and their templates
		 */
		function setup_default_directories() {
			$this->register_directory( 'templates', THEMIFY_BUILDER_TEMPLATES_DIR, 1 );
			$this->register_directory( 'templates', get_template_directory() . '/themify-builder/', 5 );
			if( is_child_theme() ) {
				$this->register_directory( 'templates', get_stylesheet_directory() . '/themify-builder/', 9 );
			}
			$this->register_directory( 'modules', THEMIFY_BUILDER_MODULES_DIR, 1 );
			$this->register_directory( 'modules', get_template_directory() . '/themify-builder-modules/', 5 );
		}

		/**
		 * Init function
		 */
		function setup() {
			// Define builder path
			$this->builder_settings = array(
				'template_url' => 'themify-builder/',
				'builder_path' => THEMIFY_BUILDER_TEMPLATES_DIR .'/'
			);

			// Define meta key name
			$this->meta_key = apply_filters( 'themify_builder_meta_key', '_themify_builder_settings' );
			$this->meta_key_transient = apply_filters( 'themify_builder_meta_key_transient', 'themify_builder_settings_transient' );

			// Check whether grid edit active
			$this->is_front_builder_activate();
		}

		function get_meta_key() {
			return $this->meta_key;
		}

		/**
		 * Include required files
		 */
		function includes() {
			// Class duplicate page
			include_once THEMIFY_BUILDER_CLASSES_DIR . '/class-builder-duplicate-page.php';
		}

		/**
		 * Builder write panels
		 *
		 * @param $meta_boxes
		 *
		 * @return array
		 */
		function builder_write_panels( $meta_boxes ) {
			global $pagenow;

			// Page builder Options
			$page_builder_options = apply_filters( 'themify_builder_write_panels_options', array(
				// Notice
				array(
					'name' => '_builder_notice',
					'title' => '',
					'description' => '',
					'type' => 'separator',
					'meta' => array(
						'html' => '<div class="themify-info-link">' . wp_kses_post( sprintf( __( '<a href="%s">Themify Builder</a> is a drag &amp; drop tool that helps you to create any type of layouts. To use it: drop the module on the grid where it says "drop module here". Once the post is saved or published, you can click on the "Switch to frontend" button to switch to frontend edit mode.', 'themify' ), 'http://themify.me/docs/builder' ) ) . '</div>'
					),
				),
				array(
					'name' 		=> 'page_builder',
					'title' 	=> __( 'Themify Builder', 'themify' ),
					'description' => '',
					'type' 		=> 'page_builder',
					'meta'		=> array()
				),
				array(
					'name' 		=> 'builder_switch_frontend',
					'title' 		=> false,
					'type' 		=> 'textbox',
					'value'		=> 0,
					'meta'		=> array( 'size' => 'small' )
				)
			) );

			$types = themify_post_types();
			$all_meta_boxes = array();
			foreach ( $types as $type ) {
				$all_meta_boxes[] = apply_filters( 'themify_builder_write_panels_meta_boxes', array(
					'name'		=> __( 'Themify Builder', 'themify' ),
					'id' 		=> 'page-builder',
					'options'	=> $page_builder_options,
					'pages'    	=> $type
				) );
			}

			return array_merge( $meta_boxes, $all_meta_boxes);
		}

		function register_directory( $context, $path, $priority = 10 ) {
			$this->directory_registry[$context][$priority][] = trailingslashit( $path );
		}

		function get_directory_path( $context ) {
			return call_user_func_array( 'array_merge', $this->directory_registry[$context] );;
		}

		/**
		 * Load builder modules
		 */
		function load_modules() {
			// load modules
			$active_modules = $this->get_modules( 'active' );

			foreach ( $active_modules as $m ) {
				$path = $m['dirname'] . '/' . $m['basename'];
				require_once( $path );
			}
		}

		/**
		 * Get module php files data
		 * @param string $select
		 * @return array
		 */
		function get_modules( $select = 'all' ) {
			$_modules = array();
			foreach( $this->get_directory_path( 'modules' ) as $dir ) {
				if( file_exists( $dir ) ) {
					$d = dir( $dir );
					while( ( false !== ( $entry = $d->read() ) ) ) {
						if( $entry !== '.' && $entry !== '..' && $entry !== '.svn' ) {
							$path = $d->path . $entry;
							$module_name = basename( $path );
							$_modules[$module_name] = $path;
						}
					}
				}
			}
			ksort( $_modules );

			foreach ( $_modules as $value ) {
				$path_info = pathinfo( $value );
				$name = explode( '-', $path_info['filename'] );
				$name = $name[1];
				$modules[ $name ] = array(
					'name' => $name,
					'dirname' => $path_info['dirname'],
					'extension' => $path_info['extension'],
					'basename' => $path_info['basename'],
				);
			}

			if ( 'active' == $select ) {
				$pre = 'setting-page_builder_';
				$data = themify_get_data();
				if ( count( $modules ) > 0 ) {
					foreach ( $modules as $key => $m ) {
						$exclude = $pre . 'exc_' . $m['name'];
						if( isset( $data[ $exclude ] ) )
							unset( $modules[ $m['name'] ] );
					}
				}
			} elseif( 'registered' == $select ) {
				foreach ( $modules as $key => $m ) {
					/* check if module is registered */
					if( ! Themify_Builder_Model::check_module_active( $key ) ) {
						unset( $modules[ $key ] );
					}
				}
			}

			return $modules;
		}

		/**
		 * Check if builder frontend edit being invoked
		 */
		function is_front_builder_activate() {
			if( isset( $_POST['builder_grid_activate'] ) && $_POST['builder_grid_activate'] == 1 )
				$this->frontedit_active = true;
		}

		/**
		 * Add builder metabox
		 */
		function add_builder_metabox() {
			global $post, $pagenow;

			$builder_data = get_post_meta( $post->ID, $this->meta_key, true );
			$builder_data = stripslashes_deep( maybe_unserialize( $builder_data ) );

			if ( empty( $builder_data ) ) {
				$builder_data = array();
			}

			include THEMIFY_BUILDER_INCLUDES_DIR . '/themify-builder-meta.php';
		}

		/**
		 * Load admin js and css
		 * @param $hook
		 */
		function load_admin_interface( $hook ) {
			global $pagenow, $current_screen;

			if ( in_array( $hook, array( 'post-new.php', 'post.php' ) ) && in_array( get_post_type(), themify_post_types() ) ) {

				add_action( 'admin_footer', array( &$this, 'load_javascript_template' ), 10 );

				wp_enqueue_style( 'themify-builder-main', THEMIFY_BUILDER_URI . '/css/themify-builder-main.css', array(), THEMIFY_VERSION );
				wp_enqueue_style( 'themify-builder-admin-ui', THEMIFY_BUILDER_URI . '/css/themify-builder-admin-ui.css', array(), THEMIFY_VERSION );
				if( is_rtl() ) {
					wp_enqueue_style( 'themify-builder-admin-ui-rtl', THEMIFY_BUILDER_URI . '/css/themify-builder-admin-ui-rtl.css', array('themify-builder-admin-ui'), THEMIFY_VERSION );
				}

				// Enqueue builder admin scripts
				$enqueue_scripts = array( 'jquery-ui-accordion', 'jquery-ui-droppable', 'jquery-ui-sortable', 'jquery-ui-resizable', 'themify-builder-admin-ui-js' );

				foreach ( $enqueue_scripts as $script ) {
					switch ( $script ) {
						case 'themify-builder-admin-js':
							wp_register_script( 'themify-builder-admin-js', THEMIFY_BUILDER_URI . "/js/themify.builder.admin.js", array('jquery'), THEMIFY_VERSION, true );
							wp_enqueue_script( 'themify-builder-admin-js' );

							wp_localize_script( 'themify-builder-admin-js', 'TBuilderAdmin_Settings', apply_filters( 'themify_builder_ajax_admin_vars', array(
								'home_url' => get_home_url(),
								'permalink' => get_permalink(),
								'tfb_load_nonce' => wp_create_nonce( 'tfb_load_nonce' )
							)) );
						break;

						case 'themify-builder-admin-ui-js':
							wp_register_script( 'themify-builder-admin-ui-js', THEMIFY_BUILDER_URI . "/js/themify.builder.admin.ui.js", array('jquery'), THEMIFY_VERSION, true );
							wp_enqueue_script( 'themify-builder-admin-ui-js' );
							wp_localize_script( 'themify-builder-admin-ui-js', 'themifyBuilder', apply_filters( 'themify_builder_ajax_admin_vars', array(
								'ajaxurl' => admin_url( 'admin-ajax.php' ),
								'tfb_load_nonce' => wp_create_nonce( 'tfb_load_nonce' ),
								'tfb_url' => THEMIFY_BUILDER_URI,
								'dropPlaceHolder' => __( 'drop module here', 'themify' ),
								'draggerTitleMiddle' => __( 'Drag left/right to change columns', 'themify' ),
								'draggerTitleLast' => __( 'Drag left to add columns', 'themify' ),
								'confirm_on_duplicate_page' => __('Save the Builder before duplicating this page?', 'themify'),
								'textRowStyling' => __('Row Styling', 'themify'),
								'permalink' => get_permalink(),
								'isTouch' => themify_is_touch() ? 'true' : 'false',
								'isThemifyTheme' => $this->is_themify_theme() ? 'true' : 'false',
								'subRowDeleteConfirm' => __('Press OK to remove this sub row','themify')
							)) );
						break;

						default:
							wp_enqueue_script( $script );
						break;
					}
				}

				do_action( 'themify_builder_admin_enqueue', $this );
			}
		}

		/**
		 * Load inline js script
		 * Frontend editor
		 */
		function load_inline_js_script() {
			global $post;
			if ( Themify_Builder_Model::is_frontend_editor_page() ) {
			?>
			<script type="text/javascript">
			var ajaxurl = '<?php echo admin_url( 'admin-ajax.php', 'relative' ); ?>',
				isRtl = <?php echo (int) is_rtl(); ?>;
			</script>
			<?php
			}
		}

		/**
		 * Register styles and scripts necessary for Builder template output.
		 * These are enqueued when user initializes Builder or from a template output.
		 *
		 * Registered style handlers:
		 * themify-builder-style
		 * themify-animate
		 *
		 * Registered script handlers:
		 * themify-easy-pie-chart
		 * theme-waypoints
		 * themify-carousel-js
		 * themify-videojs-js
		 * themify-bigvideojs-js
		 * themify-scroll-highlight
		 * themify-builder-module-plugins-js
		 * themify-builder-script-js
		 *
		 * @since 2.1.9
		 */
		function register_frontend_js_css() {
			// Builder main styles
			wp_enqueue_style( 'themify-builder-style', THEMIFY_BUILDER_URI . '/css/themify-builder-style.css', array(), THEMIFY_VERSION );
			wp_register_style( 'themify-animate', THEMIFY_BUILDER_URI . '/css/animate.min.css', array(), THEMIFY_VERSION );

			// Charts
			wp_register_script( 'themify-easy-pie-chart', THEMIFY_BUILDER_URI . '/js/jquery.easy-pie-chart.js', array( 'jquery' ), THEMIFY_VERSION, true );

			// Map
			wp_register_script( 'themify-builder-map-script', themify_https_esc( 'http://maps.google.com/maps/api/js' ) . '?sensor=false', array(), false, true );

			// Waypoints
			wp_register_script( 'theme-waypoints', THEMIFY_URI . '/js/waypoints.min.js', array('jquery'), false, true );

			// Carousel
			wp_register_script( 'themify-carousel-js', THEMIFY_URI . '/js/carousel.js', array('jquery') );

			// Big Video
			wp_register_script( 'themify-videojs-js', THEMIFY_URI . '/js/video.js', array('jquery') );
			wp_register_script( 'themify-bigvideo-js', THEMIFY_URI . '/js/bigvideo.js', array('themify-videojs-js') );

			// Scroll Highlight
			wp_register_script( 'themify-scroll-highlight', THEMIFY_BUILDER_URI . '/js/themify.scroll-highlight.js', array( 'jquery' ) );

			// Builder main scripts
			wp_register_script( 'themify-builder-module-plugins-js', THEMIFY_BUILDER_URI . '/js/themify.builder.module.plugins.js', array( 'jquery' ), THEMIFY_VERSION, true );
			wp_register_script( 'themify-builder-script-js', THEMIFY_BUILDER_URI . '/js/themify.builder.script.js', array( 'jquery', 'theme-waypoints', 'themify-builder-module-plugins-js' ), THEMIFY_VERSION, true );
		}

		/**
		 * Load CSS and JS necessary for rendering of Builder templates, not editing.
		 *
		 * @since 2.1.9
		 */
		function load_templates_js_css( $args = array() ) {
			$args = wp_parse_args( $args, array(
				'chart' => false,
				'carousel' => false,
				'fullvideo' => false,
				'scroll' => false,
				'map' => false,
				'waypoints' => false,
				'module-plugins' => false, // for themify.builder.module.plugins.js
			) );

			// Enqueue Styles
			$this->load_main_styles();

			if ( $args['chart'] && ! wp_script_is( 'themify-easy-pie-chart' ) ) {
				wp_enqueue_script( 'themify-easy-pie-chart' );
			}
			if ( $args['carousel'] && ! wp_script_is( 'themify-carousel-js' ) ) {
				wp_enqueue_script( 'themify-carousel-js' );
			}
			if ( $args['fullvideo'] && ! wp_script_is( 'themify-bigvideo-js' ) ) {
				wp_enqueue_script( 'themify-bigvideo-js' );
			}
			if ( $args['map'] && ! wp_script_is( 'themify-builder-map-script' ) ) {
				wp_enqueue_script( 'themify-builder-map-script' );
			}
			if ( $args['waypoints'] && ! wp_script_is( 'theme-waypoints' ) ) {
				wp_enqueue_script( 'theme-waypoints' );
			}
			if ( $args['module-plugins'] && ! wp_script_is( 'themify-builder-module-plugins-js' ) ) {
				wp_enqueue_script( 'themify-builder-module-plugins-js' );
			}
			if ( $args['scroll'] ) {
				$this->load_scroll_highlight();
			}

			// Enqueue Scripts
			$this->load_main_scripts();
		}

		static $inview_selectors;
		static $new_selectors;
		static $animation_setup = false; // flag to ensure animation CSS is added to the page only once

		/**
		 * Defines selectors for CSS animations and transitions.
		 *
		 * @param $selectors
		 *
		 * @return array
		 */
		public function add_inview_selectors( $selectors ) {
			$extends = array(
				'.module.wow',
				'.themify_builder_content > .themify_builder_row',
				'.module_row',
				'.fly-in > .post', '.fly-in .row_inner > .tb-column',
				'.fade-in > .post', '.fade-in .row_inner > .tb-column',
				'.slide-up > .post', '.slide-up .row_inner > .tb-column'
			);
			return array_merge( $selectors, $extends );
		}

		/**
		 * Enqueues main Builder style and animation CSS and configures it.
		 * Sets variables used when enqueuing main scripts.
		 * Outputs inline styles for transitions.
		 *
		 * @since 2.1.9
		 */
		function load_main_styles() {
			wp_enqueue_style( 'themify-builder-style' );
			wp_enqueue_style( 'themify-animate' );

			if( self::$animation_setup == false ) {
				// Setup Animation
				self::$inview_selectors = apply_filters( 'themify_builder_animation_inview_selectors', array() );
				self::$new_selectors = apply_filters( 'themify_builder_create_animation_selectors', array() );

				$global_selectors = isset( self::$new_selectors['selectors'] ) ? self::$new_selectors['selectors'] : array();
				$specific_selectors = isset( self::$new_selectors['specificSelectors'] ) ? array_keys( self::$new_selectors['specificSelectors']) : array();
				$instyle_selectors = array_merge( self::$inview_selectors, $global_selectors, $specific_selectors );

				if ( count( $instyle_selectors ) > 0 ) {
					$inline_style = '.js.csstransitions ' . join(', .js.csstransitions ', $instyle_selectors ) . '{ visibility:hidden; }';
					wp_add_inline_style( 'themify-builder-style', $inline_style );
				}
				self::$animation_setup = true;
			}
		}

		/**
		 * Enqueues main Builder scripts and passes vars to them.
		 *
		 * @since 2.1.9
		 */
		function load_main_scripts() {
			if ( ! wp_script_is( 'themify-builder-script-js' ) ) {
				wp_enqueue_script( 'themify-builder-script-js' );
				wp_localize_script( 'themify-builder-script-js', 'tbLocalScript', apply_filters( 'themify_builder_script_vars', array(
					'isTouch' => themify_is_touch() ? true : false,
					'isAnimationActive' => Themify_Builder_Model::is_animation_active(),
					'isParallaxActive' => Themify_Builder_Model::is_parallax_active(),
					'animationInviewSelectors' => self::$inview_selectors,
					'createAnimationSelectors' => self::$new_selectors,
					'backgroundSlider' => array(
						'autoplay' => 5000,
						'speed' => 2000,
					),
					'animationOffset' => 100,
					'videoPoster' => THEMIFY_BUILDER_URI . '/img/blank.png',
					'backgroundVideoLoop' => 'yes',
				) ) );
			}
		}

		/**
		 * Loads Scroll-Highlight script and passes JS vars for setup.
		 *
		 * @since 2.1.9
		 */
		function load_scroll_highlight() {
			if ( ! wp_script_is( 'themify-scroll-highlight' ) ) {
				wp_enqueue_script( 'themify-scroll-highlight' );
				wp_localize_script( 'themify-scroll-highlight', 'tbScrollHighlight', apply_filters( 'themify_builder_scroll_highlight_vars', array(
					'fixedHeaderSelector' => '',
					'speed' => 900,
					'navigation' => '#main-nav',
					'scrollOffset' => 0
				) ) );
			}
		}

		/**
		 * Load interface js and css
		 *
		 * @since 2.1.9
		 */
		function load_frontend_interface() {
			// Builder main styles
			$this->load_main_styles();

			// Charts
			wp_enqueue_script( 'themify-easy-pie-chart' );

			// Waypoints
			wp_enqueue_script( 'theme-waypoints' );

			// load only when editing and login
			if ( Themify_Builder_Model::is_frontend_editor_page() ) {
				wp_enqueue_style( 'themify-builder-main', THEMIFY_BUILDER_URI . '/css/themify-builder-main.css', array(), THEMIFY_VERSION );
				wp_enqueue_style( 'themify-builder-admin-ui', THEMIFY_BUILDER_URI . '/css/themify-builder-admin-ui.css', array(), THEMIFY_VERSION );
				wp_enqueue_style( 'themify-icons', THEMIFY_URI . '/themify-icons/themify-icons.css', array(), THEMIFY_VERSION );
				wp_enqueue_style( 'google-fonts-builder', themify_https_esc( 'http://fonts.googleapis.com/css' ) . '?family=Open+Sans:400,300,600|Montserrat' );
				wp_enqueue_style( 'colorpicker', THEMIFY_URI . '/css/jquery.minicolors.css' ); // from themify framework

				// Icon picker
				wp_enqueue_script( 'themify-font-icons-js', THEMIFY_URI . '/js/themify.font-icons-select.js', array( 'jquery' ), THEMIFY_VERSION, true );

				do_action( 'themify_builder_admin_enqueue', $this );
			}

			// lib scripts
			if ( ! wp_script_is( 'themify-carousel-js' ) ) {
				wp_enqueue_script( 'themify-carousel-js' ); // grab from themify framework
			}
			// Check if BigVideo.js is loaded, if it's not, load it after loading Video.js
			// which is set as dependency under the handler 'themify-videojs-js'
			if ( ! wp_script_is( 'themify-bigvideo-js' ) ) {
				wp_enqueue_script( 'themify-bigvideo-js' );
			}

			// Check if scroll highlight is loaded. If it's not, load it.
			$this->load_scroll_highlight();

			// module scripts
			wp_register_script( 'themify-builder-module-plugins-js', THEMIFY_BUILDER_URI . "/js/themify.builder.module.plugins.js", array( 'jquery' ), THEMIFY_VERSION, true );
			wp_enqueue_script( 'themify-builder-module-plugins-js' );

			wp_register_script( 'themify-builder-script-js', THEMIFY_BUILDER_URI . "/js/themify.builder.script.js", array( 'jquery', 'theme-waypoints' ), THEMIFY_VERSION, true );
			wp_enqueue_script( 'themify-builder-script-js' );
			wp_localize_script( 'themify-builder-script-js', 'tbLocalScript', apply_filters( 'themify_builder_script_vars', array( 
				'isTouch' => themify_is_touch() ? true : false,
				'isAnimationActive' => Themify_Builder_Model::is_animation_active(),
				'isParallaxActive' => Themify_Builder_Model::is_parallax_active(),
				'animationInviewSelectors' => self::$inview_selectors,
				'createAnimationSelectors' => self::$new_selectors,
				'backgroundSlider' => array(
					'autoplay' => 5000,
					'speed' => 2000,
				),
				'animationOffset' => 100,
				'videoPoster' => THEMIFY_BUILDER_URI . '/img/blank.png',
			) ) );

			// Main module scripts
			$this->load_main_scripts();

			if ( Themify_Builder_Model::is_frontend_editor_page() ) {

				if ( class_exists( 'Jetpack_VideoPress' ) ) {
					// Load this so submit_button() is available in VideoPress' print_media_templates().
					require_once ABSPATH . 'wp-admin/includes/template.php';
				}

				$enqueue_scripts = array(
					'underscore',
					'jquery-ui-core',
					'jquery-ui-accordion', 
					'jquery-ui-droppable', 
					'jquery-ui-sortable', 
					'jquery-ui-resizable',
					'jquery-effects-core',
					'media-upload',
					'jquery-ui-dialog',
					'wpdialogs',
					'wpdialogs-popup',
					'wplink',
					'word-count',
					'editor',
					'quicktags',
					'wp-fullscreen',
					'admin-widgets',
					'colorpicker-js',
					'themify-builder-google-webfont',
					'themify-builder-front-ui-js'
				);

				// For editor
				wp_enqueue_style( 'buttons' );

				// is mobile version
				if( $this->isMobile() ) {
					wp_register_script( 'themify-builder-mobile-ui-js', THEMIFY_BUILDER_URI . "/js/jquery.ui.touch-punch.js", array( 'jquery' ), THEMIFY_VERSION, true );
					wp_enqueue_script( 'jquery-ui-mouse' );
					wp_enqueue_script( 'themify-builder-mobile-ui-js' );
				}

				foreach ( $enqueue_scripts as $script ) {
					switch ( $script ) {
						case 'admin-widgets':
							wp_enqueue_script( $script, admin_url( '/js/widgets.min.js' ) ,array( 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable' ) );
						break;

						case 'colorpicker-js':
							wp_enqueue_script( $script, THEMIFY_URI . '/js/jquery.minicolors.js', array('jquery') ); // grab from themify framework
						break;

						case 'themify-builder-google-webfont':
							//wp_enqueue_script( $script, themify_https_esc( 'http://ajax.googleapis.com/ajax/libs/webfont/1/webfont.js' ) );
						break;

						case 'themify-builder-front-ui-js':
							// front ui js
							wp_register_script( $script, THEMIFY_BUILDER_URI . "/js/themify.builder.front.ui.js", array( 'jquery', 'jquery-ui-tabs' ), THEMIFY_VERSION, true );
							wp_enqueue_script( $script );

							$gutterClass = Themify_Builder_Model::get_grid_settings('gutter_class');
							wp_localize_script( $script, 'themifyBuilder', apply_filters( 'themify_builder_ajax_front_vars', array(
								'ajaxurl' => admin_url( 'admin-ajax.php' ),
								'isTouch' => themify_is_touch()? 'true': 'false',
								'tfb_load_nonce' => wp_create_nonce( 'tfb_load_nonce' ),
								'tfb_url' => THEMIFY_BUILDER_URI,
								'post_ID' => get_the_ID(),
								'dropPlaceHolder' => __('drop module here', 'themify'),
								'draggerTitleMiddle' => __('Drag left/right to change columns','themify'),
								'draggerTitleLast' => __('Drag left to add columns','themify'),
								'moduleDeleteConfirm' => __('Press OK to remove this module','themify'),
								'toggleOn' => __('Turn On Builder', 'themify'),
								'toggleOff' => __('Turn Off Builder', 'themify'),
								'confirm_on_turn_off' => __('Do you want to save the changes made to this page?', 'themify'),
								'confirm_on_duplicate_page' => __('Save the Builder before duplicating this page?', 'themify'),
								'confirm_on_unload' => __('You have unsaved data.', 'themify'),
								'textImportBuilder' => __('Import From', 'themify'),
								'textRowStyling' => __('Row Styling', 'themify'),
								'importFileConfirm' => __( 'This import will override all current Builder data. Press OK to continue', 'themify'),
								'confirm_template_selected' => __('This will replace your current Builder layout with the Template', 'themify'),
								'load_layout_title' => __('Layouts', 'themify'),
								'save_as_layout_title' => __('Save as Layout', 'themify'),
								'confirm_delete_layout' => __('Are you sure want to delete this layout ?', 'themify'),
								'isThemifyTheme' => $this->is_themify_theme() ? 'true' : 'false',
								'gutterClass' => $gutterClass,
								'subRowDeleteConfirm' => __('Press OK to remove this sub row','themify')
							)) );
							wp_localize_script( $script, 'themify_builder_plupload_init', $this->get_builder_plupload_init() );
						break;
						
						default:
							wp_enqueue_script( $script );
						break;
					}	
				}

			}
		}

		/**
		 * Load Google Fonts Style
		 */
		function load_builder_google_fonts() {
			global $themify;
			if ( ! isset( $themify->builder_google_fonts ) || '' == $themify->builder_google_fonts ) return;
			$themify->builder_google_fonts = substr( $themify->builder_google_fonts, 0, -1 );
			wp_enqueue_style( 'builder-google-fonts', themify_https_esc( 'http://fonts.googleapis.com/css' ). '?family='.$themify->builder_google_fonts );
		}

		/**
		 * Add element via ajax
		 * Drag / drop / add + button
		 */
		function add_element_ajaxify() {
			check_ajax_referer( 'tfb_load_nonce', 'tfb_load_nonce' );

			$template_name = $_POST['tfb_template_name'];
			
			if( 'module_front' == $template_name ) {
				$mod = array( 'mod_name' => $_POST['tfb_module_name'] );
				$this->get_template_module( $mod );
			}
			
			die();
		}

		/**
		 * Module settings modal lightbox
		 */
		function module_lightbox_options_ajaxify() {
			check_ajax_referer( 'tfb_load_nonce', 'nonce' );

			$module_name = $_POST['tfb_module_name'];
			$this->load_form = 'module';
			$module = isset( Themify_Builder_Model::$modules[ $module_name ] ) ? Themify_Builder_Model::$modules[ $module_name ] : false;

			if ( false !== $module ) {
				require_once( THEMIFY_BUILDER_INCLUDES_DIR . '/themify-builder-options-form.php' );
			} else {
				echo '<p>' . wp_kses_post( sprintf( __( 'Module %s is not active', 'themify' ), $module_name ) ) . '</p>';
			}
			
			die();
		}

		/**
		 * Row Styling settings
		 */
		function row_lightbox_options_ajaxify() {
			check_ajax_referer( 'tfb_load_nonce', 'nonce' );
			$this->load_form = 'row';

			require_once( THEMIFY_BUILDER_INCLUDES_DIR . '/themify-builder-options-form.php' );
			die();
		}

		/**
		 * Duplicate page
		 */
		function duplicate_page_ajaxify() {
			global $themifyBuilderDuplicate;
			check_ajax_referer( 'tfb_load_nonce', 'tfb_load_nonce' );

			$post_id = (int) $_POST['tfb_post_id'];
			$post = get_post( $post_id );
			$themifyBuilderDuplicate->edit_link = $_POST['tfb_is_admin'];
			$themifyBuilderDuplicate->duplicate( $post );
			$response['status'] = 'success';
			$response['new_url'] = $themifyBuilderDuplicate->new_url;
			echo json_encode( $response );
			die();
		}

		/**
		 * Add wp editor element
		 */
		function add_wp_editor_ajaxify() {
			check_ajax_referer( 'tfb_load_nonce', 'tfb_load_nonce' );

			$txt_id = $_POST['txt_id'];
			$class = $_POST['txt_class'];
			$txt_name = $_POST['txt_name'];
			$txt_val = stripslashes_deep( $_POST['txt_val'] );
			wp_editor( $txt_val, $txt_id, array('textarea_name' => $txt_name, 'editor_class' => $class, 'textarea_rows' => 20) );
			
			die();
		}

		/**
		 * Load Editable builder grid
		 */
		function load_toggle_frontend_ajaxify() {
			check_ajax_referer( 'tfb_load_nonce', 'tfb_load_nonce' );

			$response = array();
			$post_ids = $_POST['tfb_post_ids'];
			global $post;
			
			foreach( $post_ids as $k => $id ) {
				$sanitize_id = (int)$id;
				$post = get_post( $sanitize_id );
				setup_postdata( $post );
				
				$builder_data = get_post_meta( $post->ID, $this->meta_key, true );
				$builder_data = stripslashes_deep( maybe_unserialize( $builder_data ) );

				if ( ! is_array( $builder_data ) ) {
					$builder_data = array();
				}

				$response[ $k ]['builder_id'] = $post->ID;
				$response[ $k ]['markup'] = $this->retrieve_template( 'builder-output.php', array( 'builder_output' => $builder_data, 'builder_id' => $post->ID ), '', '', false );
			} wp_reset_postdata();

			echo json_encode( $response );

			die();
		}

		/**
		 * Load module partial when update live content
		 */
		function load_module_partial_ajaxify() {
			check_ajax_referer( 'tfb_load_nonce', 'tfb_load_nonce' );
			global $post;
			
			$temp_post = $post;
			$post_id = (int) $_POST['tfb_post_id'];
			$post = get_post( $post_id );
			$module_slug = $_POST['tfb_module_slug'];
			$module_settings = json_decode( stripslashes( $_POST['tfb_module_data'] ), true );
			$identifier = array( uniqid() );
			$response = array();

			$new_modules = array(
				'mod_name' => $module_slug,
				'mod_settings' => $module_settings
			);

			$response['html'] = $this->get_template_module( $new_modules, $post_id, false, true, null, $identifier );
			$response['gfonts'] = $this->get_custom_google_fonts();

			$post = $temp_post;
			echo json_encode( $response );

			die();
		}

		/**
		 * Load row partial when update live content
		 */
		function load_row_partial_ajaxify() {
			check_ajax_referer( 'tfb_load_nonce', 'nonce' );
			global $themify;
			
			$post_id = (int) $_POST['post_id'];
			$row = stripslashes_deep( $_POST['row'] );
			$uniqid = uniqid();
			$response = array();

			if ( isset( $row['row_order'] ) ) 
				unset( $row['row_order'] );

			$response['html'] = $this->get_template_row( $uniqid, $row, $post_id );
			$response['gfonts'] = $this->get_custom_google_fonts();

			echo json_encode( $response );

			die();
		}

		/**
		 * Render duplicate row
		 */
		function render_duplicate_row_ajaxify() {
			check_ajax_referer( 'tfb_load_nonce', 'nonce' );
			
			$row = stripslashes_deep( $_POST['row'] );
			$post_id = $_POST['id'];
			$response = array();
			$uniqid = uniqid();

			if ( isset( $row['row_order'] ) ) 
				unset( $row['row_order'] );

			$response['html'] = $this->get_template_row( $uniqid, $row, $post_id );

			echo json_encode( $response );

			die();
		}

		/**
		 * Save builder main data
		 */
		function save_data_builder() {
			check_ajax_referer( 'tfb_load_nonce', 'tfb_load_nonce' );

			$saveto = $_POST['tfb_saveto'];
			$ids = json_decode( stripslashes( $_POST['ids'] ), true );
			
			if ( is_array( $ids ) && count( $ids ) > 0 ) {
				foreach( $ids as $v ) {
					$post_id = isset( $v['id'] ) ? $v['id'] : '';
					$post_data = ( isset( $v['data'] ) && is_array( $v['data'] ) && count( $v['data'] ) > 0 ) ? $v['data'] : array();
					if ( 'main' == $saveto ) {
						update_post_meta( $post_id, $this->meta_key, $post_data );
						do_action( 'themify_builder_save_data', $post_id, $this->meta_key, $post_data ); // hook save data
					} else {
						$transient = $this->meta_key_transient . '_' . $post_id;
						set_transient( $transient, $post_data, 60*60 );
					}
				}
			}
			
			wp_send_json_success();
		}

		/**
		 * Hook to content filter to show builder output
		 * @param $content
		 * @return string
		 */
		function builder_show_on_front( $content ) {
			global $post, $wp_query;

			if ( is_admin() ) return $content; // Disable builder on admin post list

			if ( ( is_post_type_archive() && ! is_post_type_archive( 'product' ) ) || post_password_required() || isset( $wp_query->query_vars['product_cat'] ) || is_tax( 'product_tag' ) ) return $content;

			if ( is_singular( 'product' ) && 'product' == get_post_type() ) return $content; // dont show builder on product single description

			if ( is_post_type_archive( 'product' ) && get_query_var( 'paged' ) == 0 && $this->builder_is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
				$post = get_post( woocommerce_get_page_id( 'shop' ) );
			}

			if ( ! is_object( $post ) ) return $content;
			
			// Paid Membership Pro
			if( defined( 'PMPRO_VERSION' ) ) {
				$hasaccess = pmpro_has_membership_access( NULL, NULL, true );
				if( is_array( $hasaccess ) ) {
					//returned an array to give us the membership level values
					$post_membership_levels_ids = $hasaccess[1];
					$post_membership_levels_names = $hasaccess[2];
					$hasaccess = $hasaccess[0];
				}

				if( ! $hasaccess ) {
					return $content;
				}
			}

			// Members
			if( class_exists( 'Members_Load' ) ) {
				if( ! members_can_current_user_view_post( get_the_ID() ) ) {
					return $content;
				}
			}

			$builder_data = get_post_meta( $post->ID, $this->meta_key, true );
			$builder_data = stripslashes_deep( maybe_unserialize( $builder_data ) );

			if ( ! is_array( $builder_data ) || strpos( $content, '#more-' ) ) {
				$builder_data = array();
			}

			if ( $this->in_the_loop ) {
				$content .= $this->retrieve_template( 'builder-output-in-the-loop.php', array( 'builder_output' => $builder_data, 'builder_id' => $post->ID ), '', '', false );
			} else {
				$content .= $this->retrieve_template( 'builder-output.php', array( 'builder_output' => $builder_data, 'builder_id' => $post->ID ), '', '', false );
			}
			return $content;
		}

		/**
		 * Display module panel on frontend edit
		 */
		function builder_module_panel_frontedit() {
			include_once( sprintf( "%s/themify-builder-module-panel.php", THEMIFY_BUILDER_INCLUDES_DIR ) );
		}

		public function load_javascript_template() {
			include_once( sprintf( "%s/themify-builder-javascript-tmpl.php", THEMIFY_BUILDER_INCLUDES_DIR ) );
		}

		/**
		 * Get initialization parameters for plupload. Filtered through themify_builder_plupload_init_vars.
		 * @return mixed|void
		 * @since 1.4.2
		 */
		function get_builder_plupload_init() {
			return apply_filters('themify_builder_plupload_init_vars', array(
				'runtimes'				=> 'html5,flash,silverlight,html4',
				'browse_button'			=> 'themify-builder-plupload-browse-button', // adjusted by uploader
				'container' 			=> 'themify-builder-plupload-upload-ui', // adjusted by uploader
				'drop_element' 			=> 'drag-drop-area', // adjusted by uploader
				'file_data_name' 		=> 'async-upload', // adjusted by uploader
				'multiple_queues' 		=> true,
				'max_file_size' 		=> wp_max_upload_size() . 'b',
				'url' 					=> admin_url('admin-ajax.php'),
				'flash_swf_url' 		=> includes_url('js/plupload/plupload.flash.swf'),
				'silverlight_xap_url' 	=> includes_url('js/plupload/plupload.silverlight.xap'),
				'filters' 				=> array( array(
					'title' => __('Allowed Files', 'themify'),
					'extensions' => 'jpg,jpeg,gif,png,zip,txt'
				)),
				'multipart' 			=> true,
				'urlstream_upload' 		=> true,
				'multi_selection' 		=> false, // added by uploader
				 // additional post data to send to our ajax hook
				'multipart_params' 		=> array(
					'_ajax_nonce' 		=> '', // added by uploader
					'action' 			=> 'themify_builder_plupload_action', // the ajax action name
					'imgid' 			=> 0 // added by uploader
				)
			));
		}

		/**
		 * Inject plupload initialization variables in Javascript
		 * @since 1.4.2
		 */
		function plupload_front_head() {
			wp_localize_script( 'themify-builder-front-ui-js', 'themify_builder_plupload_init', $this->get_builder_plupload_init() );
		}

		/**
		 * Plupload initialization parameters
		 * @since 1.4.2
		 */
		function plupload_admin_head() {
			wp_localize_script( 'themify-builder-admin-ui-js', 'themify_builder_plupload_init', $this->get_builder_plupload_init() );
		}

		/**
		 * Plupload ajax action
		 */
		function builder_plupload() {
			// check ajax nonce
			$imgid = $_POST['imgid'];
			//check_ajax_referer( $imgid . 'themify-builder-plupload' );
			check_ajax_referer( 'tfb_load_nonce' );
			
			/** If post ID is set, uploaded image will be attached to it. @var String */
			$postid = $_POST['topost'];

			/** Handle file upload storing file|url|type. @var Array */
			$file = wp_handle_upload( $_FILES[$imgid . 'async-upload'], array('test_form' => true, 'action' => 'themify_builder_plupload_action') );

			//let's see if it's an image, a zip file or something else
			$ext = explode( '/', $file['type'] );

			// Import routines
			if( 'zip' == $ext[1] || 'rar' == $ext[1] || 'plain' == $ext[1] ){
				
				$url = wp_nonce_url( 'admin.php?page=themify' );
				$upload_dir = wp_upload_dir();
				
				if (false === ( $creds = request_filesystem_credentials( $url ) ) ) {
					return true;
				}
				if ( ! WP_Filesystem( $creds ) ) {
					request_filesystem_credentials( $url, '', true );
					return true;
				}
				
				global $wp_filesystem;
				
				if( 'zip' == $ext[1] || 'rar' == $ext[1] ) {
					$destination = wp_upload_dir();
					$destination_path = $destination['path'];

					unzip_file( $file['file'], $destination_path );
					if( $wp_filesystem->exists( $destination_path . '/builder_data_export.txt' ) ){
						$data = $wp_filesystem->get_contents( $destination_path . '/builder_data_export.txt' );
						
						// Set data here
						update_post_meta( $postid, $this->meta_key, maybe_unserialize( $data ) );

						$wp_filesystem->delete( $destination_path . '/builder_data_export.txt');
						$wp_filesystem->delete( $file['file'] );
					} else {
						_e('Data could not be loaded', 'themify');
					}
				} else {
					if( $wp_filesystem->exists( $file['file'] ) ){
						$data = $wp_filesystem->get_contents( $file['file'] );
						
						// set data here
						update_post_meta( $postid, $this->meta_key, maybe_unserialize( $data ) );

						$wp_filesystem->delete($file['file']);
					} else {
						_e('Data could not be loaded', 'themify');
					}
				}
				
			} else {
				// Insert into Media Library
				// Set up options array to add this file as an attachment
				$attachment = array(
					'post_mime_type' => sanitize_mime_type( $file['type'] ),
					'post_title' => str_replace( '-', ' ', sanitize_file_name( pathinfo( $file['file'], PATHINFO_FILENAME ) ) ),
					'post_status' => 'inherit'
				);
				
				if( $postid ) 
					$attach_id = wp_insert_attachment( $attachment, $file['file'], $postid );

				// Common attachment procedures
				require_once( ABSPATH . 'wp-admin' . '/includes/image.php' );
				$attach_data = wp_generate_attachment_metadata( $attach_id, $file['file'] );
				wp_update_attachment_metadata( $attach_id, $attach_data );

				if( $postid ) {		
					$large = wp_get_attachment_image_src( $attach_id, 'large' );		
					$thumb = wp_get_attachment_image_src( $attach_id, 'thumbnail' );
					
					//Return URL for the image field in meta box
					$file['large_url'] = $large[0];
					$file['thumb'] = $thumb[0];
					$file['id'] = $attach_id;
				}
			}

			$file['type'] = $ext[1];
			// send the uploaded file url in response
			echo json_encode( $file );
			exit;
		}

		/**
		 * Display Toggle themify builder
		 * wp admin bar
		 */
		function builder_admin_bar_menu( $wp_admin_bar ) {
			global $wp_query;
			$post_id = get_the_ID();
			
			if ( ( is_post_type_archive() && ! is_post_type_archive( 'product' ) ) || !is_admin_bar_showing() || is_admin() || !current_user_can( 'edit_page', $post_id ) || isset( $wp_query->query_vars['product_cat'] ) || is_tax( 'product_tag' ) ) return;
			
			$args = array(
				array(
					'id'    => 'themify_builder',
					'title' => sprintf('<span class="themify_builder_front_icon"></span> %s', __('Themify Builder','themify')),
					'href'  => '#'
				),
				array(
					'id' => 'toggle_themify_builder',
					'parent' => 'themify_builder',
					'title' => __( 'Turn On Builder', 'themify' ),
					'href' => '#',
					'meta' => array( 'class' => 'toggle_tf_builder')
				),
				array(
					'id' => 'duplicate_themify_builder', 
					'parent' => 'themify_builder',
					'title' => __( 'Duplicate This Page', 'themify' ), 
					'href' => '#', 
					'meta' => array( 'class' => 'themify_builder_dup_link' )
				)
			);


			$help_args = array(
				array(
					'id' => 'help_themify_builder', 
					'parent' => 'themify_builder', 
					'title' => __( 'Help', 'themify' ), 
					'href' => 'http://themify.me/docs/builder',
					'meta' => array( 'target' => '_blank', 'class' => '' )
				)
			);

			if ( is_singular() || is_page() ) {
				$import_args = array(
					array(
						'id' => 'import_themify_builder',
						'parent' => 'themify_builder',
						'title' => __('Import From', 'themify'),
						'href' => '#'
					),
						// Sub Menu
						array(
							'id' => 'from_existing_pages_themify_builder',
							'parent' => 'import_themify_builder',
							'title' => __('Existing Pages', 'themify'),
							'href' => '#',
							'meta' => array( 'class' => 'themify_builder_import_page' )
						),
						array(
							'id' => 'from_existing_posts_themify_builder',
							'parent' => 'import_themify_builder',
							'title' => __('Existing Posts', 'themify'),
							'href' => '#',
							'meta' => array( 'class' => 'themify_builder_import_post' )
						),
					array(
						'id' => 'import_export_themify_builder',
						'parent' => 'themify_builder',
						'title' => __('Import / Export', 'themify'),
						'href' => '#'
					),	
						// Sub Menu
						array(
							'id' => 'import_file_themify_builder',
							'parent' => 'import_export_themify_builder',
							'title' => __('Import', 'themify'),
							'href' => '#',
							'meta' => array( 'class' => 'themify_builder_import_file' )
						),
						array(
							'id' => 'export_file_themify_builder',
							'parent' => 'import_export_themify_builder',
							'title' => __('Export', 'themify'),
							'href' => wp_nonce_url( '?themify_builder_export_file=true&postid=' . $post_id, 'themify_builder_export_nonce' ),
							'meta' => array( 'class' => 'themify_builder_export_file' )
						),
					array(
						'id' => 'layout_themify_builder',
						'parent' => 'themify_builder',
						'title' => __('Layouts', 'themify'),
						'href' => '#'
					),
						// Sub Menu
						array(
							'id' => 'load_layout_themify_builder',
							'parent' => 'layout_themify_builder',
							'title' => __('Load Layout', 'themify'),
							'href' => '#',
							'meta' => array( 'class' => 'themify_builder_load_layout' )
						),
						array(
							'id' => 'save_layout_themify_builder',
							'parent' => 'layout_themify_builder',
							'title' => __('Save as Layout', 'themify'),
							'href' => '#',
							'meta' => array( 'class' => 'themify_builder_save_layout' )
						),
				);
				global $Themify_Builder_Layouts;
				if ( ! is_singular( $Themify_Builder_Layouts->post_types ) || ! Themify_Builder_Model::is_prebuilt_layout( $post_id ) ) {
					$args = array_merge( $args, $import_args );
				} else {
					unset( $args[1] ); // unset Turn on Builder Link
				}
			}

			$args = array_merge( $args, $help_args );
			
			foreach ( $args as $arg ) {
				$wp_admin_bar->add_node( $arg );
			}
		}

		/**
		 * Switch to frontend
		 * @param int $post_id
		 */
		function switch_frontend( $post_id ) {
			//verify post is not a revision
			if ( ! wp_is_post_revision( $post_id ) ) {
				$redirect = isset( $_POST['builder_switch_frontend'] ) ? $_POST['builder_switch_frontend'] : 0;

				// redirect to frontend
				if( 1 == $redirect ) {
					$_POST['builder_switch_frontend'] = 0;
					$post_url = get_permalink( $post_id );
					wp_redirect( themify_https_esc( $post_url ) . '#builder_active' );
					exit;
				}
			}
		}

		/**
		 * Editing module panel in frontend
		 * @param $mod_name
		 * @param $mod_settings
		 */
		function module_edit_panel_front( $mod_name, $mod_settings ) {
			?>
			<div class="module_menu_front">
				<ul class="themify_builder_dropdown_front">
					<li class="themify_module_menu"><span class="ti-menu"></span>
						<ul>
							<li><a href="#" title="<?php _e('Edit', 'themify') ?>" class="themify_module_options" data-module-name="<?php echo esc_attr( $mod_name ); ?>"><?php _e('Edit', 'themify') ?></a></li>
							<li><a href="#" title="<?php _e('Duplicate', 'themify') ?>" class="themify_module_duplicate"><?php _e('Duplicate', 'themify') ?></a></li>
							<li><a href="#" title="<?php _e('Delete', 'themify') ?>" class="themify_module_delete"><?php _e('Delete', 'themify') ?></a></li>
						</ul>
					</li>
				</ul>
				<div class="front_mod_settings mod_settings_<?php echo esc_attr( $mod_name ); ?>" data-mod-name="<?php echo esc_attr( $mod_name ); ?>">
					<script type="text/json"><?php echo json_encode( $this->clean_json_bad_escaped_char( $mod_settings ) ); ?></script>
				</div>
			</div>
			<div class="themify_builder_data_mod_name"><?php echo Themify_Builder_model::get_module_name( $mod_name ); ?></div>
			<?php
		}

		/**
		 * Add Builder body class
		 * @param $classes
		 * @return mixed|void
		 */
		function body_class( $classes ) {
			if ( Themify_Builder_Model::is_frontend_editor_page() ) 
				$classes[] = 'frontend';

			// return the $classes array
			return apply_filters( 'themify_builder_body_class', $classes );
		}

		/**
		 * Just print the shortcode text instead of output html
		 * @param array $array
		 * @return array
		 */
		function return_text_shortcode( $array ) {
			if ( count( $array ) > 0 ) {
				foreach ( $array as $key => $value ) {
					if( is_array( $value ) ) {
						$this->return_text_shortcode( $value );
					} else {
						$array[ $key ] = str_replace( "[", "&#91;", $value );
						$array[ $key ] = str_replace( "]", "&#93;", $value ); 
					}
				}
			} else {
				$array = array();
			}
			return $array;
		}

		/**
		 * Clean bad escape char for json
		 * @param array $array 
		 * @return array
		 */
		function clean_json_bad_escaped_char( $array ) {
			if ( count( $array ) > 0 ) {
				foreach ( $array as $key => $value ) {
					if( is_array( $value ) ) {
						$this->clean_json_bad_escaped_char( $value );
					} else {
						$array[ $key ] = str_replace( "<wbr />", "<wbr>", $value ); 
					}
				}
			} else {
				$array = array();
			}
			return $array;
		}

		/**
		 * Retrieve builder templates
		 * @param $template_name
		 * @param array $args
		 * @param string $template_path
		 * @param string $default_path
		 * @param bool $echo
		 * @return string
		 */
		function retrieve_template( $template_name, $args = array(), $template_path = '', $default_path = '', $echo = true ) {
			ob_start();
			$this->get_template( $template_name, $args, $template_path = '', $default_path = '' );
			if ( $echo )
				echo ob_get_clean();
			else
				return ob_get_clean();
		}

		/**
		 * Get template builder
		 * @param $template_name
		 * @param array $args
		 * @param string $template_path
		 * @param string $default_path
		 */
		function get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
			if ( $args && is_array( $args ) )
				extract( $args );

			$located = $this->locate_template( $template_name, $template_path, $default_path );

			include( $located );
		}

		/**
		 * Locate a template and return the path for inclusion.
		 *
		 * This is the load order:
		 *
		 *		yourtheme		/	$template_path	/	$template_name
		 *		$default_path	/	$template_name
		 */
		function locate_template( $template_name, $template_path = '', $default_path = '' ) {
			$template = '';
			foreach( $this->get_directory_path( 'templates' ) as $dir ) {
				if( is_file( $dir . $template_name ) ) {
					$template = $dir . $template_name;
				}
			}

			// Get default template
			if ( ! $template )
				$template = $default_path . $template_name;

			// Return what we found
			return apply_filters( 'themify_builder_locate_template', $template, $template_name, $template_path );
		}

		/**
		 * Get template for module
		 * @param $mod
		 * @param bool $echo
		 * @param bool $wrap
		 * @param null $class
		 * @param array $identifier
		 * @return bool|string
		 */
		function get_template_module( $mod, $builder_id = 0, $echo = true, $wrap = true, $class = null, $identifier = array() ) {
			/* allow addons to control the display of the modules */
			$display = apply_filters( 'themify_builder_module_display', true, $mod, $builder_id, $identifier );
			if( false === $display ) {
				return false;
			}

			$output = '';
			$mod['mod_name'] = isset( $mod['mod_name'] ) ? $mod['mod_name'] : '';
			$mod['mod_settings'] = isset( $mod['mod_settings'] ) ? $mod['mod_settings'] : array();
			
			$mod_id = $mod['mod_name'] . '-' . $builder_id . '-' . implode( '-', $identifier );
			$output .= PHP_EOL; // add line break

			// check whether module active or not
			if ( ! Themify_Builder_Model::check_module_active( $mod['mod_name'] ) ) 
				return false;

			if ( $wrap ) {
				ob_start(); ?>
				<div class="themify_builder_module_front clearfix module-<?php echo esc_attr( $mod['mod_name'] ); ?> active_module <?php echo esc_attr( $class ); ?>" data-module-name="<?php echo esc_attr( $mod['mod_name'] ); ?>">
				<div class="themify_builder_module_front_overlay"></div>
				<?php themify_builder_edit_module_panel( $mod['mod_name'], $mod['mod_settings'] ); ?>
				<?php
				$output .= ob_get_clean();
			}
			$output .= $this->retrieve_template( 'template-'.$mod['mod_name'].'.php', array(
				'module_ID' => $mod_id,
				'mod_name' => $mod['mod_name'],
				'builder_id' => $builder_id,
				'mod_settings' => ( isset( $mod['mod_settings'] ) ? $mod['mod_settings'] : '' )
			),'', '', false );
			$style_id = '.themify_builder .' . $mod_id;
			$output .= $this->get_custom_styling( $style_id, $mod['mod_name'], $mod['mod_settings'] );

			if ( $wrap ) 
				$output .= '</div>';

			// add line break
			$output .= PHP_EOL;

			if ( $echo ) {
				echo $output;
			} else {
				return $output;
			}
		}

		/**
		 * Check whether theme loop template exist
		 * @param string $template_name 
		 * @param string $template_path 
		 * @return boolean
		 */
		function is_loop_template_exist( $template_name, $template_path ) {
			$template = locate_template(
				array(
					trailingslashit( $template_path ) . $template_name
				)
			);

			if ( ! $template ) {
				return false;
			} else {
				return true;
			}
		}

		/**
		 * Get checkbox data
		 * @param $setting
		 * @return string
		 */
		function get_checkbox_data( $setting ) {
			return implode( ' ', explode( '|', $setting ) );
		}

		/**
		 * Return only value setting
		 * @param $string 
		 * @return string
		 */
		function get_param_value( $string ) {
			$val = explode( '|', $string );
			return $val[0];
		}

		/**
		 * Get custom menus
		 * @param int $term_id
		 */
		function get_custom_menus( $term_id ) {
			$menu_list = '';
			ob_start();
			wp_nav_menu( array( 'menu' => $term_id ) );
			$menu_list .= ob_get_clean();

			return $menu_list;
		}

		/**
		 * Display an additional column in categories list
		 * @since 1.1.8
		 */
		function taxonomy_header( $cat_columns ) {
			$cat_columns['cat_id'] = 'ID';
			return $cat_columns;
		}

		/**
		 * Display ID in additional column in categories list
		 * @since 1.1.8
		 */
		function taxonomy_column_id( $null, $column, $termid ){
			return $termid;
		}

		/**
		 * Includes this custom post to array of cpts managed by Themify
		 * @param Array
		 * @return Array
		 */
		function extend_post_types( $types ) {
			return array_merge( $types, $this->registered_post_types );
		}

		/**
		 * Push the registered post types to object class
		 * @param $type
		 */
		function push_post_types( $type ) {
			array_push( $this->registered_post_types, $type );
		}

		/**
		 * Detect mobile browser
		 */
		function isMobile() {
			return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
		}

		/**
		 * Get images from gallery shortcode
		 * @return object
		 */
		function get_images_from_gallery_shortcode( $shortcode ) {
			preg_match( '/\[gallery.*ids=.(.*).\]/', $shortcode, $ids );
			$image_ids = explode( ",", $ids[1] );
			$orderby = $this->get_gallery_param_option( $shortcode, 'orderby' );
			$orderby = $orderby != '' ? $orderby : 'post__in';
			$order = $this->get_gallery_param_option( $shortcode, 'order' );
			$order = $order != '' ? $order : 'ASC';

			// Check if post has more than one image in gallery
			return get_posts( array(
				'post__in' => $image_ids,
				'post_type' => 'attachment',
				'post_mime_type' => 'image',
				'numberposts' => -1,
				'orderby' => $orderby,
				'order' => $order
			) );
		}

		/**
		 * Get gallery shortcode options
		 * @param $shortcode
		 * @param $param
		 */
		function get_gallery_param_option( $shortcode, $param = 'link' ) {
			if ( $param == 'link' ) {
				preg_match( '/\[gallery .*?(?=link)link=.([^\']+)./si', $shortcode, $out );
			} elseif ( $param == 'order' ) {
				preg_match( '/\[gallery .*?(?=order)order=.([^\']+)./si', $shortcode, $out );	
			} elseif ( $param == 'orderby' ) {
				preg_match( '/\[gallery .*?(?=orderby)orderby=.([^\']+)./si', $shortcode, $out );	
			} elseif ( $param == 'columns' ) {
				preg_match( '/\[gallery .*?(?=columns)columns=.([^\']+)./si', $shortcode, $out );	
			}
			
			$out = isset($out[1]) ? explode( '"', $out[1] ) : array('');
			return $out[0];
		}

		/**
		 * Reset builder query
		 * @param $action
		 */
		function reset_builder_query( $action = 'reset' ) {
			if ( 'reset' == $action ) {
				remove_filter( 'the_content', array( &$this, 'builder_show_on_front' ), 11 );
			} elseif ( 'restore' == $action ) {
				add_filter( 'the_content', array( &$this, 'builder_show_on_front' ), 11 );
			}
		}

		/**
		 * Check whether image script is in use or not
		 * @return boolean
		 */
		function is_img_php_disabled() {
			if ( themify_check( 'setting-img_settings_use' ) ) {
				return true;
			} else{
				return false;
			}
		}

		/**
		 * Checks whether the url is an img link, youtube, vimeo or not.
		 * @param string $url
		 * @return bool
		 */
		function is_img_link( $url ) {
			$parsed_url = parse_url( $url );
			$pathinfo = isset( $parsed_url['path'] ) ? pathinfo( $parsed_url['path'] ) : '';
			$extension = isset( $pathinfo['extension'] ) ? strtolower( $pathinfo['extension'] ) : '';

			$image_extensions = array('png', 'jpg', 'jpeg', 'gif');

			if ( in_array( $extension, $image_extensions ) || stripos( 'youtube', $url ) || stripos( 'vimeo', $url ) ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Get query page
		 */
		function get_paged_query() {
			global $wp;
			$page = 1;
			$qpaged = get_query_var( 'paged' );
			if ( ! empty( $qpaged ) ) {
				$page = $qpaged;
			} else {
				$qpaged = wp_parse_args( $wp->matched_query );
				if ( isset( $qpaged['paged'] ) && $qpaged['paged'] > 0 ) {
					$page = $qpaged['paged'];
				}
			}
			return $page;
		}

		/**
		 * Returns page navigation
		 * @param string Markup to show before pagination links
		 * @param string Markup to show after pagination links
		 * @param object WordPress query object to use
		 * @return string
		 */
		function get_pagenav( $before = '', $after = '', $query = false ) {
			global $wpdb, $wp_query;
			
			if( false == $query ){
				$query = $wp_query;
			}
			$request = $query->request;
			$posts_per_page = intval(get_query_var('posts_per_page'));
			$paged = intval($this->get_paged_query());
			$numposts = $query->found_posts;
			$max_page = $query->max_num_pages;
			$out = '';
		
			if(empty($paged) || $paged == 0) {
				$paged = 1;
			}
			$pages_to_show = apply_filters('themify_filter_pages_to_show', 5);
			$pages_to_show_minus_1 = $pages_to_show-1;
			$half_page_start = floor($pages_to_show_minus_1/2);
			$half_page_end = ceil($pages_to_show_minus_1/2);
			$start_page = $paged - $half_page_start;
			if($start_page <= 0) {
				$start_page = 1;
			}
			$end_page = $paged + $half_page_end;
			if(($end_page - $start_page) != $pages_to_show_minus_1) {
				$end_page = $start_page + $pages_to_show_minus_1;
			}
			if($end_page > $max_page) {
				$start_page = $max_page - $pages_to_show_minus_1;
				$end_page = $max_page;
			}
			if($start_page <= 0) {
				$start_page = 1;
			}
		
			if ($max_page > 1) {
				$out .=  $before.'<div class="pagenav clearfix">';
				if ($start_page >= 2 && $pages_to_show < $max_page) {
					$first_page_text = "&laquo;";
					$out .=  '<a href="'.esc_url( get_pagenum_link() ).'" title="'.esc_attr( $first_page_text ).'" class="number">'.$first_page_text.'</a>';
				}
				if($pages_to_show < $max_page)
					$out .= get_previous_posts_link('&lt;');
				for($i = $start_page; $i  <= $end_page; $i++) {
					if($i == $paged) {
						$out .=  ' <span class="number current">'.$i.'</span> ';
					} else {
						$out .=  ' <a href="'.esc_url( get_pagenum_link($i) ).'" class="number">'.$i.'</a> ';
					}
				}
				if($pages_to_show < $max_page)
					$out .= get_next_posts_link('&gt;');
				if ($end_page < $max_page) {
					$last_page_text = "&raquo;";
					$out .=  '<a href="'.esc_url( get_pagenum_link($max_page) ).'" title="'.esc_attr( $last_page_text ).'" class="number">'.$last_page_text.'</a>';
				}
				$out .=  '</div>'.$after;
			}
			return $out;
		}

		/**
		 * Reset builder filter before template content render
		 */
		function do_reset_before_template_content_render(){
			//$this->reset_builder_query();
		}

		/**
		 * Reset builder filter after template content render
		 */
		function do_reset_after_template_content_render(){
			//$this->reset_builder_query('restore');
		}

		/**
		 * Check is plugin active
		 */
		function builder_is_plugin_active( $plugin ) {
			return in_array( $plugin, apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
		}

		/**
		 * Include builder in search
		 * @param string $where 
		 * @return string
		 */
		function do_search( $where ){
			if ( ! is_admin() ) {
				if( is_search() ) {
					global $wpdb;
					$query = get_search_query();
					if ( method_exists( $wpdb, 'esc_like' ) ) {
						$query = $wpdb->esc_like( $query );
					} else {
						/**
						 * If this is not WP 4.0 or above, use old method to escape db query.
						 * @since 2.0.2
						 */
						$do = 'like'; $it = 'escape';
						$query = call_user_func( $do . '_' . $it, $query );
					}
					$types = Themify_Builder_Model::get_post_types();

					$where .= " OR {$wpdb->posts}.ID IN (
							SELECT {$wpdb->postmeta}.post_id FROM {$wpdb->posts}, {$wpdb->postmeta} WHERE {$wpdb->postmeta}.meta_key = '{$this->meta_key}'
							AND {$wpdb->postmeta}.meta_value LIKE '%$query%' AND {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
							AND {$wpdb->posts}.post_type IN ('". implode("', '", $types ) ."'))";
				}
			}
			return $where;
		}

		/**
		 * Builder Import Lightbox
		 */
		function builder_import_ajaxify(){
			check_ajax_referer( 'tfb_load_nonce', 'nonce' );

			$type = $_POST['type'];
			$data = array();

			if ( 'post' == $type ) {
				$post_types = get_post_types( array('_builtin' => false) );
				$data[] = array(
					'post_type' => 'post',
					'label' => __('Post', 'themify'),
					'items' => get_posts( array( 'posts_per_page' => -1, 'post_type' => 'post' ) )
				);
				foreach( $post_types as $post_type ){
					$data[] = array(
						'post_type' => $post_type,
						'label' => ucfirst( $post_type ),
						'items' => get_posts( array( 'posts_per_page' => -1, 'post_type' => $post_type ) )
					);
				}

			} else if( 'page' == $type ){
				$data[] = array(
					'post_type' => 'page',
					'label' => __('Page', 'themify'),
					'items' => get_pages()
				);
			} else {
				die();
			}

			include_once THEMIFY_BUILDER_INCLUDES_DIR . '/themify-builder-import.php';
			die();
		}

		/**
		 * Process import builder
		 */
		function builder_import_submit_ajaxify(){
			check_ajax_referer( 'tfb_load_nonce', 'nonce' );
			parse_str( $_POST['data'], $imports );
			$import_to = (int) $_POST['importTo'];
			
			if ( count( $imports ) > 0 && is_array( $imports ) ) {
				$meta_values = array();

				// get current page builder data
				$current_builder_data = get_post_meta( $import_to, $this->meta_key, true );
				$current_builder_data = stripslashes_deep( maybe_unserialize( $current_builder_data ) );
				if ( count( $current_builder_data ) > 0 ) {
					$meta_values[] = $current_builder_data;
				}

				foreach( $imports as $post_type => $post_id ) {
					if ( empty( $post_id ) || $post_id == 0 ) continue;

					$builder_data = get_post_meta( $post_id, $this->meta_key, true );
					$builder_data = stripslashes_deep( maybe_unserialize( $builder_data ) );
					$meta_values[] = $builder_data;
				}

				if ( count( $meta_values ) > 0 ) {
					$result = array();
					foreach( $meta_values as $meta ) {
						$result = array_merge( $result, (array) $meta );
					}
					update_post_meta( $import_to, $this->meta_key, $result );
				}
			}

			die();
		}

		/**
		 * Output row styling style
		 * @param int $builder_id 
		 * @param array $row 
		 * @return string
		 */
		function render_row_styling( $builder_id, $row ) {
			$row['styling'] = isset( $row['styling'] ) ? $row['styling'] : '';
			$row['row_order'] = isset( $row['row_order'] ) ? $row['row_order'] : '';
			$settings = $row['styling'];
			$style_id = '.themify_builder_content-' . $builder_id . ' > .module_row_' . $row['row_order'];
			echo $this->get_custom_styling( $style_id, 'row', $settings );  
		}

		function get_custom_styling( $style_id, $mod_name, $settings, $array = false ) {
			global $themify;

			if ( ! isset( $themify->builder_google_fonts ) ) {
				$themify->builder_google_fonts = '';
			}

			if( 'row' == $mod_name
				|| ( isset( Themify_Builder_model::$modules[ $mod_name ] ) && is_array( Themify_Builder_model::$modules[ $mod_name ]->get_css_selectors() ) ) // legacy module def support
			) {
				return $this->get_custom_styling_legacy( $style_id, $mod_name, $settings, $array );
			}

			$styling = Themify_Builder_model::$modules[ $mod_name ]->get_styling();
			$rules = $this->make_styling_rules( $styling, $settings );

			if( ! empty( $rules ) ) {
				$css = array();
				foreach( $rules as $value ) {
					$css[$value['selector']] = isset( $css[$value['selector']] ) ? $css[$value['selector']] : '';
					if( in_array( $value['prop'], array( 'background-color', 'color', 'border-top-color', 'border-bottom-color', 'border-left-color', 'border-right-color' ) ) ) {
						$css[$value['selector']] .= sprintf( '%s: %s; ', $value['prop'], $this->get_rgba_color( $value['value'] ) );
					} elseif( $value['prop'] == 'font-family' && $value['value'] != 'default' ) {
						if ( ! in_array( $value['value'], themify_get_web_safe_font_list( true ) ) ) {
							$themify->builder_google_fonts .= str_replace( ' ', '+', $value['value'] .'|' );
						}
						$css[$value['selector']] .= sprintf( 'font-family: %s; ', $value['value'] );
					} elseif( in_array( $value['prop'], array( 'font-size', 'line-height', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left', 'border-top-width', 'border-right-width', 'border-bottom-width', 'border-left-width' ) ) ) {
						$unit = isset( $settings[$value['id'] . '_unit'] ) ? $settings[$value['id'] . '_unit'] : 'px';
						$css[$value['selector']] .= sprintf( '%s: %s%s; ', $value['prop'], $value['value'], $unit );
					} elseif( in_array( $value['prop'], array( 'text-decoration', 'text-align', 'background-repeat', 'background-position', 'border-top-style', 'border-right-style', 'border-bottom-style', 'border-left-style' ) ) ) {
						$css[$value['selector']] .= sprintf( '%s: %s; ', $value['prop'], $value['value'] );
					} elseif( $value['prop'] == 'background-image' ) {
						$css[$value['selector']] .= sprintf( '%s: url("%s"); ', $value['prop'], $value['value'] );
					}
				}
			}

			$output = '';
			if( ! empty( $css ) ) {
				$output .= '<style>';
				foreach( $css as $selector => $defs ) {
					$output .= "{$style_id}{$selector } { {$defs} } \n";
				}
				$output .= '</style>';
			}

			return $output;
		}

		function make_styling_rules( $def, $settings ) {
			$result = array();
			if( empty( $def ) )
				return $result;

			foreach( $def as $option ) {
				if( $option['type'] == 'multi' ) {
					$result = array_merge( $result, $this->make_styling_rules( $option['fields'], $settings ) );
				} elseif( $option['type'] == 'tabs' ) {
					foreach( $option['tabs'] as $tab ) {
						$result = array_merge( $result, $this->make_styling_rules( $tab['fields'], $settings ) );
					}
				} elseif( isset( $option['prop'] ) && isset( $settings[$option['id']] ) ) {
					foreach( (array) $option['selector'] as $selector ) {
						$result[] = array(
							'id' => $option['id'],
							'prop' => $option['prop'],
							'selector' => $selector,
							'value' => $settings[$option['id']]
						);
					}
				}
			}

			return $result;
		}

		/**
		 * Get custom style
		 * @param string $style_id 
		 * @param string $mod_name 
		 * @param array $settings 
		 * @param boolean $array 
		 * @return string|array
		 */
		function get_custom_styling_legacy( $style_id, $mod_name, $settings, $array = false ) {
			global $themify;

			if ( ! isset( $themify->builder_google_fonts ) ) {
				$themify->builder_google_fonts = '';
			}

			$rules_arr = array(
				'font_size' => array(
					'prop' => 'font-size',
					'key' => array('font_size', 'font_size_unit')
				),
				'font_family' => array(
					'prop' => 'font-family',
					'key' => 'font_family'
				),
				'line_height' => array(
					'prop' => 'line-height',
					'key' => array('line_height', 'line_height_unit')
				),
				'text_align' => array(
					'prop' => 'text-align',
					'key' => 'text_align'
				),
				'color' => array(
					'prop' => 'color',
					'key' => 'font_color'
				),
				'link_color' => array(
					'prop' => 'color',
					'key' => 'link_color'
				),
				'text_decoration' => array(
					'prop' => 'text-decoration',
					'key' => 'text_decoration'
				),
				'background_color' => array(
					'prop' => 'background-color',
					'key' => 'background_color'
				),
				'background_image' => array(
					'prop' => 'background-image',
					'key' => 'background_image'
				),
				'background_repeat' => array(
					'prop' => 'background-repeat',
					'key' => 'background_repeat'
				),
				'background_position' => array(
					'prop' => 'background-position',
					'key' => array( 'background_position_x', 'background_position_y' )
				),
				'padding' => array(
					'prop' => 'padding',
					'key' => array( 'padding_top', 'padding_right', 'padding_bottom', 'padding_left' )
				),
				'margin' => array(
					'prop' => 'margin',
					'key' => array( 'margin_top', 'margin_right', 'margin_bottom', 'margin_left' )
				),
				'border_top' => array(
					'prop' => 'border-top',
					'key' => array( 'border_top_color', 'border_top_width', 'border_top_style' )
				),
				'border_right' => array(
					'prop' => 'border-right',
					'key' => array( 'border_right_color', 'border_right_width', 'border_right_style' )
				),
				'border_bottom' => array(
					'prop' => 'border-bottom',
					'key' => array( 'border_bottom_color', 'border_bottom_width', 'border_bottom_style' )
				),
				'border_left' => array(
					'prop' => 'border-left',
					'key' => array( 'border_left_color', 'border_left_width', 'border_left_style' )
				)
			);
			

			if ( $mod_name != 'row' ) {
				$styles_selector = Themify_Builder_Model::$modules[ $mod_name ]->get_css_selectors();
			} else {
				$styles_selector = array(
					'.module_row' => array(
						'background_image', 'background_color', 'font_family', 'font_size', 'line_height', 'text_align', 'color', 'padding', 'margin', 'border_top', 'border_right', 'border_bottom', 'border_left'
					),
					'.module_row a' => array(
						'link_color', 'text_decoration'
					),
					'.module_row h1' => array( 'color' ),
					'.module_row h2' => array( 'color' ),
					'.module_row h3:not(.module-title)' => array( 'color' ),
					'.module_row h4' => array( 'color' ),
					'.module_row h5' => array( 'color' ),
					'.module_row h6' => array( 'color' ),
				);
			}
			$rules = array();
			$css = array();
			$style = '';

			foreach( $styles_selector as $selector => $properties ) {
				$property_arr = array();
				foreach( $properties as $property ) {
					array_push( $property_arr, $rules_arr[ $property ] );
				}
				$rules[ $style_id . $selector ] = $property_arr;
			}

			foreach ( $rules as $selector => $property ) {
				foreach ( $property as $val ) {
					$prop = $val['prop'];
					$key = $val['key'];

					if ( is_array( $key ) ) {
						if ( $prop == 'font-size' && isset( $settings[ $key[0] ] ) && '' != $settings[ $key[0] ] ) {
							$css[ $selector ][ $prop ] = $prop . ': ' . $settings[ $key[0] ] . $settings[ $key[1] ];
						} else if ( $prop == 'line-height' && isset( $settings[ $key[0] ] ) && '' != $settings[ $key[0] ] ) {
							$css[ $selector ][ $prop ] = $prop . ': ' . $settings[ $key[0] ] . $settings[ $key[1] ];
						} else if( $prop == 'background-position' && isset( $settings[ $key[0] ] ) && '' != $settings[ $key[0] ] ) {
							$css[ $selector ][ $prop ] = $prop . ': ' . $settings[ $key[0] ] . ' ' . $settings[ $key[1] ];
						} else if( $prop == 'padding' ) {
							$padding['top'] = isset( $settings[ $key[0] ]) && '' != $settings[ $key[0] ] ? $settings[ $key[0] ]  : '';
							$padding['right'] = isset( $settings[ $key[1] ]) && '' != $settings[ $key[1] ] ? $settings[ $key[1] ]  : '';
							$padding['bottom'] = isset( $settings[ $key[2] ]) && '' != $settings[ $key[2] ] ? $settings[ $key[2] ]  : '';
							$padding['left'] = isset( $settings[ $key[3] ]) && '' != $settings[ $key[3] ] ? $settings[ $key[3] ]  : '';
							
							foreach( $padding as $k => $v ) {
								if ( '' == $v ) continue;
								$unit = isset( $settings["padding_{$k}_unit"] ) ? $settings["padding_{$k}_unit"] : 'px';
								$css[ $selector ][ 'padding-' . $k ] = 'padding-'. $k .' : ' . $v . $unit;
							}

						} else if( $prop == 'margin' ) {
							$margin['top'] = isset( $settings[ $key[0] ]) && '' != $settings[ $key[0] ] ? $settings[ $key[0] ]  : '';
							$margin['right'] = isset( $settings[ $key[1] ]) && '' != $settings[ $key[1] ] ? $settings[ $key[1] ]  : '';
							$margin['bottom'] = isset( $settings[ $key[2] ]) && '' != $settings[ $key[2] ] ? $settings[ $key[2] ]  : '';
							$margin['left'] = isset( $settings[ $key[3] ]) && '' != $settings[ $key[3] ] ? $settings[ $key[3] ]  : '';
							
							foreach( $margin as $k => $v ) {
								if ( '' == $v ) continue;
								$unit = isset( $settings["margin_{$k}_unit"] ) ? $settings["margin_{$k}_unit"] : 'px';
								$css[ $selector ][ 'margin-' . $k ] = 'margin-'. $k .' : ' . $v . $unit;
							}

						} else if ( in_array( $prop, array('border-top', 'border-right', 'border-bottom', 'border-left' ) ) ) {
							$border['color'] = isset( $settings[ $key[0] ] ) && '' != $settings[ $key[0] ] ? '#' . $settings[ $key[0] ] : '' ;
							$border['width'] = isset( $settings[ $key[1] ] ) && '' != $settings[ $key[1] ] ? $settings[ $key[1] ] . 'px' : '';
							$border['style'] = isset( $settings[ $key[2] ] ) && '' != $settings[ $key[2] ] ? $settings[ $key[2] ] : '' ;
							$css[ $selector ][ $prop ] = $this->build_color_props( array(
									'color_opacity' => $border['color'],
									'property' => $prop,
									'border_width'  => $border['width'],
									'border_style'  => $border['style'],
								)
							);
							
							if ( empty( $border['color'] ) && empty( $border['width'] ) && empty( $border['style'] ) ) 
								unset( $css[ $selector ][ $prop ] );
						}
					} elseif ( isset( $settings[ $key ] ) && 'default' != $settings[ $key ] && '' != $settings[ $key ] ) {
						if ( $prop == 'color' || stripos( $prop, 'color' ) ) {
							$css[ $selector ][ $prop ] = $this->build_color_props( array(
									'color_opacity' => $settings[ $key ],
									'property' => $prop,
								)
							);
						}
						elseif ( $prop == 'background-image' && 'default' != $settings[ $key ] ) {
							$css[ $selector ][ $prop ] = $prop .': url(' . $settings[ $key ] . ')';
							if ( isset( $settings['background_type'] ) && 'video' == $settings['background_type'] ) {
								$css[ $selector ][ $prop ] .= ";\n\tbackground-size: cover";
							}
						}
						elseif ( $prop == 'font-family' ) {
							$font = $settings[ $key ];
							$css[ $selector ][ $prop ] = $prop .': '. $font;
							if ( ! in_array( $font, themify_get_web_safe_font_list( true ) ) ) {
								$themify->builder_google_fonts .= str_replace( ' ', '+', $font.'|' );
							}
						}
						else {
							$css[ $selector ][ $prop ] = $prop .': '. $settings[ $key ];
						}
					}

				}

				if ( ! empty( $css[ $selector ] ) ) {
					$style .= "$selector {\n\t" . implode( ";\n\t", $css[ $selector ] ) . "\n}\n";
				}
			}

			if ( ! $array ) {
				if ( '' != $style ) {
					return "\n<!-- $style_id Style -->\n<style>\n$style</style>\n<!-- End $style_id Style -->\n";
				}
			} else if ( $array ) {
				return $css;
			}

		}

		/**
		 * Outputs color for the logo in text mode since it's needed for the <a>.
		 *
		 * @since 1.9.6
		 *
		 * @param array $args
		 * @return string
		 */
		function build_color_props( $args = array() ) {
			$args = wp_parse_args( $args, array(
				'color_opacity' => '',
				'property' => 'color',
			    'border_width' => '1px',
			    'border_style' => 'solid',
			) );
			// Strip any lingering hashes just in case
			$args['color_opacity'] = str_replace( '#', '', $args['color_opacity'] );
			// Separator between color and opacity
			$sep = '_';

			if ( false !== stripos( $args['color_opacity'], $sep ) ) {
				// If it's the new color+opacity, an underscore separates color from opacity
				$all = explode( $sep, $args['color_opacity'] );
				$color = isset( $all[0] ) ? $all[0] : '';
				$opacity = isset( $all[1] ) ? $all[1] : '';
			} else {
				// If it's the traditional, it's a simple color
				$color = $args['color_opacity'];
				$opacity = '';
			}
			$element_props = '';
			if ( '' != $color ) {
				// Setup opacity value or solid
				$opacity = ( '' != $opacity ) ? $opacity : '1';
				if ( false !== stripos( $args['property'], 'border' ) ) {
					// It's a border property, a composite of border size style
					$element_props .= "{$args['property']}: #$color {$args['border_width']} {$args['border_style']};";
					if ( '1' != $opacity ) {
						$element_props .= "\n\t{$args['property']}: rgba(" . $this->hex2rgb( $color ) . ",  $opacity) {$args['border_width']} {$args['border_style']}";
					}
				} else {
					// It's either background-color or color, a simple color
					$element_props .= "{$args['property']}: #$color;";
					if ( '1' != $opacity ) {
						$element_props .= "\n\t{$args['property']}: rgba(" . $this->hex2rgb( $color ) . ", $opacity)";
					}
				}
			}
			return $element_props;
		}

		/**
		 * Converts color in hexadecimal format to RGB format.
		 *
		 * @since 1.9.6
		 *
		 * @param string $hex Color in hexadecimal format.
		 * @return string Color in RGB components separated by comma.
		 */
		function hex2rgb( $hex ) {
			$hex = str_replace( "#", "", $hex );

			if ( strlen( $hex ) == 3 ) {
				$r = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
				$g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
				$b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
			} else {
				$r = hexdec( substr( $hex, 0, 2 ) );
				$g = hexdec( substr( $hex, 2, 2 ) );
				$b = hexdec( substr( $hex, 4, 2 ) );
			}
			return implode( ',', array( $r, $g, $b ) );
		}

		/**
		 * Get RGBA color format from hex color
		 *
		 * @return string
		 */
		function get_rgba_color( $color ) {
			$color = explode( '_', $color );
			$opacity = isset( $color[1] ) ? $color[1] : '1';
			return 'rgba(' . $this->hex2rgb( $color[0] ) . ', ' . $opacity . ')';
		}

		/**
		 * Get google fonts
		 */
		function get_custom_google_fonts() {
			global $themify;
			$fonts = array();

			if ( ! isset( $themify->builder_google_fonts ) || '' == $themify->builder_google_fonts ) return $fonts;
			$themify->builder_google_fonts = substr( $themify->builder_google_fonts, 0, -1 );
			$fonts = explode( '|', $themify->builder_google_fonts );
			return $fonts;
		}

		/**
		 * Add filter to module content
		 * @param string $content 
		 * @return string
		 */
		function the_module_content( $content ) {
			global $wp_embed;
			$content = $wp_embed->run_shortcode( $content );
			$content = do_shortcode( shortcode_unautop( $content ) );
			$content = $this->autoembed_adjustments( $content );
			$content = $wp_embed->autoembed( $content );
			return $content;
		}

		/**
		 * Adjust autoembed filter
		 * @param string $content 
		 * @return string
		 */
		function autoembed_adjustments( $content ){
			$pattern = '|<p>\s*(https?://[^\s"]+)\s*</p>|im';	// pattern to check embed url
			$to      = '<p>'. PHP_EOL .'$1'. PHP_EOL .'</p>';	// add line break 
			$content = preg_replace( $pattern, $to, $content );
			return $content;
		}

		/**
		 * Add custom Themify Builder button after Add Media btn
		 * @param string $context 
		 * @return string
		 */
		function add_custom_switch_btn( $context ) {
			global $pagenow;
			$post_types = themify_post_types();
			if ( 'post.php' == $pagenow && in_array( get_post_type(), $post_types ) ) {
				$context .= sprintf( '<a href="#" class="button themify_builder_switch_btn">%s</a>', __('Themify Builder', 'themify') );
			}
			return $context;
		}

		/**
		 * Get template row
		 *
		 * @param array  $rows
		 * @param array  $row
		 * @param string $builder_id
		 * @param bool   $echo
		 *
		 * @return string
		 */
		public function get_template_row( $rows, $row, $builder_id, $echo = false ) {
			/* allow addons to control the display of the rows */
			$display = apply_filters( 'themify_builder_row_display', true, $row, $builder_id );
			if( false === $display ) {
				return false;
			}

			$row['row_order'] = isset( $row['row_order'] ) ? $row['row_order'] : uniqid();
			$row_classes = array( 'themify_builder_row', 'module_row', 'module_row_' . $row['row_order'], 'clearfix' );
			$class_fields = array( 'custom_css_row', 'background_repeat', 'animation_effect', 'row_width', 'row_height' );
			$row_gutter_class = isset( $row['gutter'] ) && ! empty( $row['gutter'] ) ? $row['gutter'] : 'gutter-default';

			// Set Gutter Class
			if ( '' != $row_gutter_class ) 
				$row_classes[] = $row_gutter_class;

			// Class for Scroll Highlight
			if ( isset( $row['styling'] ) && isset( $row['styling']['row_anchor'] ) && '' != $row['styling']['row_anchor'] ) {

				// Load styles and scripts registered in Themify_Builder::register_frontend_js_css()
				$GLOBALS['ThemifyBuilder']->load_templates_js_css( array( 'scroll' => true ) );

				$row_classes[] = 'tb_section-' . $row['styling']['row_anchor'];
			}

			// @backward-compatibility
			if( ! isset( $row['styling']['background_type'] ) && isset( $row['styling']['background_video'] ) && '' != $row['styling']['background_video'] ) {
				$row['styling']['background_type'] = 'video';
			}

			// Fullwidth video
			$is_type_video = isset( $row['styling']['background_type'] ) && 'video' == $row['styling']['background_type'];
			$has_video = isset( $row['styling']['background_video'] ) && ! empty( $row['styling']['background_video'] );

			foreach( $class_fields as $field ) {
				if ( isset( $row['styling'][ $field ] ) && ! empty( $row['styling'][ $field ] ) ) {
					if ( 'animation_effect' == $field ) {
						$row_classes[] = 'wow';
					}
					$row_classes[] = $row['styling'][ $field ];
				}
			}
			$row_classes = apply_filters( 'themify_builder_row_classes', $row_classes, $row, $builder_id );

			if ( $is_type_video && $has_video ) {
				// Load styles and scripts registered in Themify_Builder::register_frontend_js_css()
				$GLOBALS['ThemifyBuilder']->load_templates_js_css( array( 'fullvideo' => true ) );
			}

			$output = PHP_EOL; // add line break
			ob_start();
			?>
			<!-- module_row -->
			<div data-gutter="<?php echo esc_attr( $row_gutter_class ); ?>" class="<?php echo esc_attr( implode( ' ', $row_classes ) ); ?>" <?php if ( $is_type_video && $has_video ) : echo 'data-fullwidthvideo="' . esc_url( $row['styling']['background_video'] ) . '"'; endif; ?>>

				<?php if ( $this->frontedit_active ): ?>
				<div class="themify_builder_row_top">
					<div class="row_menu">
						<div class="menu_icon">
						</div>
						<ul class="themify_builder_dropdown">
							<li><a href="#" class="themify_builder_option_row"><?php _e('Options', 'themify') ?></a></li>
							<li><a href="#" class="themify_builder_duplicate_row"><?php _e('Duplicate', 'themify') ?></a></li>
							<li><a href="#" class="themify_builder_delete_row"><?php _e('Delete', 'themify') ?></a></li>
						</ul>
					</div>
					<!-- /row_menu -->
					
					<?php themify_builder_grid_lists( 'row', $row_gutter_class ); ?>

					<div class="toggle_row"></div><!-- /toggle_row -->
				</div>
				<!-- /row_top -->
				<?php endif; // builder edit active ?>

				<?php
				if( isset( $row['styling']['cover_color'] ) || isset( $row['styling']['cover_color_hover'] ) ) {
					$cover_color = ( $row['styling']['cover_color'] != '' ) ? 'style="background-color: ' . esc_attr( $this->get_rgba_color( $row['styling']['cover_color'] ) ) . '" data-color="'. esc_attr( $this->get_rgba_color( $row['styling']['cover_color'] ) ) .'"' : '';
					$cover_hover_color = ( $row['styling']['cover_color_hover'] != '' ) ? 'data-hover-color="' . esc_attr( $this->get_rgba_color( $row['styling']['cover_color_hover'] ) ) . '"' : '';
					?>
					<div class="builder_row_cover" <?php echo "$cover_color $cover_hover_color"; ?>></div>
				<?php } ?>

				<?php
				// Background Slider
				if ( isset( $row['styling']['background_slider'] ) && ! empty( $row['styling']['background_slider'] ) && 'slider' == $row['styling']['background_type'] ) :

					if ( $images = $this->get_images_from_gallery_shortcode( $row['styling']['background_slider'] ) ) :
						$bgmode = isset( $row['styling']['background_slider_mode'] ) && ! empty( $row['styling']['background_slider_mode'] ) ? $row['styling']['background_slider_mode'] : 'fullcover';
						?>

						<div id="row-slider-<?php echo esc_attr( $row['row_order'] ); ?>" class="row-slider"
							data-bgmode="<?php echo esc_attr( $bgmode ); ?>">
							<ul class="row-slider-slides clearfix">
								<?php
								$dot_i = 0;
								foreach ( $images as $image ) :
									$img_data = wp_get_attachment_image_src( $image->ID, 'large' ); ?>
									<li data-bg="<?php echo esc_url( $img_data[0] ); ?>">
										<a class="row-slider-dot" data-index="<?php echo esc_attr( $dot_i ); ?>"></a>
									</li>
									<?php
									$dot_i++;
								endforeach;
								?>
							</ul>
							<div class="row-slider-nav">
								<a class="row-slider-arrow row-slider-prev">&lsaquo;</a>
								<a class="row-slider-arrow row-slider-next">&rsaquo;</a>
							</div>
						</div>
						<!-- /.row-bgs -->
					<?php
					endif; // images

				endif; // background slider
						?>

				<div class="row_inner_wrapper">
					<div class="row_inner">

						<?php do_action('themify_builder_row_start', $builder_id, $row ); ?>

						<?php if ( $this->frontedit_active ): ?>
						<div class="themify_builder_row_content">	
						<?php endif; // builder edit active ?>

						<?php if ( isset( $row['cols'] ) && count( $row['cols'] ) > 0 ):
							
								$count = count( $row['cols'] );

								switch ( $count ) {
									
									case 4:
										$order_classes = array( 'first', 'second', 'third', 'last' );
									break;

									case 3:
										$order_classes = array( 'first', 'middle', 'last' );
									break;

									case 2:
										$order_classes = array( 'first', 'last' );
									break;

									default:
										$order_classes = array( 'first' );
									break;
								}

								foreach ( $row['cols'] as $cols => $col ):
									$columns_class = array();
									$grid_class = explode(' ', $col['grid_class'] );
									$dynamic_class = array( '', '' );
									$dynamic_class[0] = $this->frontedit_active ? 'themify_builder_col' : $order_classes[ $cols ];
									$dynamic_class[1] = $this->frontedit_active ? '' : 'tb-column';
									$columns_class = array_merge( $columns_class, $grid_class );
									foreach( $dynamic_class as $class ) {
										array_push( $columns_class, $class );
									}
									$columns_class = array_unique( $columns_class );
									// remove class "last" if the column is fullwidth
									if ( 1 == $count ) {
										if ( ( $key = array_search( 'last', $columns_class ) ) !== false) {
											unset( $columns_class[ $key ] );
										}
									}
									$print_column_classes = implode( ' ', $columns_class );
									?>

									<div class="<?php echo esc_attr( $print_column_classes ); ?>">
										<?php if($this->frontedit_active): ?>
										<div class="themify_module_holder">
											<div class="empty_holder_text"><?php _e('drop module here', 'themify') ?></div><!-- /empty module text -->
										<?php endif; ?>
											
											<?php
												if ( isset( $col['modules'] ) && count( $col['modules'] ) > 0 ) { 
													foreach ( $col['modules'] as $modules => $mod ) {

														if ( isset( $mod['mod_name']) ) {
															$w_wrap = ( $this->frontedit_active ) ? true : false;
															$w_class = ( $this->frontedit_active ) ? 'r'.$rows.'c'.$cols.'m'.$modules : '';
															$identifier = array( $rows, $cols, $modules ); // define module id
															$this->get_template_module( $mod, $builder_id, true, $w_wrap, $w_class, $identifier );
														}

														// Check for Sub-rows
														if ( isset( $mod['cols'] ) && count( $mod['cols'] ) > 0 ) {
															$sub_row_gutter = isset( $mod['gutter'] ) && ! empty( $mod['gutter'] ) ? $mod['gutter'] : 'gutter-default';
															$sub_row_class = 'sub_row_' . $rows . '-' . $cols . '-' . $modules; 
															$sub_row_attr = $this->frontedit_active ? '
															data-gutter="' . esc_attr( $sub_row_gutter ) . '"' : '';
															echo sprintf('<div class="themify_builder_sub_row
															clearfix %s %s"%s>', esc_attr( $sub_row_gutter ), esc_attr( $sub_row_class ),
																$sub_row_attr );
															?>
															
															<?php if( $this->frontedit_active ): ?>
															<div class="themify_builder_sub_row_top">
																<?php themify_builder_grid_lists( 'sub_row', $sub_row_gutter ); ?>
																<ul class="sub_row_action">
																	<li><a href="#" class="sub_row_duplicate"><span class="ti-layers"></span></a></li>
																	<li><a href="#" class="sub_row_delete"><span class="ti-close"></span></a></li>
																</ul>
															</div>
															<div class="themify_builder_sub_row_content">
															<?php endif; ?>

															<?php
															foreach( $mod['cols'] as $col_key => $sub_col ) {
																$sub_col_class = $this->frontedit_active ? 'themify_builder_col ' . $sub_col['grid_class'] : $sub_col['grid_class'];
																echo sprintf( '<div class="%s">', esc_attr( $sub_col_class ) ); ?>
																
																<?php if( $this->frontedit_active ): ?>
																<div class="themify_module_holder">
																	<div class="empty_holder_text"><?php _e('drop module here', 'themify') ?></div><!-- /empty module text -->
																<?php endif; ?>
																<?php
																if ( isset( $sub_col['modules'] ) && count( $sub_col['modules'] ) > 0 ) {
																	foreach( $sub_col['modules'] as $sub_module_k => $sub_module ) {
																		$sw_wrap = ( $this->frontedit_active ) ? true : false;
																		$sw_class = ( $this->frontedit_active ) ? 'r'. $sub_row_class .'c'.$col_key.'m'.$sub_module_k : '';
																		$sub_identifier = array( $sub_row_class, $col_key, $sub_module_k ); // define module id
																		$this->get_template_module( $sub_module, $builder_id, true, $sw_wrap, $sw_class, $sub_identifier );
																	}
																} ?>

																<?php if ( $this->frontedit_active ): ?>
																</div>
																<!-- /module_holder -->
																<?php endif; ?>
																<?php
																echo '</div>';
															}
															
															if ( $this->frontedit_active ) {
																echo '</div>';
															}

															echo '</div>';
														}
													}
												} elseif ( ! $this->frontedit_active )  {
													echo '&nbsp;'; // output empty space
												}
											?>
										
										<?php if ( $this->frontedit_active ): ?>
										</div>
										<!-- /module_holder -->
										<?php endif; ?>
									</div>
									<!-- /col -->
							<?php endforeach; ?>  

						<?php else: ?>

						<div class="themify_builder_col col-full first last">
							<?php if($this->frontedit_active): ?>
							<div class="themify_module_holder">
								<div class="empty_holder_text"><?php _e('drop module here', 'themify') ?></div><!-- /empty module text -->
							<?php endif; ?>
								
								<?php
									if ( ! $this->frontedit_active )  {
										echo '&nbsp;'; // output empty space
									}
								?>
							
							<?php if ( $this->frontedit_active ): ?>
							</div>
							<!-- /module_holder -->
							<?php endif; ?>
						</div>
						<!-- /col -->

						<?php endif; // end col loop ?>

						<?php if ( $this->frontedit_active ): ?>
						</div> <!-- /themify_builder_row_content -->
						
						<?php $row_data_styling = isset( $row['styling'] ) ? json_encode( $row['styling'] ) : json_encode( array() ); ?>
						<div class="row-data-styling" data-styling="<?php echo esc_attr( $row_data_styling ); ?>"></div>
						<?php endif; ?>
						
						<?php do_action('themify_builder_row_end', $builder_id, $row ); ?>
					
					</div>
					<!-- /row_inner -->
				</div>
			<!-- /row_inner_wrapper -->
			</div>
			<!-- /module_row -->
			<?php
			$output .= ob_get_clean();
			// add line break
			$output .= PHP_EOL;

			if ( $echo ) {
				echo $output;
			} else {
				return $output;
			}
		}

		/**
		 * Return the correct animation css class name
		 * @param string $effect 
		 * @return string
		 */
		function parse_animation_effect( $effect ) {
			if ( ! Themify_Builder_Model::is_animation_active() ) return '';
			
			return ( '' != $effect && ! in_array( $effect, array('fade-in', 'fly-in', 'slide-up') ) ) ? 'wow ' . $effect : $effect;
		}

		/**
		 * Add classes to post_class
		 * @param string|array $classes 
		 */
		function add_post_class( $classes ) {
			foreach( (array) $classes as $class ) {
				$this->_post_classes[$class] = $class;
			}
		}

		/**
		 * Remove sepecified classnames from post_class
		 * @param string|array $classes 
		 */
		function remove_post_class( $classes ) {
			foreach( (array) $classes as $class ) {
				unset( $this->_post_classes[$class] );
			}
		}

		/**
		 * Filter post_class to add the classnames to posts
		 *
		 * @return array
		 */
		function filter_post_class( $classes ) {
			$classes = array_merge( $classes, $this->_post_classes );
			return $classes;
		}

		/**
 		 * Return whether this is a Themify theme or not.
		 *
		 * @return bool
		 */
		function is_themify_theme() {
			// Check if THEMIFY_BUILDER_VERSION constant is defined.
			if ( defined( 'THEMIFY_BUILDER_VERSION' ) ) {
				// Check if it's defined with an expected value and not something odd.
				if ( preg_match( '/[1-9].[0-9].[0-9]/', THEMIFY_BUILDER_VERSION ) ) {
					return false;
				}
			}
			// It's a Themify theme.
			return true;
		}

		/**
		 * Add any js classname to html element when JavaScript is enabled
		 */
		function render_javascript_classes() {
			echo '<script>'; ?>
			function isSupportTransition() {
				var b = document.body || document.documentElement,
					s = b.style,
					p = 'transition';

				if (typeof s[p] == 'string') { return true; }

				// Tests for vendor specific prop
				var v = ['Moz', 'webkit', 'Webkit', 'Khtml', 'O', 'ms'];
				p = p.charAt(0).toUpperCase() + p.substr(1);

				for (var i=0; i<v.length; i++) {
					if (typeof s[v[i] + p] == 'string') { return true; }
				}
				return false;
			}
			if ( isSupportTransition() ) {
				document.documentElement.className += " csstransitions";	
			}
			<?php
			echo '</script>';
		}

		function parse_slug_to_ids( $slug_string, $post_type = 'post' ) {
			$slug_arr = explode( ',', $slug_string );
			$return = array();
			if ( count( $slug_arr ) > 0 ) {
				foreach( $slug_arr as $slug ) {
					array_push( $return, $this->get_id_by_slug( trim( $slug ), $post_type ) );
				}
			}
			return $return;
		}

		function get_id_by_slug( $slug, $post_type = 'post' ) {
			$args=array(
				'name' => $slug,
				'post_type' => $post_type,
				'post_status' => 'publish',
				'numberposts' => 1
			);
			$my_posts = get_posts($args);
			if( $my_posts ) {
				return $my_posts[0]->ID;
			} else {
				return null;
			}
		}

		/**
		 * Get a list of post types that can be accessed publicly
		 *
		 * does not include attachments, Builder layouts and layout parts,
		 * and also custom post types in Builder that have their own module.
		 *
		 * @return array of key => label pairs
		 */
		function get_public_post_types( $exclude_builder_post_types = true ) {
			$result = array();
			$post_types = get_post_types( array( 'public' => true, 'publicly_queryable' => 'true' ), 'objects' );
			$excluded_types = array( 'attachment', 'tbuilder_layout', 'tbuilder_layout_part', 'section' );
			if( $exclude_builder_post_types ) {
				$excluded_types = array_merge( $this->builder_cpt, $excluded_types );
			}
			foreach( $post_types as $key => $value ) {
				if( ! in_array( $key, $excluded_types ) ) {
					$result[$key] = $value->labels->singular_name;
				}
			}

			return apply_filters( 'builder_get_public_post_types', $result );
		}

		/**
		 * Get a list of taxonomies that can be accessed publicly
		 *
		 * does not include post formats, section categories (used by some themes),
		 * and also custom post types in Builder that have their own module.
		 *
		 * @return array of key => label pairs
		 */
		function get_public_taxonomies( $exclude_builder_post_types = true ) {
			$result = array();
			$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
			$excludes = array( 'post_format', 'section-category' );
			if( $exclude_builder_post_types ) { // exclude taxonomies from Builder CPTs
				foreach( $this->builder_cpt as $value ) {
					$excludes[] = "{$value}-category";
				}
			}
			foreach( $taxonomies as $key => $value ) {
				if( ! in_array( $key, $excludes ) ) {
					$result[$key] = $value->labels->name;
				}
			}

			return apply_filters( 'builder_get_public_taxonomies', $result );
		}

		/**
		 * If installation is in debug mode, returns '' to load non-minified scripts and stylesheets.
		 *
		 * @since 1.0.3
		 */
		function minified() {
			return ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? '' : '.min';
		}
	}

} // class_exists check