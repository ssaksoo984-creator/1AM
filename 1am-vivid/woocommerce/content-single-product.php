<?php
/**
 * 1AM 상품 상세 (WooCommerce content-single-product.php 대체)
 * 예전 slim-hybrid.html 과 같은 구성:
 * 맛 슬라이더(woo-sync.php) → 상품 정보(pinfo) → 맛 목록 → Stock 1AM
 * 승인 회원에게만 가격 · 맛 선택 · 장바구니가 상품 정보 안에 함께 보임.
 *
 * @package 1am-vivid
 */

defined( 'ABSPATH' ) || exit;

global $product;

do_action( 'woocommerce_before_single_product' );
if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput
	return;
}

$oneam_line    = oneam_product_flavour_line( $product );
$oneam_flavors = false === $oneam_line ? array() : oneam_flavours_for_line( $oneam_line );
$oneam_colour  = $oneam_flavors ? $oneam_flavors[0] : array(
	'c1' => $product->get_meta( '_oneam_c1' ) ?: '#8A3FFC',
	'c2' => $product->get_meta( '_oneam_c2' ) ?: '#16C75A',
);

// 스펙: 옵션이 아닌 속성 + 맛 수
$oneam_specs = array();
foreach ( $product->get_attributes() as $attr ) {
	if ( $attr->get_variation() || ! $attr->get_visible() ) {
		continue;
	}
	$vals = $attr->is_taxonomy() ? wc_get_product_terms( $product->get_id(), $attr->get_name(), array( 'fields' => 'names' ) ) : $attr->get_options();
	$oneam_specs[ wc_attribute_label( $attr->get_name() ) ] = implode( ', ', $vals );
}
if ( $oneam_flavors ) {
	$oneam_specs['Flavours'] = count( $oneam_flavors );
}
$oneam_label = $product->get_meta( '_oneam_label' );
$oneam_text  = $product->get_short_description() ? $product->get_short_description() : $product->get_description();
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( '', $product ); ?>>
	<section class="pinfo" id="details" style="<?php echo oneam_flavor_style( $oneam_colour ); // phpcs:ignore ?>">
		<div class="pinfo__copy">
			<?php if ( $oneam_label ) : ?>
				<p class="eyebrow"><?php echo esc_html( $oneam_label ); ?></p>
			<?php endif; ?>
			<h1 class="pinfo__title"><?php the_title(); ?></h1>
			<?php if ( $oneam_text ) : ?>
				<p class="pinfo__text"><?php echo esc_html( wp_strip_all_tags( $oneam_text ) ); ?></p>
			<?php endif; ?>
			<?php echo oneam_regions_html( array_keys( oneam_province_prices( $product ) ), $product ); // phpcs:ignore ?>
			<?php if ( 'approved' === oneam_member_state() && 'yes' !== $product->get_meta( '_oneam_soon' ) ) : ?>
				<div class="pinfo__buy">
					<p class="pinfo__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
					<?php woocommerce_template_single_add_to_cart(); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php if ( $oneam_specs ) : ?>
			<dl class="pinfo__specs">
				<?php foreach ( $oneam_specs as $k => $v ) : ?>
					<div><dt><?php echo esc_html( $k ); ?></dt><dd><?php echo esc_html( $v ); ?></dd></div>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>
	</section>
</div>
<?php do_action( 'woocommerce_after_single_product' ); ?>
