<?php
/**
 * 1AM 쇼핑몰 상품 페이지 (WooCommerce content-single-product.php 대체)
 *
 * 왼쪽: 상품 이미지(맛을 고르면 그 맛 이미지로) / 오른쪽: 옵션 · 수량 · 가격 · 장바구니(페이지 이동 없이 담김)
 * 아래: 상품 설명 · 맛 특징 · 판매 지역/소비세 스탬프 · 배송/반품 · 컴플라이언스 (리뷰 없음)
 * 브랜드 소개(맛 슬라이더 등)는 소개 페이지(/slim-hybrid/)에 있습니다.
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

$oneam_state   = oneam_member_state();
$oneam_soon    = 'yes' === $product->get_meta( '_oneam_soon' );
$oneam_line    = oneam_product_flavour_line( $product );
$oneam_flavors = false === $oneam_line ? array() : oneam_flavours_for_line( $oneam_line );
$oneam_first   = $oneam_flavors ? $oneam_flavors[0] : array( 'c1' => $product->get_meta( '_oneam_c1' ) ?: '#8A3FFC', 'c2' => $product->get_meta( '_oneam_c2' ) ?: '#16C75A' );
$oneam_pp      = oneam_province_prices( $product );
$oneam_cats    = oneam_flavor_categories();
$oneam_prov    = oneam_customer_province();
$oneam_intro   = get_page_by_path( $product->get_slug() );

// 스펙: 옵션이 아닌 속성
$oneam_specs = array();
foreach ( $product->get_attributes() as $attr ) {
	if ( $attr->get_variation() || ! $attr->get_visible() ) {
		continue;
	}
	$vals = $attr->is_taxonomy() ? wc_get_product_terms( $product->get_id(), $attr->get_name(), array( 'fields' => 'names' ) ) : $attr->get_options();
	$oneam_specs[ wc_attribute_label( $attr->get_name() ) ] = implode( ', ', $vals );
}
// 니코틴 함량 (상품 편집 > Nicotine concentration)
if ( oneam_pack_count( $product ) ) {
	$oneam_specs['Per carton'] = oneam_pack_count( $product ) . ' pcs';
}
if ( $product->get_meta( '_oneam_nicotine' ) ) {
	$oneam_specs['Nicotine'] = $product->get_meta( '_oneam_nicotine' );
}

// 옵션 버튼 색 (맛 이름 → 색)
$oneam_swatch = array();
foreach ( $oneam_flavors as $f ) {
	$oneam_swatch[ strtolower( $f['name'] ) ] = array( $f['c1'], $f['c2'] );
}

$oneam_lines = function ( $key ) {
	return array_filter( array_map( 'trim', preg_split( '/\r?\n/', (string) oneam_opt( $key ) ) ) );
};
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'spdp', $product ); ?> style="<?php echo oneam_flavor_style( $oneam_first ); // phpcs:ignore ?>;--img-bg:<?php echo esc_attr( oneam_opt( 'oneam_shop_img_bg' ) ?: '#ffffff' ); ?>">

	<nav class="spdp__crumbs" aria-label="Breadcrumb">
		<a href="<?php echo esc_url( oneam_shop_url() ); ?>">Shop</a>
		<span aria-hidden="true">/</span>
		<span><?php the_title(); ?></span>
		<?php if ( $oneam_intro && 'publish' === $oneam_intro->post_status ) : ?>
			<a class="spdp__about" href="<?php echo esc_url( get_permalink( $oneam_intro ) ); ?>">About this product &rarr;</a>
		<?php endif; ?>
	</nav>

	<div class="spdp__main">
		<div class="spdp__gallery">
			<?php woocommerce_show_product_images(); ?>
		</div>

		<div class="spdp__summary">
			<p class="spdp__chips">
				<span class="spdp__chip spdp__chip--status"><span class="dot"></span><?php echo $oneam_soon ? 'Coming soon' : 'In stock for approved retailers'; ?></span>
				<?php if ( $product->get_meta( '_oneam_label' ) ) : ?>
					<span class="spdp__chip"><?php echo esc_html( $product->get_meta( '_oneam_label' ) ); ?></span>
				<?php endif; ?>
			</p>
			<h1 class="spdp__name"><?php the_title(); ?></h1>
			<?php if ( $product->get_short_description() ) : ?>
				<div class="spdp__lead"><?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?></div>
			<?php endif; ?>

			<?php if ( $oneam_specs ) : ?>
				<dl class="spdp__specs">
					<?php foreach ( $oneam_specs as $k => $v ) : ?>
						<div><dt><?php echo esc_html( $k ); ?></dt><dd><?php echo esc_html( $v ); ?></dd></div>
					<?php endforeach; ?>
					<?php if ( $oneam_flavors ) : ?>
						<div><dt>Flavours</dt><dd><?php echo count( $oneam_flavors ); ?></dd></div>
					<?php endif; ?>
				</dl>
			<?php endif; ?>

			<?php if ( $oneam_pp ) : ?>
				<div class="spdp__regions">
					<p class="spdp__label"><?php echo 'approved' === $oneam_state ? 'Choose your sales region' : 'Sales regions'; ?></p>
					<?php echo oneam_regions_html( array_keys( $oneam_pp ), $product ); // phpcs:ignore ?>
				</div>
			<?php endif; ?>

			<?php if ( $oneam_soon ) : ?>
				<div class="spdp__gate">
					<p><strong>In development.</strong> Approved retailers will be notified when it launches.</p>
					<?php oneam_member_cta(); ?>
				</div>
			<?php elseif ( 'approved' === $oneam_state ) : ?>
				<div class="spdp__buy" data-swatches="<?php echo esc_attr( wp_json_encode( $oneam_swatch ) ); ?>">
					<div class="spdp__price">
						<?php woocommerce_template_single_price(); ?>
						<?php if ( $oneam_pp ) : ?>
							<p class="spdp__prov">
								<?php if ( $oneam_prov && isset( $oneam_pp[ $oneam_prov ] ) ) : ?>
									Price for <strong><?php echo esc_html( $oneam_prov ); ?></strong> (your shipping province). Change it at checkout.
								<?php else : ?>
									Your final price follows the shipping province you choose at checkout.
								<?php endif; ?>
							</p>
						<?php endif; ?>
					</div>
					<?php if ( function_exists( 'oneam_min_order_line' ) && oneam_min_order_line() ) : ?>
						<p class="spdp__min"><?php echo esc_html( oneam_min_order_line() ); ?></p>
					<?php endif; ?>
					<?php woocommerce_template_single_add_to_cart(); ?>
					<div class="spdp__added" hidden role="status" aria-live="polite">
						<p class="spdp__added-msg"></p>
						<p class="spdp__added-cart"></p>
						<div class="spdp__added-btns">
							<a class="btn btn--solid" href="<?php echo esc_url( wc_get_checkout_url() ); ?>"><span>Checkout &rarr;</span></a>
							<a class="link-arrow" href="<?php echo esc_url( wc_get_cart_url() ); ?>">View cart <span>&rarr;</span></a>
						</div>
					</div>
				</div>
			<?php else : ?>
				<div class="spdp__gate">
					<p><strong><?php echo 'pending' === $oneam_state ? 'Your application is under review.' : 'Wholesale pricing and ordering are for approved retailers.'; ?></strong>
					<?php echo 'pending' === $oneam_state ? 'We will email you as soon as your account is approved.' : 'Apply for a retail account, or log in to order.'; ?></p>
					<div class="spdp__gate-btns">
						<?php oneam_member_cta(); ?>
						<?php if ( 'guest' === $oneam_state ) : ?>
							<a class="link-arrow" href="<?php echo esc_url( oneam_login_url() ); ?>">Log in <span>&rarr;</span></a>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>

			<ul class="spdp__facts">
				<li><?php echo oneam_icon( 'truck' ); // phpcs:ignore ?> Ships within <?php echo esc_html( oneam_opt( 'oneam_dispatch_time' ) ); ?> of payment · delivery <?php echo esc_html( oneam_opt( 'oneam_transit_time' ) ); ?> after shipping</li>
				<li><?php echo oneam_icon( 'check' ); // phpcs:ignore ?> All products carry the correct excise stamp for their intended province of sale</li>
				<li><?php echo oneam_icon( 'card' ); // phpcs:ignore ?> <?php echo esc_html( oneam_opt( 'oneam_payments' ) ); ?> · Prices in CAD before GST</li>
				<li><?php echo oneam_icon( 'check' ); // phpcs:ignore ?> <?php echo oneam_opt( 'oneam_processor' ) ? 'Secure payment by ' . esc_html( oneam_opt( 'oneam_processor' ) ) . ' · ' : ''; ?>All sales final</li>
			</ul>

			<p class="spdp__warning"><?php echo esc_html( oneam_health_warning() ); ?></p>
		</div>
	</div>

	<div class="spdp__info">
		<?php if ( trim( wp_strip_all_tags( get_the_content() ) ) ) : ?>
			<details class="spdp__acc" open>
				<summary>Description</summary>
				<div class="spdp__acc-body entry__content"><?php the_content(); ?></div>
			</details>
		<?php endif; ?>

		<?php if ( $oneam_flavors ) : ?>
			<details class="spdp__acc" <?php echo trim( wp_strip_all_tags( get_the_content() ) ) ? '' : 'open'; ?>>
				<summary>Flavour profiles <sup><?php echo count( $oneam_flavors ); ?></sup></summary>
				<div class="spdp__acc-body">
					<ul class="spdp__flavours">
						<?php foreach ( $oneam_flavors as $f ) : ?>
							<li style="<?php echo oneam_flavor_style( $f ); // phpcs:ignore ?>">
								<span class="spdp__dot" aria-hidden="true"></span>
								<strong><?php echo esc_html( $f['name'] ); ?></strong>
								<em><?php echo esc_html( $oneam_cats[ $f['cat'] ] ?? '' ); ?></em>
								<span><?php echo esc_html( $f['desc'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</details>
		<?php endif; ?>

		<details class="spdp__acc">
			<summary>Sales regions &amp; excise stamps</summary>
			<div class="spdp__acc-body">
				<?php if ( $oneam_pp ) : ?>
					<table class="spdp__table">
						<thead><tr><th>Province</th><th>Excise stamp</th><?php echo 'approved' === $oneam_state ? '<th>Price / ' . esc_html( oneam_unit_label( $product ) ) . '</th>' : ''; ?></tr></thead>
						<tbody>
							<?php foreach ( $oneam_pp as $code => $price ) : ?>
								<tr>
									<td><?php echo esc_html( oneam_provinces()[ $code ] ?? $code ); ?></td>
									<td><?php echo esc_html( oneam_stamp_label( $code ) ); ?></td>
									<?php if ( 'approved' === $oneam_state ) : ?>
										<td><?php echo wp_kses_post( wc_price( $price ) ); ?> + GST</td>
									<?php endif; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
				<?php foreach ( $oneam_lines( 'oneam_shop_regions' ) as $line ) : ?>
					<p><?php echo esc_html( $line ); ?></p>
				<?php endforeach; ?>
			</div>
		</details>

		<details class="spdp__acc">
			<summary>Shipping &amp; returns</summary>
			<div class="spdp__acc-body">
				<p><?php echo esc_html( oneam_shipping_time_sentence() ); ?></p>
				<?php foreach ( $oneam_lines( 'oneam_shop_shipping' ) as $line ) : ?>
					<p><?php echo esc_html( $line ); ?></p>
				<?php endforeach; ?>
				<p><a class="link-arrow" href="<?php echo esc_url( home_url( '/return-policy/' ) ); ?>">Return Policy <span>&rarr;</span></a></p>
			</div>
		</details>

		<details class="spdp__acc">
			<summary>Compliance &amp; terms</summary>
			<div class="spdp__acc-body">
				<ul class="spdp__points">
					<?php foreach ( $oneam_lines( 'oneam_shop_compliance' ) as $line ) : ?>
						<li><?php echo esc_html( $line ); ?></li>
					<?php endforeach; ?>
				</ul>
				<p><a class="link-arrow" href="<?php echo esc_url( home_url( '/terms/#vape-compliance' ) ); ?>">Vaping Product Compliance terms <span>&rarr;</span></a></p>
			</div>
		</details>
	</div>
</div>
<?php do_action( 'woocommerce_after_single_product' ); ?>
