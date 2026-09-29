<?php
/**
 * Product page (Products 1 · 2 · 3): product intro, flavours, member-aware button.
 * Available lines with flavours open with the flavour slider.
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
	$has_flavours = (bool) array_filter( oneam_get_flavors(), function ( $f ) use ( $slug ) { return $f['line'] === $slug; } );

	if ( 'available' === $p['status'] && $has_flavours ) :
		get_template_part(
			'template-parts/hero',
			null,
			array(
				'line'   => $slug,
				'title'  => $p['name'] . '.',
				'kicker' => $p['tag'],
			)
		);
		?>
		<section class="pinfo" style="<?php echo oneam_flavor_style( $p ); // phpcs:ignore ?>">
			<div class="pinfo__copy">
				<p class="eyebrow"><?php echo esc_html( $p['tag'] ); ?></p>
				<h2 class="pinfo__title"><?php echo esc_html( $p['name'] ); ?></h2>
				<p class="pinfo__text"><?php echo esc_html( $p['desc'] ); ?></p>
				<?php if ( get_the_content() ) : ?>
					<div class="entry__content"><?php the_content(); ?></div>
				<?php endif; ?>
			</div>
			<?php if ( ! empty( $p['specs'] ) ) : ?>
				<dl class="pinfo__specs">
					<?php foreach ( $p['specs'] as $k => $v ) : ?>
						<div><dt><?php echo esc_html( $k ); ?></dt><dd><?php echo esc_html( $v ); ?></dd></div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>
		</section>
		<?php
		get_template_part( 'template-parts/flavors', null, array( 'line' => $slug ) );
	else :
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
	endif;
endwhile;

get_template_part( 'template-parts/cta' );
get_footer();
