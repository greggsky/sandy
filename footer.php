<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the id=main div and all content after
 *
 * @package WordPress
 * @subpackage Foghorn
 * @since Foghorn 0.1
 */
?>

	</div><!-- #main -->

	<footer id="interoccupy">
			<div class="about">
				<p>Occupy Sandy is a coordinated relief effort to help distribute resources & volunteers to help neighborhoods and people affected by Hurricane Sandy. We are a coalition of people & organizations who are dedicated to implementing aid and establishing hubs for neighborhood resource distribution. Members of this coalition are from Occupy Wall Street, 350.org, recovers.org and interoccupy.net.</p>
			</div>
			<div class="events">
				<h3>Next meeting:</h3>
				<p>None scheduled. Check back for the next call.</p>
			</div>
			<div class="contact">
				<h3>Contact us:</h3>
				<dl>
					<dt>General Inquiries:</dt>
					<dd><a href="mailto:OccupySandy@interoccupy.net"> OccupySandy@interoccupy.net</a></dd>
					<dt>Press:</dt>
					<dd><a href="mailto:SandyPress@interoccupy.net"> SandyPress@interoccupy.net</a></dd>
				</dl>
			</div>		
	</footer>

	<footer id="colophon" role="contentinfo">
            <div id="site-generator">
            	<?php if ( $footer = of_get_option('footer_text', 0) ) {
					echo $footer;
				} else {
					_e( 'Powered by ', 'foghorn' ); ?><a href="<?php echo esc_url( __( 'http://www.wordpress.org', 'foghorn' ) ); ?>" title="<?php esc_attr_e( 'Semantic Personal Publishing Platform', 'foghorn' ); ?>" rel="generator"><?php _e( 'WordPress', 'foghorn' ); ?></a>
                <?php _e( 'and ', 'foghorn' ); ?><a href="<?php echo esc_url( 'http://wptheming.com/foghorn/' ); ?>" title="<?php esc_attr_e( 'Download the Foghorn Theme', 'foghorn' ); ?>" rel="generator"><?php _e( 'Foghorn', 'foghorn' ); ?></a>
                <?php } ?>
			</div>
	</footer><!-- #colophon -->
	
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>