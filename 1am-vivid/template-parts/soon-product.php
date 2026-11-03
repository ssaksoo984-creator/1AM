<?php
/**
 * 출시 예정 상품 페이지: 빈 상세 화면 대신 "Launching soon" 화면
 * (실루엣 또는 Home line-up image · 이름 · 라벨 · 설명 · 스펙 · 안내 버튼)
 * args: product (WC_Product), specs (array)
 */
$product = $args['product'];
$specs   = $args['specs'] ?? array();
$p       = oneam_product_by_slug( $product->get_slug() );
if ( ! $p ) {
	$p = array(
		'name'   => $product->get_name(),
		'slug'   => $product->get_slug(),
		'img'    => '',
		'c1'     => $product->get_meta( '_oneam_c1' ) ?: '#8A3FFC',
		'c2'     => $product->get_meta( '_oneam_c2' ) ?: '#16C75A',
		'shape'  => $product->get_meta( '_oneam_shape' ),
	);
}
$label = $product->get_meta( '_oneam_label' );
?>
<section class="soonp" style="<?php echo oneam_flavor_style( $p ); // phpcs:ignore ?>">
	<div class="soonp__visual">
		<span class="soonp__blob" aria-hidden="true"></span>
		<?php oneam_product_visual( $p, 'soonp__img' ); ?>
	</div>
	<div class="soonp__text">
		<nav class="spdp__crumbs" aria-label="Breadcrumb">
			<a href="<?php echo esc_url( home_url( '/#products' ) ); ?>">Products</a>
			<span aria-hidden="true">/</span>
			<span><?php echo esc_html( $product->get_name() ); ?></span>
		</nav>
		<p class="soonp__chips">
			<span class="soonp__chip soonp__chip--soon"><span class="dot"></span>Launching soon</span>
			<?php if ( $label ) : ?>
				<span class="soonp__chip"><?php echo esc_html( $label ); ?></span>
			<?php endif; ?>
		</p>
		<h1 class="soonp__name"><?php echo esc_html( $product->get_name() ); ?></h1>
		<?php if ( $product->get_short_description() ) : ?>
			<div class="soonp__lead"><?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?></div>
		<?php endif; ?>
		<?php if ( $specs ) : ?>
			<dl class="soonp__specs">
				<?php foreach ( $specs as $k => $v ) : ?>
					<div><dt><?php echo esc_html( $k ); ?></dt><dd><?php echo esc_html( $v ); ?></dd></div>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>
		<div class="soonp__box">
			<p><strong>In development.</strong> <?php echo 'approved' === oneam_member_state() ? 'We will email you as soon as it launches.' : 'Approved retailers are emailed as soon as it launches — apply now to be first in line.'; ?></p>
			<div class="soonp__btns">
				<?php if ( 'approved' === oneam_member_state() ) : ?>
					<a class="btn btn--solid" href="<?php echo esc_url( oneam_shop_url() ); ?>"><span>Shop available products &rarr;</span></a>
				<?php else : ?>
					<?php oneam_member_cta(); ?>
				<?php endif; ?>
				<a class="link-arrow" href="<?php echo esc_url( home_url( '/#products' ) ); ?>">See the line-up <span>&rarr;</span></a>
			</div>
		</div>
		<?php if ( trim( wp_strip_all_tags( $product->get_description() ) ) ) : ?>
			<div class="soonp__desc entry__content"><?php echo wp_kses_post( wpautop( $product->get_description() ) ); ?></div>
		<?php endif; ?>
		<p class="spdp__warning"><?php echo esc_html( oneam_health_warning() ); ?></p>
	</div>
</section>
