<?php
/**
 * Portfolio Meta Box Options
 * @param array $args
 * @return array
 * @since 1.0.7
 */
function themify_theme_testimonial_meta_box( $args = array() ){
	extract( $args );
	return array(
		// Feature Image
		array(
			'name' 		=> 'post_image',
			'title' 		=> __('Featured Image', 'themify'),
			'description' => '',
			'type' 		=> 'image',
			'meta'		=> array()
		),
		// Featured Image Size
		array(
			'name'	=>	'feature_size',
			'title'	=>	__('Image Size', 'themify'),
			'description' => sprintf(__('Image sizes can be set at <a href="%s">Media Settings</a> and <a href="%s">Regenerated</a>', 'themify'), 'options-media.php', 'admin.php?page=themify_regenerate-thumbnails'),
			'type'		 =>	'featimgdropdown'
		),
		// Multi field: Image Dimension
		themify_image_dimensions_field(),
		// Testimonial Author Name
		array(
			'name' 		=> 'testimonial_name',
			'title' 	=> __('Author Name', 'themify'),
			'description' => '',
			'type' 		=> 'textbox',
			'meta'		=> array()
		),
		// Testimonial Title
		array(
			'name' 		=> 'testimonial_title',
			'title' 	=> __('Title', 'themify'),
			'description' => '',
			'type' 		=> 'textbox',
			'meta'		=> array()
		),
		// External Link
		array(
			'name' 		=> 'external_link',
			'title' 		=> __('External Link', 'themify'),
			'description' => __('Link Featured Image and Post Title to external URL', 'themify'),
			'type' 		=> 'textbox',
			'meta'		=> array()
		),
		// Lightbox Link
		themify_lightbox_link_field(),
		// Shortcode ID
		array(
			'name' 		=> 'post_id_info',
			'title' 	=> __('Shortcode ID', 'themify'),
			'description' => __('To show this use [testimonial id="%s"]', 'themify'),
			'type' 		=> 'post_id_info'
		)
	);
}

/**************************************************************************************************
 * Section Class - Shortcode
 **************************************************************************************************/

