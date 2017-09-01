<div class="lightbox_inner">
	<form id="themify_builder_load_template_form" method="POST">
		
		<p><?php _e('Builder Layouts are the pre-designed layouts which you can apply to any page for quicker prototyping. Click on the thumbnail to apply. <strong>Warning:</strong> your current Builder content on the page will be override with the Layout.', 'themify') ?></p>

		<?php if ( count( $posts ) > 0 ): ?>
		<ul class="themify_builder_layout_lists">
			<?php global $post; $temp_post = $post; ?>
			
			<?php foreach( $posts as $post ): setup_postdata( $post ); ?>
			<li class="layout_preview_list">
				<div class="layout_preview">
					<div class="thumbnail" data-layout-slug="<?php echo esc_attr( $post->post_name ); ?>">
						<?php
						if ( has_post_thumbnail() ) {
							the_post_thumbnail( 'thumbnail', array( 150, 150 ) );
						} else {
							echo sprintf( '<img src="%s">', 'http://placehold.it/150x150' );
						} ?>
					</div>
					<!-- /thumbnail -->
					<div class="layout_action">
						<div class="layout_title">
							<?php the_title(); ?>
						</div>
						<!-- /template_title -->
					</div>
					<!-- /template_action -->
				</div>
				<!-- /template_preview -->
			</li>
			<?php endforeach; wp_reset_postdata(); $post = $temp_post; ?>
		</ul>
		<?php endif; ?>
		<div class="clearfix"></div>

		<p class="add_new_template">
			<a href="<?php echo admin_url('post-new.php?post_type=' . $this->layout->post_type_name); ?>" target="_blank">
				<span class="themify_builder_icon add"></span>
				<?php _e('Add new layout', 'themify') ?>
			</a>
			&nbsp;
			<a href="<?php echo admin_url('edit.php?post_type=' . $this->layout->post_type_name); ?>" target="_blank">
				<span class="themify_builder_icon ti-folder"></span>
				<?php _e('Manage Layouts', 'themify') ?>
			</a>
		</p>

	</form>
</div> <!-- /lightbox_inner -->