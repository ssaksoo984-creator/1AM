</main>

<footer class="site-footer" data-header="light">
	<div class="site-footer__top">
		<div class="site-footer__col">
			<p class="site-footer__eyebrow">Stay up late</p>
			<p class="site-footer__big">Don't sleep on<br><em>flavor.</em></p>
		</div>
		<nav class="site-footer__nav" aria-label="Footer">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'container'      => false,
					'fallback_cb'    => 'oneam_fallback_menu',
					'depth'          => 1,
				)
			);
			?>
		</nav>
	</div>
	<div class="site-footer__logo" data-footer-logo>
		<?php oneam_logo( 'white' ); ?>
	</div>
	<div class="site-footer__bottom">
		<p class="site-footer__warning"><?php echo esc_html( oneam_opt( 'oneam_warning' ) ); ?></p>
		<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> 1AM. All rights reserved.</p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
