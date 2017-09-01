<?php

// check for rights
if ( !is_user_logged_in() || !current_user_can('edit_posts') )
	wp_die(__('You are not allowed to be here', 'themify'));
	
	if ( $this->load_form == 'module' ):
		$module_settings = apply_filters( 'themify_builder_module_settings_fields', $module->get_options(), $module );
		$styling_settings = apply_filters( 'themify_builder_styling_settings_fields', $module->get_styling(), $module );

	?><form id="tfb_module_settings">

	<div class="lightbox_inner">
		
		<ul class="themify_builder_options_tab clearfix">
			<li><a href="#themify_builder_options_setting"><?php echo ucfirst( $module->name ); ?></a></li>
			<?php if( count( $styling_settings ) > 0 ): ?>
			<li><a href="#themify_builder_options_styling"><?php _e('Styling', 'themify') ?></a></li>
			<?php endif; ?>
		</ul>

		<div id="themify_builder_options_setting" class="themify_builder_options_tab_content">
			<?php if( count( $module_settings ) > 0 ) {
				themify_builder_module_settings_field( $module_settings, $module->slug );
			} ?>
		</div>

		<?php if ( count( $styling_settings ) > 0 ) : ?>
		<div id="themify_builder_options_styling" class="themify_builder_options_tab_content">

			<?php themify_render_styling_settings( $styling_settings ); ?>

			<p>
				<a href="#" class="reset-module-styling" data-reset="module">
					<i class="ti ti-close"></i>
					<?php _e('Reset Styling', 'themify') ?>
				</a>
			</p>
		</div>
		<!-- /themify_builder_options_tab_content -->
		<?php endif; ?>
					
	</div>
	<!-- /themify_builder_lightbox_inner -->

	<p class="themify_builder_save">
		<a class="builder_cancel_lightbox"><?php _e( 'Cancel', 'themify' ) ?><i class="ti ti-close"></i></a>
		<input class="builder_button" type="submit" name="submit" value="<?php _e('Save', 'themify') ?>" />
	</p>

	</form>

<?php elseif ( $this->load_form == 'row' ): ?>