if ( ! class_exists( 'Themify_Testimonial' ) ) {

	class Themify_Testimonial {

		var $instance = 0;
		var $atts = array();
		var $post_type = 'testimonial';
		var $tax = 'testimonial-category';
		var $taxonomies;

		function __construct( $args = array() ) {
			$this->atts = array(
				'id' => '',
				'title' => 'no', // no
				'image' => 'yes', // no
				'image_w' => 144,
				'image_h' => 144,
				'display' => 'content', // excerpt, none
				'more_link' => false, // true goes to post type archive, and admits custom link
				'more_text' => __('More &rarr;', 'themify'),
				'limit' => 4,
				'category' => 'all', // integer category ID
				'order' => 'DESC', // ASC
				'orderby' => 'date', // title, rand
				'style' => 'grid2', // grid3, grid4, list-post, slider
				'show_author' => 'yes', // no
				'use_original_dimensions' => 'no', // yes
			);
			add_action( 'init', array( $this, 'register' ) );
			add_action( 'admin_init', array( $this, 'manage_and_filter' ) );
			add_action( 'save_post', array($this, 'set_default_term'), 100, 2 );
			add_filter( 'themify_post_types', array($this, 'extend_post_types' ) );

			add_shortcode( $this->post_type, array( $this, 'init_shortcode' ) );
			add_shortcode( 'themify_'.$this->post_type.'_posts', array( $this, 'init_shortcode' ) );

			add_filter( "builder_is_{$this->post_type}_active", '__return_true' );
		}

		/**
		 * Register post type and taxonomy
		 */
		function register() {
			$cpt = array(
				'plural' => __('Testimonials', 'themify'),
				'singular' => __('Testimonial', 'themify'),
			);
			register_post_type( $this->post_type, array(
				'labels' => array(
					'name' => $cpt['plural'],
					'singular_name' => $cpt['singular']
				),
				'supports' => isset( $cpt['supports'] )? $cpt['supports'] : array( 'title', 'editor', 'thumbnail', 'custom-fields', 'excerpt' ),

				'hierarchical' => false,
				'public' => true,
				'exclude_from_search' => true,
				'query_var' => true,
				'can_export' => true,
				'capability_type' => 'post'
			));
			register_taxonomy( $this->tax, array( $this->post_type ), array(
				'labels' => array(
					'name' => sprintf( __( '%s Categories', 'themify' ), $cpt['singular'] ),
					'singular_name' => sprintf( __( '%s Category', 'themify' ), $cpt['singular'] )
				),
				'public' => true,
				'show_in_nav_menus' => true,
				'show_ui' => true,
				'show_tagcloud' => true,
				'hierarchical' => true,
				'rewrite' => true,
				'query_var' => true
			));
			if ( is_admin() ) {
				add_filter('manage_edit-'.$this->tax.'_columns', array(&$this, 'taxonomy_header'), 10, 2);
				add_filter('manage_'.$this->tax.'_custom_column', array(&$this, 'taxonomy_column_id'), 10, 3);
			}
		}

		/**
		 * Set default term for custom taxonomy and assign to post
		 * @param number
		 * @param object
		 */
		function set_default_term( $post_id, $post ) {
			if ( 'publish' === $post->post_status ) {
				$terms = wp_get_post_terms( $post_id, $this->tax );
				if ( empty( $terms ) ) {
					wp_set_object_terms( $post_id, __( 'Uncategorized', 'themify' ), $this->tax );
				}
			}
		}

		/**
		 * Display an additional column in categories list
		 * @since 1.0.0
		 */
		function taxonomy_header($cat_columns) {
			$cat_columns['cat_id'] = 'ID';
			return $cat_columns;
		}
		/**
		 * Display ID in additional column in categories list
		 * @since 1.0.0
		 */
		function taxonomy_column_id($null, $column, $termid) {
			return $termid;
		}

		/**
		 * Includes new post types registered in theme to array of post types managed by Themify
		 * @param array
		 * @return array
		 */
		function extend_post_types( $types ) {
			return array_merge( $types, array( $this->post_type ) );
		}

		/**
		 * Add shortcode to WP
		 * @param $atts Array shortcode attributes
		 * @return String
		 * @since 1.0.0
		 */
		function init_shortcode( $atts ) {
			$this->instance++;
			return do_shortcode( $this->shortcode( shortcode_atts( $this->atts, $atts ), $this->post_type ) );
		}

		/**
		 * Trigger at the end of __construct of this shortcode
		 */
		function manage_and_filter() {
			add_filter( "manage_edit-{$this->post_type}_columns", array( $this, 'type_column_header' ), 10, 2 );
			add_action( "manage_{$this->post_type}_posts_custom_column", array( $this, 'type_column' ), 10, 3 );
			add_action( 'load-edit.php', array( $this, 'filter_load' ) );
			add_filter( 'post_row_actions', array( $this, 'remove_quick_edit' ), 10, 1 );
			add_filter( 'get_sample_permalink_html', array($this, 'hide_view_post'), '', 4 );
		}

		/**
		 * Remove quick edit action from entries list in admin
		 * @param $actions
		 * @return mixed
		 */
		function remove_quick_edit( $actions ) {
			global $post;
			if( $post->post_type == $this->post_type )
				unset($actions['inline hide-if-no-js']);
			return $actions;
		}

		/**
		 * Hides View Section/Team/Highlight/Testimonial/Timeline button in edit screen
		 * @param string $return
		 * @param string $id
		 * @param string $new_title
		 * @param string $new_slug
		 * @return string Markup without the button
		 */
		function hide_view_post($return, $id, $new_title, $new_slug){
			if( get_post_type( $id ) == $this->post_type ) {
				return preg_replace('/<span id=\'view-post-btn\'>.*<\/span>/i', '', $return);
			} else {
				return $return;
			}
		}

		/**
		 * Display an additional column in list
		 * @param array
		 * @return array
		 */
		function type_column_header( $columns ) {
			unset( $columns['date'] );
			$columns['icon'] = __('Icon', 'themify');
			$columns['testimonial_name'] = __('Name', 'themify');
			$columns['shortcode'] = __('Shortcode', 'themify');
			return $columns;
		}

		/**
		 * Display shortcode, type, size and color in columns in tiles list
		 * @param string $column key
		 * @param number $post_id
		 * @return string
		 */
		function type_column( $column, $post_id ) {
			switch( $column ) {
				case 'shortcode' :
					echo '<code>[' . $this->post_type . ' id="' . $post_id . '"]</code>';
					break;

				case 'icon' :
					the_post_thumbnail( array( 50, 50 ) );
					break;

				case 'testimonial_name' :
					echo get_post_meta( $post_id, 'testimonial_name', true );
					break;
			}
		}

		/**
		 * Filter request to sort
		 */
		function filter_load() {
			global $typenow;
			if ( $typenow == $this->post_type ) {
				add_action( current_filter(), array( $this, 'setup_vars' ), 20 );
				add_action( 'restrict_manage_posts', array( $this, 'get_select' ) );
				add_filter( "manage_taxonomies_for_{$this->post_type}_columns", array( $this, 'add_columns' ) );
			}
		}

		/**
		 * Add columns when filtering posts in edit.php
		 */
		public function add_columns( $taxonomies ) {
			return array_merge( $taxonomies, $this->taxonomies );
		}

		/**
		 * Checks if the string begins with - like "-excluded"
		 *
		 * @param string $string String to check
		 *
		 * @return bool
		 */
		function is_negative_string( $string ) {
			return '-' === $string[0];
		}

		/**
		 * Checks if the string does not being with - like "included"
		 *
		 * @param string $string String to check
		 *
		 * @return bool
		 */
		function is_positive_string( $string ) {
			return '-' !== $string[0];
		}

		/**
		 * Parses the arguments given as category to see if they are category IDs or slugs and returns a proper tax_query
		 * @param $category
		 * @param $post_type
		 * @return array
		 */
		function parse_category_args( $category, $post_type ) {
			$tax_query = array();
			if ( 'all' != $category ) {
				$terms = explode(',', $category);
				if( preg_match( '#[a-z]#', $category ) ) {
					$include = array_filter( $terms, array( $this, 'is_positive_string' ) );
					$exclude = array_filter( $terms, array( $this, 'is_negative_string' ) );
					$field = 'slug';
				} else {
					$include = array_filter( $terms, 'themify_is_positive_number' );
					$exclude = array_map( 'themify_make_absolute_number', array_filter( $terms, 'themify_is_negative_number' ) );
					$field = 'id';
				}

				if ( !empty( $include ) && !empty( $exclude ) ) {
					$tax_query = array(
						'relation' => 'AND'
					);
				}
				if ( !empty( $include ) ) {
					$tax_query[] = array(
						'taxonomy' => $post_type . '-category',
						'field'    => $field,
						'terms'    => $include,
					);
				}
				if ( !empty( $exclude ) ) {
					$tax_query[] = array(
						'taxonomy' => $post_type . '-category',
						'field'    => $field,
						'terms'    => $exclude,
						'operator' => 'NOT IN',
					);
				}
			}
			return $tax_query;
		}

		/**
		 * Select form element to filter the post list
		 * @return string HTML
		 */
		public function get_select() {
			$html = '';
			foreach ($this->taxonomies as $tax) {
				$options = sprintf('<option value="">%s %s</option>', __('View All', 'themify'),
				get_taxonomy($tax)->label);
				$class = is_taxonomy_hierarchical($tax) ? ' class="level-0"' : '';
				foreach (get_terms( $tax ) as $taxon) {
					$options .= sprintf('<option %s%s value="%s">%s%s</option>', isset($_GET[$tax]) ? selected($taxon->slug, $_GET[$tax], false) : '', '0' !== $taxon->parent ? ' class="level-1"' : $class, $taxon->slug, '0' !== $taxon->parent ? str_repeat('&nbsp;', 3) : '', "{$taxon->name} ({$taxon->count})");
				}
				$html .= sprintf('<select name="%s" id="%s" class="postform">%s</select>', $tax, $tax, $options);
			}
			return print $html;
		}

		/**
		 * Setup vars when filtering posts in edit.php
		 */
		function setup_vars() {
			$this->post_type =  get_current_screen()->post_type;
			$this->taxonomies = array_diff(get_object_taxonomies($this->post_type), get_taxonomies(array('show_admin_column' => 'false')));
		}

		/**
		 * Returns link wrapped in paragraph either to the post type archive page or a custom location
		 * @param bool|string $more_link False does nothing, true goes to archive page, custom string sets custom location
		 * @param string $more_text
		 * @param string $post_type
		 * @return string
		 */
		function section_link( $more_link = false, $more_text, $post_type ) {
			if ( $more_link ) {
				if ( 'true' == $more_link ) {
					$more_link = get_post_type_archive_link( $post_type );
				}
				return '<p class="more-link-wrap"><a href="' . esc_url( $more_link ) . '" class="more-link">' . $more_text . '</a></p>';
			}
			return '';
		}

		/**
		 * Returns class to add in columns when querying multiple entries
		 * @param string $style Entries layout
		 * @return string $col_class CSS class for column
		 */
		function column_class( $style ) {
			switch ( $style ) {
				case 'grid4':
					$col_class = 'col4-1';
					break;
				case 'grid3':
					$col_class = 'col3-1';
					break;
				case 'grid2':
					$col_class = 'col2-1';
					break;
				default:
					$col_class = '';
					break;
			}
			return $col_class;
		}

		/**
		 * Main shortcode rendering
		 * @param array $atts
		 * @param $post_type
		 * @return string|void
		 */
		function shortcode($atts = array(), $post_type) {
			extract($atts);
			// Parameters to get posts
			$args = array(
				'post_type' => $post_type,
				'posts_per_page' => $limit,
				'order' => $order,
				'orderby' => $orderby,
				'suppress_filters' => false
			);
			$args['tax_query'] = $this->parse_category_args($category, $post_type);

			// Defines layout type
			$cpt_layout_class = $this->post_type.'-multiple clearfix type-multiple';
			$multiple = true;

			// Single post type or many single post types
			if( '' != $id ) {
				if(strpos($id, ',')) {
					$ids = explode(',', str_replace(' ', '', $id));
					foreach ($ids as $string_id) {
						$int_ids[] = intval($string_id);
					}
					$args['post__in'] = $int_ids;
					$args['orderby'] = 'post__in';
				} else {
					$args['p'] = intval($id);
					$cpt_layout_class = $this->post_type.'-single';
					$multiple = false;
				}
			}

			// Get posts according to parameters
			$posts = get_posts( apply_filters('themify_'.$post_type.'_shortcode_args', $args) );

			// Collect markup to be returned
			$out = '';

			if ($posts) {
				global $themify;
				$themify_save = clone $themify; // save a copy

				// override $themify object
				$themify->hide_title = $title;
				$themify->hide_image = $image;
				if(!$multiple) {
					if( '' == $image_w || get_post_meta($args['p'], 'image_width', true ) ) {
						$themify->width = get_post_meta($args['p'], 'image_width', true );
					}
					if( '' == $image_h || get_post_meta($args['p'], 'image_height', true ) ) {
						$themify->height = get_post_meta($args['p'], 'image_height', true );
					}
				} else {
					$themify->width = $image_w;
					$themify->height = $image_h;
				}

				$themify->use_original_dimensions = 'yes' == $use_original_dimensions? 'yes': 'no';
				$themify->display_content = $display;
				$themify->more_link = $more_link;
				$themify->more_text = $more_text;
				$themify->post_layout = $style;

				$slider_id = ( 'slider' == $style )? 'id="testimonial-slider-' . $this->instance.'"': '';
				$out .= '<div '.$slider_id.' class="loops-wrapper shortcode ' . $post_type  . ' ' . $style . ' '. $cpt_layout_class .'">';
					if ( 'slider' == $style ) {
						$option = 'setting-testimonial_slider';
						$out .= sprintf('<div class="slideshow-wrap"><ul class="slideshow" data-id="testimonial-slider-%s" data-autoplay="%s" data-effect="%s" data-speed="%s">',
							$this->instance,
							themify_check($option.'_autoplay')? themify_get($option.'_autoplay'): '4000',
							themify_check($option.'_effect')? themify_get($option.'_effect'): 'scroll',
							themify_check($option.'_transition_speed')?	themify_get($option.'_transition_speed'): '500'
						);
					}
					$out .= themify_get_shortcode_template($posts, 'includes/loop-testimonial', 'index');
					if ( 'slider' == $style ) {
						$out .= '</ul></div>';
					}
					$out .= $this->section_link($more_link, $more_text, $post_type);
				$out .= '</div>';

				$themify = clone $themify_save; // revert to original $themify state
			}
			$this->instance++;
			return $out;
		}
	}
}

/**************************************************************************************************
 * Initialize Type Class
 **************************************************************************************************/
$GLOBALS['themify_testimonial'] = new Themify_Testimonial();

/**************************************************************************************************
 * Themify Theme Settings Module
 **************************************************************************************************/

/**
 * Creates testimonial slider module
 * @return string
 */
function themify_testimonial_slider() {
	return themify_generic_slider_controls('setting-testimonial_slider_');
}