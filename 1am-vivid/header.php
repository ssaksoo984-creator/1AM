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
		<p class="agegate__txt">이 사이트는 만 <?php echo (int) oneam_opt( 'oneam_min_age' ); ?>세 이상 성인만 이용할 수 있습니다.</p>
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
	<nav class="site-header__quick" aria-label="Quick">
		<a href="<?php echo esc_url( home_url( '/#products' ) ); ?>" data-magnetic>Products</a>
		<a href="<?php echo esc_url( oneam_opt( 'oneam_about_url' ) ); ?>" data-magnetic>About</a>
		<a href="<?php echo esc_url( oneam_opt( 'oneam_faq_url' ) ); ?>" data-magnetic>FAQ</a>
		<?php if ( 'approved' === oneam_member_state() ) : ?>
			<a class="is-accent" href="<?php echo esc_url( oneam_shop_url() ); ?>" data-magnetic>Shop</a>
		<?php elseif ( 'pending' === oneam_member_state() ) : ?>
			<a class="is-accent" href="<?php echo esc_url( oneam_login_url() ); ?>" data-magnetic>My account</a>
		<?php else : ?>
			<a href="<?php echo esc_url( oneam_login_url() ); ?>" data-magnetic>Log in</a>
			<a class="is-accent" href="<?php echo esc_url( oneam_signup_url() ); ?>" data-magnetic>Apply</a>
		<?php endif; ?>
	</nav>
	<button class="menu-btn" id="menu-btn" type="button" aria-expanded="false" aria-controls="menu" data-magnetic>
		<span class="menu-btn__label">Menu</span>
		<span class="menu-btn__lines"><i></i><i></i></span>
	</button>
</header>

<div class="menu" id="menu" aria-hidden="true">
	<div class="menu__bg"></div>
	<div class="menu__inner">
		<nav class="menu__nav" aria-label="Primary">
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
		</nav>
		<div class="menu__side">
			<p class="menu__eyebrow">Wholesale</p>
			<p class="menu__lead">승인된 도매 거래처만 주문할 수 있습니다.</p>
			<?php oneam_member_cta(); ?>
			<p class="menu__eyebrow">Slim HYBRID flavours</p>
			<div class="menu__flavors">
				<?php foreach ( array_slice( $oneam_flavors, 0, 6 ) as $f ) : ?>
					<a href="<?php echo esc_url( $f['url'] ); ?>" style="<?php echo oneam_flavor_style( $f ); // phpcs:ignore ?>"><?php echo esc_html( $f['name'] ); ?></a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</div>

<main id="main" class="site-main">
