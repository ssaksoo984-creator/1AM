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
		<p class="site-footer__warning"><?php echo esc_html( oneam_health_warning( 'oneam_warning' ) ); ?></p>
		<p>
			Distributed by <?php echo esc_html( oneam_opt( 'oneam_company' ) ); ?>
			<?php if ( oneam_opt( 'oneam_address' ) ) : ?>
				· <?php echo esc_html( preg_replace( '/\s*\r?\n\s*/', ', ', trim( oneam_opt( 'oneam_address' ) ) ) ); ?>
			<?php endif; ?>
			· <a href="mailto:<?php echo esc_attr( oneam_opt( 'oneam_email' ) ); ?>"><?php echo esc_html( oneam_opt( 'oneam_email' ) ); ?></a>
			<?php if ( oneam_opt( 'oneam_phone' ) ) : ?>
				· <a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', oneam_opt( 'oneam_phone' ) ) ); ?>"><?php echo esc_html( oneam_opt( 'oneam_phone' ) ); ?></a>
			<?php endif; ?>
			<?php if ( oneam_gst_line() ) : ?>
				· <?php echo esc_html( oneam_gst_line() ); ?>
			<?php endif; ?>
			<br>
			<?php echo esc_html( oneam_opt( 'oneam_payments' ) ); ?> · Prices in CAD<br>
			&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> 1AM. All rights reserved.
		</p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