<?php
$row_settings = apply_filters( 'themify_builder_row_fields', array(
	// Row Width
	array(
		'id' => 'row_width',
		'label' => __( 'Row Width', 'themify' ),
		'type' => 'radio',
		'description' => __( 'Fullwidth row is only available when the Content Width option in Themify Custom Panel is set to Fullwidth.', 'themify' ),
		'meta' => array(
			array( 'value' => '', 'name' => __( 'Default', 'themify' ), 'selected' => true ),
			array( 'value' => 'fullwidth', 'name' => __( 'Fullwidth', 'themify' ) )
		),
		'wrap_with_class' => 'hide-if-not-themify-theme',
	),
	// Row Height
	array(
		'id' => 'row_height',
		'label' => __( 'Row Height', 'themify' ),
		'type' => 'radio',
		'description' => '',
		'meta' => array(
			array( 'value' => '', 'name' => __( 'Default', 'themify' ), 'selected' => true ),
			array( 'value' => 'fullheight', 'name' => __( 'Fullheight (100% viewport height)', 'themify' ) )
		),
		'wrap_with_class' => 'hide-if-not-themify-theme',
	),
	// Animation
	array(
		'type' => 'separator',
		'meta' => array('html'=>'<hr />')
	),
	array(
		'id' => 'separator_animation',
		'title' => '',
		'description' => '',
		'type' => 'separator',
		'meta' => array('html'=>'<h4>'.__('Animation', 'themify').'</h4>'),
	),
	array(
		'id' => 'animation_effect',
		'type' => 'animation_select',
		'label' => __( 'Effect', 'themify' )
	),
	// Background
	array(
		'type' => 'separator',
		'meta' => array('html'=>'<hr />')
	),
	array(
		'id' => 'separator_image_background',
		'title' => '',
		'description' => '',
		'type' => 'separator',
		'meta' => array('html'=>'<h4>'.__('Background', 'themify').'</h4>'),
	),
	array(
		'id' => 'background_type',
		'label' => __( 'Background Type', 'themify' ),
		'type' => 'radio',
		'meta' => array(
			array( 'value' => 'image', 'name' => __( 'Background Image', 'themify' ) ),
			array( 'value' => 'video', 'name' => __( 'Background Video', 'themify' ) ),
			array( 'value' => 'slider', 'name' => __( 'Background Slider', 'themify' ) ),
		),
		'option_js' => true,
	),
    // Background Slider
    array(
        'id' => 'background_slider',
        'type' => 'textarea',
        'label' => __('Background Slider', 'themify'),
        'class' => 'fullwidth tf-shortcode-input',
        'wrap_with_class' => 'tf-group-element tf-group-element-slider',
        'description' => sprintf('<a href="#" class="builder_button tf-gallery-btn">%s</a>', __('Insert Gallery', 'themify'))
    ),
    // Background Slider Mode
    array(
        'id' 		=> 'background_slider_mode',
        'label'		=> __('Background Slider Mode', 'themify'),
        'type' 		=> 'select',
        'default'	=> '',
        'meta'		=> array(
            array('value' => 'best-fit', 'name' => __('Best Fit', 'themify')),
            array('value' => 'fullcover', 'name' => __('Fullcover', 'themify')),
        ),
        'wrap_with_class' => 'tf-group-element tf-group-element-slider',
    ),
	// Video Background
	array(
		'id' => 'background_video',
		'type' => 'video',
		'label' => __('Background Video', 'themify'),
		'description' => __('Video format: mp4. Note: video background does not play on mobile, background image will be used as fallback.', 'themify'),
		'class' => 'xlarge',
		'wrap_with_class' => 'tf-group-element tf-group-element-video'
	),
    // Background Image
    array(
        'id' => 'background_image',
        'type' => 'image',
        'label' => __('Background Image', 'themify'),
        'class' => 'xlarge',
        'wrap_with_class' => 'tf-group-element tf-group-element-image tf-group-element-video',
    ),
    // Background repeat
    array(
        'id' 		=> 'background_repeat',
        'label'		=> __('Background Mode', 'themify'),
        'type' 		=> 'select',
        'default'	=> '',
        'meta'		=> array(
            array('value' => 'repeat', 'name' => __('Repeat All', 'themify')),
            array('value' => 'repeat-x', 'name' => __('Repeat Horizontally', 'themify')),
            array('value' => 'repeat-y', 'name' => __('Repeat Vertically', 'themify')),
            array('value' => 'repeat-none', 'name' => __('Do not repeat', 'themify')),
            array('value' => 'fullcover', 'name' => __('Fullcover', 'themify')),
            array('value' => 'builder-parallax-scrolling', 'name' => __('Parallax Scrolling', 'themify'))
        ),
        'wrap_with_class' => 'tf-group-element tf-group-element-image',
    ),
	// Background Color
	array(
		'id' => 'background_color',
		'type' => 'color',
		'label' => __('Background Color', 'themify'),
		'class' => 'small'
	),
	// Overlay Color
	array(
		'id' => 'separator_cover',
		'title' => '',
		'description' => '',
		'type' => 'separator',
		'meta' => array('html'=>'<h4>'.__('Row Overlay', 'themify').'</h4>'),
	),
	array(
		'id' => 'cover_color',
		'type' => 'color',
		'label' => __('Overlay Color', 'themify'),
		'class' => 'small'
	),
	array(
		'id' => 'cover_color_hover',
		'type' => 'color',
		'label' => __('Overlay Hover Color', 'themify'),
		'class' => 'small'
	),
	// Font
	array(
		'type' => 'separator',
		'meta' => array('html'=>'<hr />')
	),
	array(
		'id' => 'separator_font',
		'type' => 'separator',
		'meta' => array('html'=>'<h4>'.__('Font', 'themify').'</h4>'),
	),
	array(
		'id' => 'font_family',
		'type' => 'font_select',
		'label' => __('Font Family', 'themify'),
		'class' => 'font-family-select'
	),
	array(
		'id' => 'font_color',
		'type' => 'color',
		'label' => __('Font Color', 'themify'),
		'class' => 'small'
	),
	array(
		'id' => 'multi_font_size',
		'type' => 'multi',
		'label' => __('Font Size', 'themify'),
		'fields' => array(
			array(
				'id' => 'font_size',
				'type' => 'text',
				'class' => 'xsmall'
			),
			array(
				'id' => 'font_size_unit',
				'type' => 'select',
				'meta' => array(
					array('value' => '', 'name' => ''),
					array('value' => 'px', 'name' => __('px', 'themify')),
					array('value' => 'em', 'name' => __('em', 'themify'))
				)
			)
		)
	),
	array(
		'id' => 'multi_line_height',
		'type' => 'multi',
		'label' => __('Line Height', 'themify'),
		'fields' => array(
			array(
				'id' => 'line_height',
				'type' => 'text',
				'class' => 'xsmall'
			),
			array(
				'id' => 'line_height_unit',
				'type' => 'select',
				'meta' => array(
					array('value' => '', 'name' => ''),
					array('value' => 'px', 'name' => __('px', 'themify')),
					array('value' => 'em', 'name' => __('em', 'themify')),
					array('value' => '%', 'name' => __('%', 'themify'))
				)
			)
		)
	),
	array(
		'id' => 'text_align',
		'label' => __( 'Text Align', 'themify' ),
		'type' => 'radio',
		'meta' => array(
			array( 'value' => '', 'name' => __( 'Default', 'themify' ), 'selected' => true ),
			array( 'value' => 'left', 'name' => __( 'Left', 'themify' ) ),
			array( 'value' => 'center', 'name' => __( 'Center', 'themify' ) ),
			array( 'value' => 'right', 'name' => __( 'Right', 'themify' ) ),
			array( 'value' => 'justify', 'name' => __( 'Justify', 'themify' ) )
		)
	),
	// Link
	array(
		'type' => 'separator',
		'meta' => array('html'=>'<hr />')
	),
	array(
		'id' => 'separator_link',
		'type' => 'separator',
		'meta' => array('html'=>'<h4>'.__('Link', 'themify').'</h4>'),
	),
	array(
		'id' => 'link_color',
		'type' => 'color',
		'label' => __('Color', 'themify'),
		'class' => 'small'
	),
	array(
		'id' => 'text_decoration',
		'type' => 'select',
		'label' => __( 'Text Decoration', 'themify' ),
		'meta'	=> array(
			array('value' => '',   'name' => '', 'selected' => true),
			array('value' => 'underline',   'name' => __('Underline', 'themify')),
			array('value' => 'overline', 'name' => __('Overline', 'themify')),
			array('value' => 'line-through',  'name' => __('Line through', 'themify')),
			array('value' => 'none',  'name' => __('None', 'themify'))
		)
	),
	// Padding
	array(
		'type' => 'separator',
		'meta' => array('html'=>'<hr />')
	),
	array(
		'id' => 'separator_padding',
		'type' => 'separator',
		'meta' => array('html'=>'<h4>'.__('Padding', 'themify').'</h4>'),
	),
	array(
		'id' => 'multi_padding_top',
		'type' => 'multi',
		'label' => __('Padding', 'themify'),
		'fields' => array(
			array(
				'id' => 'padding_top',
				'type' => 'text',
				'class' => 'xsmall'
			),
			array(
				'id' => 'padding_top_unit',
				'type' => 'select',
				'description' => __('top', 'themify'),
				'meta' => array(
					array('value' => 'px', 'name' => __('px', 'themify')),
					array('value' => '%', 'name' => __('%', 'themify'))
				)
			),
		)
	),
	array(
		'id' => 'multi_padding_right',
		'type' => 'multi',
		'label' => '',
		'fields' => array(
			array(
				'id' => 'padding_right',
				'type' => 'text',
				'class' => 'xsmall'
			),
			array(
				'id' => 'padding_right_unit',
				'type' => 'select',
				'description' => __('right', 'themify'),
				'meta' => array(
					array('value' => 'px', 'name' => __('px', 'themify')),
					array('value' => '%', 'name' => __('%', 'themify'))
				)
			),
		)
	),
	array(
		'id' => 'multi_padding_bottom',
		'type' => 'multi',
		'label' => '',
		'fields' => array(
			array(
				'id' => 'padding_bottom',
				'type' => 'text',
				'class' => 'xsmall'
			),
			array(
				'id' => 'padding_bottom_unit',
				'type' => 'select',
				'description' => __('bottom', 'themify'),
				'meta' => array(
					array('value' => 'px', 'name' => __('px', 'themify')),
					array('value' => '%', 'name' => __('%', 'themify'))
				)
			),
		)
	),
	array(
		'id' => 'multi_padding_left',
		'type' => 'multi',
		'label' => '',
		'fields' => array(
			array(
				'id' => 'padding_left',
				'type' => 'text',
				'class' => 'xsmall'
			),
			array(
				'id' => 'padding_left_unit',
				'type' => 'select',
				'description' => __('left', 'themify'),
				'meta' => array(
					array('value' => 'px', 'name' => __('px', 'themify')),
					array('value' => '%', 'name' => __('%', 'themify'))
				)
			),
		)
	),
	// Margin
	array(
		'type' => 'separator',
		'meta' => array('html'=>'<hr />')
	),
	array(
		'id' => 'separator_margin',
		'type' => 'separator',
		'meta' => array('html'=>'<h4>'.__('Margin', 'themify').'</h4>'),
	),
	array(
		'id' => 'multi_margin_top',
		'type' => 'multi',
		'label' => __('Margin', 'themify'),
		'fields' => array(
			array(
				'id' => 'margin_top',
				'type' => 'text',
				'class' => 'xsmall'
			),
			array(
				'id' => 'margin_top_unit',
				'type' => 'select',
				'description' => __('top', 'themify'),
				'meta' => array(
					array('value' => 'px', 'name' => __('px', 'themify')),
					array('value' => '%', 'name' => __('%', 'themify'))
				)
			),
		)
	),
	array(
		'id' => 'multi_margin_right',
		'type' => 'multi',
		'label' => '',
		'fields' => array(
			array(
				'id' => 'margin_right',
				'type' => 'text',
				'class' => 'xsmall'
			),
			array(
				'id' => 'margin_right_unit',
				'type' => 'select',
				'description' => __('right', 'themify'),
				'meta' => array(
					array('value' => 'px', 'name' => __('px', 'themify')),
					array('value' => '%', 'name' => __('%', 'themify'))
				)
			),
		)
	),
	array(
		'id' => 'multi_margin_bottom',
		'type' => 'multi',
		'label' => '',
		'fields' => array(
			array(
				'id' => 'margin_bottom',
				'type' => 'text',
				'class' => 'xsmall'
			),
			array(
				'id' => 'margin_bottom_unit',
				'type' => 'select',
				'description' => __('bottom', 'themify'),
				'meta' => array(
					array('value' => 'px', 'name' => __('px', 'themify')),
					array('value' => '%', 'name' => __('%', 'themify'))
				)
			),
		)
	),
	array(
		'id' => 'multi_margin_left',
		'type' => 'multi',
		'label' => '',
		'fields' => array(
			array(
				'id' => 'margin_left',
				'type' => 'text',
				'class' => 'xsmall'
			),
			array(
				'id' => 'margin_left_unit',
				'type' => 'select',
				'description' => __('left', 'themify'),
				'meta' => array(
					array('value' => 'px', 'name' => __('px', 'themify')),
					array('value' => '%', 'name' => __('%', 'themify'))
				)
			),
		)
	),
	// Border
	array(
		'type' => 'separator',
		'meta' => array('html'=>'<hr />')
	),
	array(
		'id' => 'separator_border',
		'type' => 'separator',
		'meta' => array('html'=>'<h4>'.__('Border', 'themify').'</h4>'),
	),
	array(
		'id' => 'multi_border_top',
		'type' => 'multi',
		'label' => __('Border', 'themify'),
		'fields' => array(
			array(
				'id' => 'border_top_color',
				'type' => 'color',
				'class' => 'small'
			),
			array(
				'id' => 'border_top_width',
				'type' => 'text',
				'description' => 'px',
				'class' => 'xsmall'
			),
			array(
				'id' => 'border_top_style',
				'type' => 'select',
				'description' => __('top', 'themify'),
				'meta' => array(
					array( 'value' => '', 'name' => '' ),
					array( 'value' => 'solid', 'name' => __( 'Solid', 'themify' ) ),
					array( 'value' => 'dashed', 'name' => __( 'Dashed', 'themify' ) ),
					array( 'value' => 'dotted', 'name' => __( 'Dotted', 'themify' ) ),
					array( 'value' => 'double', 'name' => __( 'Double', 'themify' ) )
				)
			)
		)
	),
	array(
		'id' => 'multi_border_right',
		'type' => 'multi',
		'label' => '',
		'fields' => array(
			array(
				'id' => 'border_right_color',
				'type' => 'color',
				'class' => 'small'
			),
			array(
				'id' => 'border_right_width',
				'type' => 'text',
				'description' => 'px',
				'class' => 'xsmall'
			),
			array(
				'id' => 'border_right_style',
				'type' => 'select',
				'description' => __('right', 'themify'),
				'meta' => array(
					array( 'value' => '', 'name' => '' ),
					array( 'value' => 'solid', 'name' => __( 'Solid', 'themify' ) ),
					array( 'value' => 'dashed', 'name' => __( 'Dashed', 'themify' ) ),
					array( 'value' => 'dotted', 'name' => __( 'Dotted', 'themify' ) ),
					array( 'value' => 'double', 'name' => __( 'Double', 'themify' ) )
				)
			)
		)
	),
	array(
		'id' => 'multi_border_bottom',
		'type' => 'multi',
		'label' => '',
		'fields' => array(
			array(
				'id' => 'border_bottom_color',
				'type' => 'color',
				'class' => 'small'
			),
			array(
				'id' => 'border_bottom_width',
				'type' => 'text',
				'description' => 'px',
				'class' => 'xsmall'
			),
			array(
				'id' => 'border_bottom_style',
				'type' => 'select',
				'description' => __('bottom', 'themify'),
				'meta' => array(
					array( 'value' => '', 'name' => '' ),
					array( 'value' => 'solid', 'name' => __( 'Solid', 'themify' ) ),
					array( 'value' => 'dashed', 'name' => __( 'Dashed', 'themify' ) ),
					array( 'value' => 'dotted', 'name' => __( 'Dotted', 'themify' ) ),
					array( 'value' => 'double', 'name' => __( 'Double', 'themify' ) )
				)
			)
		)
	),
	array(
		'id' => 'multi_border_left',
		'type' => 'multi',
		'label' => '',
		'fields' => array(
			array(
				'id' => 'border_left_color',
				'type' => 'color',
				'class' => 'small'
			),
			array(
				'id' => 'border_left_width',
				'type' => 'text',
				'description' => 'px',
				'class' => 'xsmall'
			),
			array(
				'id' => 'border_left_style',
				'type' => 'select',
				'description' => __('left', 'themify'),
				'meta' => array(
					array( 'value' => '', 'name' => '' ),
					array( 'value' => 'solid', 'name' => __( 'Solid', 'themify' ) ),
					array( 'value' => 'dashed', 'name' => __( 'Dashed', 'themify' ) ),
					array( 'value' => 'dotted', 'name' => __( 'Dotted', 'themify' ) ),
					array( 'value' => 'double', 'name' => __( 'Double', 'themify' ) )
				)
			)
		)
	),
	// Additional CSS
	array(
		'type' => 'separator',
		'meta' => array( 'html' => '<hr/>')
	),
	array(
		'id' => 'custom_css_row',
		'type' => 'text',
		'label' => __('Additional CSS Class', 'themify'),
		'class' => 'large exclude-from-reset-field',
		'description' => sprintf( '<br/><small>%s</small>', __( 'Add additional CSS class(es) for custom styling', 'themify') )
	),
	array(
		'id'          => 'row_anchor',
		'type'        => 'text',
		'label'       => __( 'Row Anchor', 'themify' ),
		'class'       => 'large exclude-from-reset-field',
		'description' => sprintf( '<br/><small>%s</small>', __( 'Example: enter ‘about’ as row anchor and add ‘#about’ link in navigation menu. When link is clicked, it will scroll to this row.', 'themify' ) )
	),
) );
?>

