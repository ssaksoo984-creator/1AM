<?php
/**
 * 상품 라인업 — 세로로 쌓이는 카드 (스크롤을 가로채지 않음, 트랙패드에서도 부드럽게)
 * 상품은 WooCommerce 에서 자동으로. Elementor 위젯에서 "Custom products" 를 채우면 그 내용으로 대신 표시.
 */
$products = oneam_get_products();
$custom   = oneam_ctx( 'items', array() );
if ( $custom ) {
	$products = array();
	foreach ( $custom as $i => $r ) {
		$soon       = ! empty( $r['soon'] ) && 'yes' === $r['soon'];
		$products[] = array(
			'name'   => isset( $r['name'] ) ? $r['name'] : '',
			'slug'   => 'custom-' . $i,
			'tag'    => isset( $r['tag'] ) ? $r['tag'] : '',
			'desc'   => isset( $r['desc'] ) ? $r['desc'] : '',
			'img'    => isset( $r['image']['url'] ) ? $r['image']['url'] : '',
			'c1'     => ! empty( $r['c1'] ) ? $r['c1'] : '#8A3FFC',
			'c2'     => ! empty( $r['c2'] ) ? $r['c2'] : '#16C75A',
			'url'    => isset( $r['link']['url'] ) && $r['link']['url'] ? $r['link']['url'] : oneam_signup_url(),
			'status' => $soon ? 'soon' : 'available',
			'specs'  => array(),
		);
	}
}
if ( ! $products ) {
	return;
}
?>
<section class="plist" id="products">
	<div class="plist__head">
		<p class="eyebrow"><?php echo esc_html( oneam_ctx( 'eyebrow', 'Product lineup' ) ); ?> · <?php echo count( $products ); ?> lines</p>
		<h2 class="section-title section-title--md" data-reveal><?php echo oneam_lines( oneam_ctx( 'title', "Three lines,\n*one standard.*" ) ); // phpcs:ignore ?></h2>
	</div>
	<div class="plist__items">
		<?php foreach ( $products as $i => $p ) : ?>
			<article class="pitem<?php echo 'soon' === $p['status'] ? ' is-soon' : ''; ?><?php echo $i % 2 ? ' is-flip' : ''; ?>" style="<?php echo oneam_flavor_style( $p ); // phpcs:ignore ?>;--i:<?php echo (int) $i; ?>">
				<div class="pitem__visual">
					<span class="pitem__num" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
					<span class="pitem__blob" aria-hidden="true"></span>
					<?php oneam_product_visual( $p, 'pitem__img' ); ?>
				</div>
				<div class="pitem__text">
					<p class="pitem__status"><span class="dot"></span><?php echo 'soon' === $p['status'] ? 'Coming soon' : 'Available now'; ?></p>
					<h3 class="pitem__name"><?php echo esc_html( $p['name'] ); ?></h3>
					<?php if ( $p['tag'] ) : ?>
						<p class="pitem__tag"><?php echo esc_html( $p['tag'] ); ?></p>
					<?php endif; ?>
					<?php if ( $p['desc'] ) : ?>
						<p class="pitem__desc"><?php echo esc_html( $p['desc'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $p['specs'] ) ) : ?>
						<dl class="pitem__specs">
							<?php foreach ( $p['specs'] as $k => $v ) : ?>
								<div><dt><?php echo esc_html( $k ); ?></dt><dd><?php echo esc_html( $v ); ?></dd></div>
							<?php endforeach; ?>
						</dl>
					<?php endif; ?>
					<?php if ( 'soon' === $p['status'] ) : ?>
						<a class="btn btn--solid" href="<?php echo esc_url( oneam_signup_url() ); ?>" data-magnetic><span>Get notified</span></a>
					<?php else : ?>
						<a class="btn btn--solid" href="<?php echo esc_url( $p['url'] ); ?>" data-magnetic><span>View product &rarr;</span></a>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</section>
