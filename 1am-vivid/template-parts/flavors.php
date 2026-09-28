<?php
$flavors = oneam_get_flavors();
$cats    = oneam_flavor_categories();
?>
<section class="flavors" id="flavors">
	<div class="flavors__head">
		<h2 class="section-title" data-reveal>
			<span class="line"><span>All</span></span>
			<span class="line"><span><em>the</em> flavors</span></span>
		</h2>
		<div class="chips" role="tablist" aria-label="Filter">
			<button type="button" class="chip is-active" data-filter="all" data-magnetic>All <sup><?php echo count( $flavors ); ?></sup></button>
			<?php foreach ( $cats as $key => $label ) : ?>
				<?php
				$n = count( array_filter( $flavors, function ( $f ) use ( $key ) { return $f['cat'] === $key; } ) );
				if ( ! $n ) {
					continue;
				}
				?>
				<button type="button" class="chip" data-filter="<?php echo esc_attr( $key ); ?>" data-magnetic><?php echo esc_html( $label ); ?> <sup><?php echo (int) $n; ?></sup></button>
			<?php endforeach; ?>
		</div>
	</div>

	<ul class="grid">
		<?php foreach ( $flavors as $f ) : ?>
			<li class="card" data-cat="<?php echo esc_attr( $f['cat'] ); ?>" style="<?php echo oneam_flavor_style( $f ); // phpcs:ignore ?>">
				<a href="<?php echo esc_url( $f['url'] ); ?>" data-cursor="View">
					<span class="card__fill" aria-hidden="true"></span>
					<span class="card__cat"><?php echo esc_html( $cats[ $f['cat'] ] ?? '' ); ?></span>
					<span class="card__img"><img src="<?php echo esc_url( $f['img'] ); ?>" alt="" width="246" height="1400" loading="lazy"></span>
					<span class="card__name"><?php echo esc_html( $f['name'] ); ?></span>
					<span class="card__swatch" aria-hidden="true"></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
