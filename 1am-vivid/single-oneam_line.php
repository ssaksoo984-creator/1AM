<?php
/**
 * 상품 페이지 (견적서: 상품 페이지 1·2·3 — 상품 소개 / 맛 소개 / 회원 상태별 버튼)
 */
get_header();

while ( have_posts() ) :
	the_post();
	$slug = get_post_field( 'post_name' );
	$p    = null;
	foreach ( oneam_get_products() as $it ) {
		if ( $it['slug'] === $slug ) {
			$p = $it;
		}
	}
	if ( ! $p ) {
		continue;
	}
	?>
	<section class="pdp" style="<?php echo oneam_flavor_style( $p ); // phpcs:ignore ?>">
		<div class="hero__bg"></div>
		<div class="pdp__name" aria-hidden="true"><?php echo oneam_split( strtoupper( $p['name'] ) ); // phpcs:ignore ?></div>
		<figure class="pdp__device"><?php oneam_product_visual( $p ); ?></figure>
		<div class="pdp__copy">
			<p class="hero__kicker"><span class="dot"></span><?php echo 'soon' === $p['status'] ? 'Coming soon' : 'Available now'; ?> · <?php echo esc_html( $p['tag'] ); ?></p>
			<h1 class="pdp__title"><?php the_title(); ?></h1>
			<p class="pdp__content"><?php echo esc_html( $p['desc'] ); ?></p>
			<?php if ( ! empty( $p['specs'] ) ) : ?>
				<dl class="lab__specs">
					<?php foreach ( $p['specs'] as $k => $v ) : ?>
						<div><dt><?php echo esc_html( $k ); ?></dt><dd><?php echo esc_html( $v ); ?></dd></div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>
			<?php oneam_member_cta(); ?>
		</div>
	</section>

	<?php if ( get_the_content() ) : ?>
		<div class="page-body entry__content"><?php the_content(); ?></div>
	<?php endif; ?>

	<?php
	get_template_part( 'template-parts/flavors', null, array( 'line' => $slug ) );
endwhile;

get_template_part( 'template-parts/steps' );
get_template_part( 'template-parts/cta' );
get_footer();
