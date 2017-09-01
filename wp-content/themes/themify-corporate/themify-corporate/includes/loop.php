<?php
/**
 * Template for generic post display.
 * @package themify
 * @since 1.0.0
 */
?>
<?php if(!is_single()){ global $more; $more = 0; } //enable more link ?>

<?php 
/** Themify Default Variables
 *  @var object */
global $themify;
?>

<?php themify_post_before(); // hook ?>

<article itemscope itemtype="http://schema.org/Article" id="post-<?php the_ID(); ?>" <?php post_class( 'post clearfix' ); ?>>
	
	<?php themify_post_start(); // hook ?>
	
	<?php if ( $themify->hide_image != 'yes' ) : ?>
		
		<?php themify_before_post_image(); // Hook ?>
		
		<?php if ( themify_get( 'video_url' ) != '' ) : ?>
			
			<?php
				global $wp_embed;
				echo $wp_embed->run_shortcode('[embed]' . themify_get('video_url') . '[/embed]');
			?>

		<?php elseif( $post_image = themify_get_image($themify->auto_featured_image . $themify->image_setting . "w=".$themify->width."&h=".$themify->height) ) : ?>
			
			<figure class="post-image <?php echo $themify->image_align; ?>">
				<?php if( 'yes' == $themify->unlink_image): ?>
					<?php echo $post_image; ?>
				<?php else: ?>
					<a href="<?php echo themify_get_featured_image_link(); ?>"><?php echo $post_image; ?><?php themify_zoom_icon(); ?></a>
				<?php endif; // unlink image ?>
			</figure>
		
		<?php endif; // video else image ?>
		
		<?php themify_after_post_image(); // Hook ?>

	<?php endif; // hide image ?>

	<?php if($themify->hide_date != 'yes'): ?>
		<div class="post-date-wrap">
			<time datetime="<?php the_time('o-m-d') ?>" class="post-date entry-date updated" itemprop="datePublished">
				<span class="day"><?php the_time( 'j' ); ?></span>
				<span class="month"><?php the_time( 'M' ); ?></span>
				<span class="year"><?php the_time( 'Y' ); ?></span>
			</time>
		</div>
	<?php endif; //post date ?>

	<div class="post-content">

		<?php if($themify->hide_meta != 'yes'): ?>
			<p class="post-meta entry-meta">

				<?php if($themify->hide_meta_author != 'yes'): ?>
					<span class="post-author"><?php echo themify_get_author_link() ?></span>
				<?php endif; ?>

				<?php if ( $themify->hide_meta_category != 'yes' ): ?>
					<?php the_terms( get_the_ID(), 'post' != get_post_type() ? get_post_type() . '-category' : 'category', ' <span class="post-category">', ', ', '</span>' ); ?>
				<?php endif; // meta category ?>

				<?php if ( $themify->hide_meta_tag != 'yes' ): ?>
					<?php the_terms( get_the_ID(), 'post' != get_post_type() ? get_post_type() . '-tag' : 'post_tag', ' <span class="post-tag">', ', ', '</span>' ); ?>
				<?php endif; // meta tag ?>

				<?php  if( !themify_get('setting-comments_posts') && comments_open() && $themify->hide_meta_comment != 'yes' ) : ?>
					<span class="post-comment"><?php comments_popup_link( __( '0 comments', 'themify' ), __( '1 comment', 'themify' ), __( '% comments', 'themify' ) ); ?></span>
				<?php endif; ?>

			</p>
		<?php endif; //post meta ?>

		<?php if($themify->hide_title != 'yes'): ?>
			<?php themify_before_post_title(); // Hook ?>

			<<?php themify_theme_entry_title_tag(); ?> class="post-title entry-title" itemprop="name">
				<?php if($themify->unlink_title == 'yes'): ?>
					<?php the_title(); ?>
				<?php else: ?>
					<a href="<?php echo themify_get_featured_image_link(); ?>" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a>
				<?php endif; //unlink post title ?>
			</<?php themify_theme_entry_title_tag(); ?>>

			<?php themify_after_post_title(); // Hook ?>
		<?php endif; //post title ?>
		
		<div class="entry-content" itemprop="articleBody">

			<?php if ( 'excerpt' == $themify->display_content && ! is_attachment() ) : ?>

				<?php the_excerpt(); ?>

				<?php if( themify_check('setting-excerpt_more') ) : ?>

					<p><a href="<?php the_permalink(); ?>" title="<?php the_title_attribute('echo=0'); ?>" class="more-link"><?php echo themify_check('setting-default_more_text')? themify_get('setting-default_more_text') : __('More &rarr;', 'themify') ?></a></p>

				<?php endif; ?>

			<?php elseif($themify->display_content == 'none'): ?>

			<?php else: ?>

				<?php the_content(themify_check('setting-default_more_text')? themify_get('setting-default_more_text') : __('More &rarr;', 'themify')); ?>

			<?php endif; //display content ?>

		</div><!-- /.entry-content -->
		
		<?php edit_post_link(__('Edit', 'themify'), '<span class="edit-button">[', ']</span>'); ?>
		
	</div>
	<!-- /.post-content -->
	<?php themify_post_end(); // hook ?>
	
</article>
<!-- /.post -->

<?php themify_post_after(); // hook ?>
