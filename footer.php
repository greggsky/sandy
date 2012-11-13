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
				<p>Follow us: <a class="socialgram" href="http://twitter.com/occupysandy" target="_blank">&#62217;</a> <a class="socialgram" href="http://facebook.com/occupysandy" target="_blank">&#62220;</a></p>
	
			</div>
			<!--
			<div class="events">
				<h3 class="events-title">Next meeting:</h3>
				<p>None scheduled. Check back soon for the next call.</p>
			</div>
			-->
			<div class="weather">
				<h3 class="footer-title"><a href="http://occupyweather.tumblr.com/">OccuWeather Update:</a></h3>
				<script src="http://widgets.twimg.com/j/2/widget.js"></script><!-- first box --><script>
				new TWTR.Widget({
				  version: 2,
				  type: 'profile',
				  rpp: 6,
				  interval: 6000,
				  width: 400,
				  height: 180,
				  theme: {
					shell: {
					  background: 'transparent',
					  color: '#222'
					},
					tweets: {
					  background: 'transparent',
					  color: '#222',
					  links: '#0085BF'
					}
				  },
				  features: {
					scrollbar: true,
					loop: false,
					live: false,
					hashtags: true,
					timestamp: true,
					avatars: false,
					behavior: 'all'
				  }
				}).render().setUser('Occuweather').start();
				</script>
				
				<!--
				<p>Cold front brings end to warm conditions on Tuesday. Rain will be heavy at times. Areas affected by Hurricane Sandy should prepare for flooding due to backed up storm drains.</p>
				<h5 class="footer-updated">Updated Nov 12 at 1:41pm</h5>-->
			</div>		
			<div class="contact">
				<h3 class="footer-title">Contact us:</h3>
				<p><span>General Inquiries:</span><a href="mailto:OccupySandy@interoccupy.net"> OccupySandy@interoccupy.net</a></p>
				<p><span>Press:</span><a href="mailto:SandyPress@interoccupy.net"> SandyPress@interoccupy.net</a></p>
				<p><span>Medics:</span><a href="mailto:SandyMedics@interoccupy.net"> SandyMedics@interoccupy.net</a></p>
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