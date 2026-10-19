<?php
/**
 * About — 우리가 만드는 것 (상품 이미지 + 맛 분류 + 출시 예정)
 */
$flavors  = oneam_get_flavors();
$products = oneam_get_products();
$main     = $products[0];
$soon     = array_filter( $products, function ( $p ) { return 'soon' === $p['status']; } );
$fan      = array_slice( $flavors, 0, 5 );
$counts   = array();
foreach ( $flavors as $f ) {
	$counts[ $f['cat'] ] = isset( $counts[ $f['cat'] ] ) ? $counts[ $f['cat'] ] + 1 : 1;
}
?>
<section class="make">
	<div class="make__card" style="<?php echo oneam_flavor_style( $main ); // phpcs:ignore ?>">
		<div class="make__visual" aria-hidden="true">
			<?php foreach ( $fan as $i => $f ) : ?>
				<img src="<?php echo esc_url( $f['img'] ); ?>" alt="" width="246" height="1400" loading="lazy" style="--o:<?php echo esc_attr( $i - ( count( $fan ) - 1 ) / 2 ); ?>">
			<?php endforeach; ?>
		</div>
		<div class="make__text">
			<p class="eyebrow"><?php echo esc_html( oneam_ctx( 'eyebrow', 'What we make' ) ); ?></p>
			<h2 class="make__title"><?php echo oneam_rich( oneam_ctx( 'title', $main['name'] ) ); // phpcs:ignore ?></h2>
			<p class="make__desc"><?php echo esc_html( oneam_ctx( 'text', 'A slim disposable with a crystal-clear body, 2ml of e-liquid and ' . count( $flavors ) . ' flavours across three families.' ) ); ?></p>
			<ul class="make__fams">
				<?php foreach ( oneam_flavor_categories() as $key => $label ) : ?>
					<li><?php echo esc_html( $label ); ?> <sup><?php echo (int) ( isset( $counts[ $key ] ) ? $counts[ $key ] : 0 ); ?></sup></li>
				<?php endforeach; ?>
			</ul>
			<?php if ( $soon ) : ?>
				<p class="make__soon"><span class="dot"></span>In development: <?php echo esc_html( implode( ' · ', wp_list_pluck( $soon, 'name' ) ) ); ?></p>
			<?php endif; ?>
			<a class="btn btn--solid" href="<?php echo esc_url( $main['url'] ); ?>" data-magnetic><span>View <?php echo esc_html( $main['name'] ); ?> &rarr;</span></a>
		</div>
	</div>
</section>
