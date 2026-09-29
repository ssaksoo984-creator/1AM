</main>

<footer class="site-footer" data-header="light">
	<div class="site-footer__top">
		<div class="site-footer__brand">
			<a class="site-footer__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="1AM home"><?php oneam_logo( 'white' ); ?></a>
			<p class="site-footer__lead">A wholesale-only brand for approved Canadian retailers.</p>
			<?php oneam_member_cta(); ?>
		</div>
		<nav class="site-footer__nav" aria-label="Footer">
			<p class="site-footer__eyebrow">Explore</p>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'container'      => false,
					'fallback_cb'    => 'oneam_fallback_footer_menu',
					'depth'          => 1,
				)
			);
			?>
		</nav>
		<nav class="site-footer__nav" aria-label="Wholesale">
			<p class="site-footer__eyebrow">Wholesale</p>
			<ul>
				<li><a href="<?php echo esc_url( oneam_signup_url() ); ?>">Apply</a></li>
				<li><a href="<?php echo esc_url( oneam_login_url() ); ?>">Log in</a></li>
				<li><a href="<?php echo esc_url( oneam_opt( 'oneam_order_url' ) ); ?>">How to order</a></li>
			</ul>
		</nav>
		<nav class="site-footer__nav" aria-label="Policies">
			<p class="site-footer__eyebrow">Policies</p>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'legal',
					'container'      => false,
					'fallback_cb'    => 'oneam_fallback_legal_menu',
					'depth'          => 1,
				)
			);
			?>
		</nav>
	</div>
	<div class="site-footer__bottom">
		<p class="site-footer__warning"><?php echo esc_html( oneam_opt( 'oneam_warning' ) ); ?></p>
		<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> 1AM. All rights reserved.</p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
