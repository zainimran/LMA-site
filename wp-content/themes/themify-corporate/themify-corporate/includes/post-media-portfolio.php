<?php 
/**
 * Media Template.
 * If there's a Video URL in Themify Custom Panel it will show it, otherwise shows the featured image.
 * @package themify
 * @since 1.0.0
 */

/** Themify Default Variables
 *  @var object */
global $themify; ?>

<?php if ( $themify->hide_image != 'yes' ) : ?>
		
	<?php themify_before_post_image(); // Hook ?>

	<?php
	if ( themify_get( 'video_url' ) != '' ) : ?>

		<figure class="post-image">
			<?php
				global $wp_embed;
				echo $wp_embed->run_shortcode('[embed]' . themify_get('video_url') . '[/embed]');
			?>
		</figure>

	<?php else: ?>

		<?php
		if ( 'yes' == $themify->use_original_dimensions ) {
			$themify->width = themify_get( 'image_width' );
			$themify->width = themify_get( 'image_height' );
		}
		?>

		<?php if( $post_image = themify_get_image( 'ignore=true&w='.$themify->width.'&h='.$themify->height ) ) : ?>
		
			<figure class="post-image <?php echo $themify->image_align; ?>">

				<a href="<?php echo themify_get_featured_image_link(); ?>" class="themify-lightbox"><?php echo $post_image; ?><?php themify_zoom_icon(); ?></a>

			</figure>
	
		<?php endif; // if there's a featured image?>

	<?php endif; // video else image ?>

	<?php themify_after_post_image(); // Hook ?>

<?php endif; // hide image ?>