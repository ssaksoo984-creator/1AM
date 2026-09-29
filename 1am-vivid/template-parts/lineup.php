<?php
/**
 * 상품 라인업 — 세로 스크롤하면 가로로 넘어가는 핀 섹션 (견적서: 취급 상품 3종 소개)
 */
$products = oneam_get_products();
?>
<section class="lab" id="products">
	<div class="lab__bg"></div>
	<div class="lab__head">
		<p class="eyebrow">Product lineup · <?php echo count( $products ); ?> lines</p>
		<p class="lab__hint">Keep scrolling &rarr;</p>
	</div>
	<div class="lab__track">
		<?php foreach ( $products as $i => $p ) : ?>
			<article class="lab__panel<?php echo 'soon' === $p['status'] ? ' is-soon' : ''; ?>" style="<?php echo oneam_flavor_style( $p ); // phpcs:ignore ?>" data-c1="<?php echo esc_attr( $p['c1'] ); ?>" data-c2="<?php echo esc_attr( $p['c2'] ); ?>">
				<div class="lab__num"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></div>
				<div class="lab__blob" aria-hidden="true"></div>
				<div class="lab__visual">
					<?php oneam_product_visual( $p, 'lab__img' ); ?>
				</div>
				<div class="lab__text">
					<p class="lab__status"><span class="dot"></span><?php echo 'soon' === $p['status'] ? 'Coming soon' : 'Available now'; ?></p>
					<h2 class="lab__name"><?php echo oneam_split( $p['name'] ); // phpcs:ignore ?></h2>
					<p class="lab__tag"><?php echo esc_html( $p['tag'] ); ?></p>
					<p class="lab__desc"><?php echo esc_html( $p['desc'] ); ?></p>
					<?php if ( ! empty( $p['specs'] ) ) : ?>
						<dl class="lab__specs">
							<?php foreach ( $p['specs'] as $k => $v ) : ?>
								<div><dt><?php echo esc_html( $k ); ?></dt><dd><?php echo esc_html( $v ); ?></dd></div>
							<?php endforeach; ?>
						</dl>
					<?php endif; ?>
					<div class="lab__btns">
						<?php if ( 'soon' === $p['status'] ) : ?>
							<a class="btn btn--solid" href="<?php echo esc_url( oneam_signup_url() ); ?>" data-magnetic><span>Get notified</span></a>
						<?php else : ?>
							<a class="btn btn--solid" href="<?php echo esc_url( $p['url'] ); ?>" data-magnetic><span>View product</span></a>
						<?php endif; ?>
					</div>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
	<div class="lab__bar"><span></span></div>
</section>
