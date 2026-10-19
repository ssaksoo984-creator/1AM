<?php
/**
 * 상품 정보 (예전 slim-hybrid.html 의 pinfo)
 * args: product (oneam_get_products() 형식) / Elementor 위젯에서는 ctx 로 글자 · 스펙을 바꿀 수 있음
 * 승인 회원이고 WooCommerce 에 같은 상품이 있으면 가격 · 맛 선택 · 장바구니도 표시
 */
$slug = oneam_ctx( 'slug', isset( $args['product']['slug'] ) ? $args['product']['slug'] : 'slim-hybrid' );
$p    = isset( $args['product'] ) ? $args['product'] : oneam_product_by_slug( $slug );
if ( ! $p ) {
	$p = array( 'name' => '', 'tag' => '', 'desc' => '', 'specs' => array(), 'c1' => '#8A3FFC', 'c2' => '#16C75A', 'slug' => $slug, 'status' => 'available' );
}
$specs = $p['specs'];
$rows  = oneam_ctx( 'specs', array() );
if ( $rows ) {
	$specs = array();
	foreach ( $rows as $r ) {
		if ( ! empty( $r['label'] ) ) {
			$specs[ $r['label'] ] = isset( $r['value'] ) ? $r['value'] : '';
		}
	}
}
$woo = ( function_exists( 'wc_get_product' ) && post_type_exists( 'product' ) ) ? get_page_by_path( $slug, OBJECT, 'product' ) : null;
$woo = ( $woo && 'publish' === $woo->post_status ) ? wc_get_product( $woo->ID ) : null;
?>
<section class="pinfo" id="details" style="<?php echo oneam_flavor_style( $p ); // phpcs:ignore ?>">
	<div class="pinfo__copy">
		<?php $label = oneam_ctx( 'label', $p['tag'] ); ?>
		<?php if ( $label ) : ?>
			<p class="eyebrow"><?php echo esc_html( $label ); ?></p>
		<?php endif; ?>
		<h1 class="pinfo__title"><?php echo esc_html( oneam_ctx( 'title', $p['name'] ) ); ?></h1>
		<?php $text = oneam_ctx( 'text', $p['desc'] ); ?>
		<?php if ( $text ) : ?>
			<p class="pinfo__text"><?php echo esc_html( $text ); ?></p>
		<?php endif; ?>
		<?php echo oneam_regions_html( $woo ? array_keys( oneam_province_prices( $woo ) ) : ( $p['regions'] ?? array() ), $woo ); // phpcs:ignore ?>
		<?php if ( $woo && 'approved' === oneam_member_state() && 'yes' !== $woo->get_meta( '_oneam_soon' ) && ! oneam_is_builder_preview() ) : ?>
			<div class="pinfo__buy">
				<p class="pinfo__price"><?php echo wp_kses_post( $woo->get_price_html() ); ?></p>
				<?php
				global $product;
				$oneam_prev = $product;
				$product    = $woo; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
				if ( $woo->is_type( 'variable' ) ) {
					wp_enqueue_script( 'wc-add-to-cart-variation' );
				}
				woocommerce_template_single_add_to_cart();
				$product = $oneam_prev; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
				?>
			</div>
		<?php endif; ?>
	</div>
	<?php if ( $specs ) : ?>
		<dl class="pinfo__specs">
			<?php foreach ( $specs as $k => $v ) : ?>
				<div><dt><?php echo esc_html( $k ); ?></dt><dd><?php echo esc_html( $v ); ?></dd></div>
			<?php endforeach; ?>
		</dl>
	<?php endif; ?>
</section>
