<?php
$oneam_flavors = oneam_get_flavors();
$oneam_first   = $oneam_flavors[0];
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'is-loading' ); ?> style="<?php echo oneam_flavor_style( $oneam_first ); // phpcs:ignore ?>">
<?php wp_body_open(); ?>

<?php if ( get_theme_mod( 'oneam_age_gate', true ) ) : ?>
<div class="agegate" id="agegate" role="dialog" aria-modal="true" aria-labelledby="agegate-title" hidden>
	<div class="agegate__blob"></div>
	<div class="agegate__box">
		<?php oneam_logo( 'black', 'agegate__logo' ); ?>
		<p class="agegate__eyebrow">Adults only</p>
		<h2 id="agegate-title" class="agegate__title">Are you <em><?php echo (int) oneam_opt( 'oneam_min_age' ); ?>+</em>?</h2>
		<p class="agegate__txt">You must be <?php echo (int) oneam_opt( 'oneam_min_age' ); ?> or older to enter this site.</p>
		<div class="agegate__btns">
			<button type="button" class="btn btn--solid" data-age="yes" data-magnetic><span>Yes, I am</span></button>
			<button type="button" class="btn btn--ghost" data-age="no" data-magnetic><span>No</span></button>
		</div>
	</div>
</div>
<?php endif; ?>

<div class="loader" id="loader" aria-hidden="true">
	<div class="loader__bg"></div>
	<div class="loader__inner">
		<?php oneam_logo( 'white', 'loader__logo' ); ?>
		<div class="loader__count" id="loader-num">00:00 AM</div>
	</div>
</div>

<div class="cursor" aria-hidden="true"><span class="cursor__dot"></span><span class="cursor__label"></span></div>

<div class="topbar" role="note"><p><?php echo esc_html( oneam_opt( 'oneam_topbar' ) ); ?></p></div>

<header class="site-header" id="site-header">
	<a class="site-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="1AM home">
		<?php oneam_logo( 'black' ); ?>
	</a>
	<nav class="site-nav" id="site-nav" aria-label="Primary">
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'depth'          => 2,
				'fallback_cb'    => 'oneam_fallback_menu',
			)
		);
		?>
		<div class="site-nav__actions">
			<?php $oneam_state = oneam_member_state(); ?>
			<?php if ( 'guest' === $oneam_state ) : ?>
				<a class="site-nav__login" href="<?php echo esc_url( oneam_login_url() ); ?>">Log in</a>
			<?php elseif ( 'pending' === $oneam_state ) : ?>
				<a class="site-nav__login" href="<?php echo esc_url( oneam_login_url() ); ?>">My account</a>
			<?php endif; ?>
			<?php oneam_member_cta( 'btn--sm' ); ?>
		</div>
	</nav>
	<button class="nav-toggle" id="menu-btn" type="button" aria-expanded="false" aria-controls="site-nav" aria-label="Menu">
		<i></i><i></i>
	</button>
</header>

<main id="main" class="site-main">
