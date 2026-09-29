<?php
/**
 * Flavour slider (used at the top of the Slim HYBRID product page).
 * Args: kicker, title (line breaks allowed), line (product line slug)
 */
$line    = isset( $args['line'] ) ? $args['line'] : 'slim-hybrid';
$flavors = array_values( array_filter( oneam_get_flavors(), function ( $f ) use ( $line ) { return $f['line'] === $line; } ) );
if ( ! $flavors ) {
	return;
}
$total   = count( $flavors );
$title   = explode( "\n", isset( $args['title'] ) ? $args['title'] : "Slim\nHYBRID." );
$kicker  = isset( $args['kicker'] ) ? $args['kicker'] : '2ml Disposable · ' . $total . ' flavours';
?>
<section class="hero" id="hero" data-cursor="Drag">
	<div class="hero__bg"></div>
	<div class="hero__orbs" aria-hidden="true">
		<span class="orb orb--1"></span><span class="orb orb--2"></span><span class="orb orb--3"></span>
	</div>

	<div class="hero__names" aria-hidden="true">
		<?php foreach ( $flavors as $i => $f ) : ?>
			<div class="hero__name<?php echo 0 === $i ? ' is-active' : ''; ?>"><?php echo oneam_split( strtoupper( $f['name'] ) ); // phpcs:ignore ?></div>
		<?php endforeach; ?>
	</div>

	<div class="hero__stage">
		<?php foreach ( $flavors as $i => $f ) : ?>
			<figure class="hero__device<?php echo 0 === $i ? ' is-active' : ''; ?>"
				data-name="<?php echo esc_attr( $f['name'] ); ?>"
				data-desc="<?php echo esc_attr( $f['desc'] ); ?>"
				data-url="<?php echo esc_url( $f['url'] ); ?>"
				data-c1="<?php echo esc_attr( $f['c1'] ); ?>"
				data-c2="<?php echo esc_attr( $f['c2'] ); ?>">
				<img src="<?php echo esc_url( $f['img'] ); ?>" alt="1AM Slim HYBRID <?php echo esc_attr( $f['name'] ); ?>" width="246" height="1400" <?php echo 0 === $i ? 'fetchpriority="high"' : 'loading="lazy"'; ?>>
			</figure>
		<?php endforeach; ?>
		<div class="hero__shadow"></div>
	</div>

	<div class="hero__copy">
		<p class="hero__kicker"><span class="dot"></span><?php echo esc_html( $kicker ); ?></p>
		<h1 class="hero__title">
			<?php foreach ( $title as $t ) : ?>
				<span class="line"><span><?php echo esc_html( $t ); ?></span></span>
			<?php endforeach; ?>
		</h1>
		<div class="hero__btns">
			<?php oneam_member_cta(); ?>
			<a class="btn btn--glass" href="#flavors" data-magnetic><span>All flavours</span></a>
		</div>
	</div>

	<div class="hero__ctrl">
		<div class="hero__info">
			<p class="hero__count"><span id="hero-idx">01</span> / <?php echo esc_html( str_pad( (string) $total, 2, '0', STR_PAD_LEFT ) ); ?></p>
			<p class="hero__flavor" id="hero-flavor"><?php echo esc_html( $flavors[0]['name'] ); ?></p>
			<p class="hero__desc" id="hero-desc"><?php echo esc_html( $flavors[0]['desc'] ); ?></p>
		</div>
		<div class="hero__arrows">
			<button type="button" class="round-btn" data-hero="prev" aria-label="Previous flavour" data-magnetic>&larr;</button>
			<button type="button" class="round-btn" data-hero="next" aria-label="Next flavour" data-magnetic>&rarr;</button>
		</div>
		<div class="hero__progress"><span></span></div>
	</div>

	<div class="hero__dots" role="tablist" aria-label="Flavors">
		<?php foreach ( $flavors as $i => $f ) : ?>
			<button type="button" role="tab" class="<?php echo 0 === $i ? 'is-active' : ''; ?>" style="<?php echo oneam_flavor_style( $f ); // phpcs:ignore ?>" data-idx="<?php echo (int) $i; ?>" aria-label="<?php echo esc_attr( $f['name'] ); ?>"></button>
		<?php endforeach; ?>
	</div>

	<a class="hero__scroll" href="#flavors" aria-label="Scroll"><span>Scroll</span><i></i></a>
</section>
