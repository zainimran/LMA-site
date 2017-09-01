<?php
/**
 * Template for site footer
 * @package themify
 * @since 1.0.0
 */
?>
<?php
/** Themify Default Variables
 @var object */
	global $themify; ?>

	<?php themify_layout_after(); //hook ?>
    </div>
	<!-- /body -->
		
	<div id="footerwrap" <?php echo themify_theme_maybe_do_animated_bg(); ?>>

		<div id="footer-inner">

			<?php themify_footer_before(); // hook ?>
			<footer id="footer" class="pagewidth">
				<?php themify_footer_start(); // hook ?>

				<?php if ( is_active_sidebar( 'footer-social-widget' ) ) : ?>
					<div class="footer-social-widgets">
						<?php dynamic_sidebar( 'footer-social-widget' ); ?>
					</div>
					<!-- /.footer-social-widgets -->
				<?php endif; ?>

				<div class="footer-nav-wrap clearfix">
					<?php if ( function_exists( 'wp_nav_menu' ) ) {
						wp_nav_menu( array( 'theme_location' => 'footer-nav' , 'fallback_cb' => '' , 'container'  => '' , 'menu_id' => 'footer-nav' , 'menu_class' => 'footer-nav' ) );
					} ?>
				</div>
				<!-- /.footer-nav-wrap -->

				<?php get_template_part( 'includes/footer-widgets' ); ?>

				<?php themify_footer_end(); // hook ?>
			</footer>
			<!-- /#footer -->
			<?php themify_footer_after(); // hook ?>

		</div>
		<!-- /.footer-inner -->

	</div>
	<!-- /#footerwrap -->
	
</div>
<!-- /#pagewrap -->

<?php
/**
 *  Stylesheets and Javascript files are enqueued in theme-functions.php
 */
?>

<?php themify_body_end(); // hook ?>
<!-- wp_footer -->
<?php wp_footer(); ?>

</body>
</html>