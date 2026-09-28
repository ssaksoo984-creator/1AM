<?php
/**
 * 맛 상세 페이지
 */
get_header();

while ( have_posts() ) :
	the_post();
	$all  = oneam_get_flavors();
	$slug = get_post_field( 'post_name' );
	$idx  = 0;
	foreach ( $all as $i => $f ) {
		if ( $f['slug'] === $slug ) {
			$idx = $i;
			break;
		}
	}
	$f    = $all[ $idx ];
	$next = $all[ ( $idx + 1 ) % count( $all ) ];
	$cats = oneam_flavor_categories();
	?>
	<section class="pdp" style="<?php echo oneam_flavor_style( $f ); // phpcs:ignore ?>">
		<div class="hero__bg"></div>
		<div class="pdp__name" aria-hidden="true"><?php echo oneam_split( strtoupper( $f['name'] ) ); // phpcs:ignore ?></div>
		<figure class="pdp__device"><img src="<?php echo esc_url( $f['img'] ); ?>" alt="<?php echo esc_attr( $f['name'] ); ?>" width="246" height="1400"></figure>
		<div class="pdp__copy">
			<p class="hero__kicker"><span class="dot"></span><?php echo esc_html( $cats[ $f['cat'] ] ?? '' ); ?> · Slim HYBRID</p>
			<h1 class="pdp__title"><?php the_title(); ?></h1>
			<div class="pdp__content"><?php the_content(); ?></div>
			<?php if ( ! get_the_content() ) : ?>
				<p class="pdp__content"><?php echo esc_html( $f['desc'] ); ?></p>
			<?php endif; ?>
			<a class="btn btn--solid" href="<?php echo esc_url( oneam_opt( 'oneam_cta_url' ) ); ?>" data-magnetic><span>Where to buy</span></a>
		</div>
	</section>

	<a class="pdp-next" href="<?php echo esc_url( $next['url'] ); ?>" style="<?php echo oneam_flavor_style( $next ); // phpcs:ignore ?>" data-cursor="Next">
		<span class="eyebrow">Next flavor</span>
		<span class="pdp-next__name"><?php echo esc_html( $next['name'] ); ?></span>
		<img src="<?php echo esc_url( $next['img'] ); ?>" alt="" width="246" height="1400" loading="lazy">
	</a>
	<?php
endwhile;

get_footer();
