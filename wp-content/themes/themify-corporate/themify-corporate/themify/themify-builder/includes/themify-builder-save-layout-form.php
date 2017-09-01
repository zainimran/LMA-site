<form id="tfb_save_layout_form">
	<input type="hidden" name="postid" value="<?php echo esc_attr( $postid ); ?>">
	<div class="lightbox_inner">
		<?php Themify_Builder_Form::render( $fields ); ?>
	</div>
	<!-- /lightbox_inner -->

	<p class="themify_builder_save">
		<input class="builder_button" type="submit" name="submit" value="<?php _e('Save', 'themify') ?>" />
	</p>	
</form>