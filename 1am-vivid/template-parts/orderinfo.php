<?php
/**
 * How to Order — 주문 전 확인 · 결제 · 배송 · 문의 카드
 */
$items = oneam_ctx( 'items', array() );
if ( ! $items ) {
	$items = oneam_orderinfo_defaults();
}
$icons = array( 'check', 'card', 'truck', 'mail' );
?>
<section class="orderinfo">
	<div class="orderinfo__head">
		<p class="eyebrow"><?php echo esc_html( oneam_ctx( 'eyebrow', 'Good to know' ) ); ?></p>
		<h2 class="section-title section-title--md" data-reveal><?php echo oneam_lines( oneam_ctx( 'title', "Before you\n*place an order.*" ) ); // phpcs:ignore ?></h2>
	</div>
	<div class="orderinfo__grid">
		<?php foreach ( $items as $i => $it ) : ?>
			<?php $icon = in_array( trim( (string) $it['icon'] ), $icons, true ) ? trim( $it['icon'] ) : $icons[ $i % 4 ]; ?>
			<article class="orderinfo__card" style="--i:<?php echo (int) $i; ?>">
				<span class="orderinfo__icon"><?php echo oneam_icon( $icon ); // phpcs:ignore ?></span>
				<h3><?php echo esc_html( $it['title'] ); ?></h3>
				<ul>
					<?php foreach ( preg_split( '/\r?\n/', trim( (string) $it['lines'] ) ) as $line ) : ?>
						<?php if ( '' !== trim( $line ) ) : ?>
							<li><?php echo wp_kses( make_clickable( esc_html( trim( $line ) ) ), array( 'a' => array( 'href' => array(), 'rel' => array() ) ) ); ?></li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			</article>
		<?php endforeach; ?>
	</div>
</section>