<form id="tfb_row_settings">
	<div class="lightbox_inner">
		<?php foreach( $row_settings as $styling ):

			$wrap_with_class = isset( $styling['wrap_with_class'] ) ? $styling['wrap_with_class'] : '';
			echo ( $styling['type'] != 'separator' ) ? '<div class="themify_builder_field ' . esc_attr( $wrap_with_class ) . '">' : '';
			if ( isset( $styling['label'] ) ) {
				echo '<div class="themify_builder_label">' . esc_html( $styling['label'] ) . '</div>';
			}
			echo ( $styling['type'] != 'separator' ) ? '<div class="themify_builder_input">' : '';
			if ( $styling['type'] != 'multi' ) {
				themify_builder_styling_field( $styling );
			} else {
				foreach( $styling['fields'] as $field ) {
					themify_builder_styling_field( $field );
				}
			}
			echo ( $styling['type'] != 'separator' ) ? '</div>' : ''; // themify_builder_input
			echo ( $styling['type'] != 'separator' ) ? '</div>' : ''; // themify_builder_field

		endforeach; ?>
	</div>
	<!-- /lightbox_inner -->

	<p>
		<a href="#" class="reset-module-styling" data-reset="row">
			<i class="ti ti-close"></i>
			<?php _e('Reset Styling', 'themify') ?>
		</a>
	</p>

	<p class="themify_builder_save">
		<a class="builder_cancel_lightbox"><?php _e( 'Cancel', 'themify' ) ?><i class="ti ti-close"></i></a>
		<input class="builder_button" type="submit" name="submit" value="<?php _e('Save', 'themify') ?>" />
	</p>	
</form>

<?php endif; ?>