<?php
/**
 * 도매 거래처를 위한 장점 (문구는 실제 정책에 맞게 수정)
 */
$items = oneam_ctx( 'items', array() ) ? array_map( function ( $r ) { return array( $r['title'], $r['text'], $r['c1'] ?: '#8A3FFC', $r['c2'] ?: '#16C75A' ); }, oneam_ctx( 'items' ) ) : apply_filters(
	'oneam_benefits',
	array(
		array( 'Licensed retailers only', 'Every account is reviewed and verified before it can view pricing or place orders.', '#8A3FFC', '#1E5BFF' ),
		array( 'Compliance first', 'Our products are supplied in accordance with applicable Canadian excise stamping requirements and provincial regulations.', '#00C2A8', '#16C75A' ),
		array( 'Product integrity', 'Original packaging, warning labels and regulatory markings stay intact from our warehouse to your shelf.', '#FF2E4D', '#FFB321' ),
		array( 'Fast, tracked shipping', 'Orders ship by Purolator, UPS or Canpar Ground within ' . oneam_opt( 'oneam_dispatch_time' ) . ' of payment and typically arrive ' . oneam_opt( 'oneam_transit_time' ) . ' after shipping.', '#FFD60A', '#FF8A4D' ),
	)
);
?>
<section class="benefits" id="why">
	<div class="benefits__head">
		<p class="eyebrow"><?php echo esc_html( oneam_ctx( 'eyebrow', 'For retailers' ) ); ?></p>
		<h2 class="section-title section-title--md" data-reveal><?php echo oneam_lines( oneam_ctx( 'title', "Why stock\n*1AM*?" ) ); // phpcs:ignore ?></h2>
	</div>
	<ul class="benefits__list">
		<?php foreach ( $items as $b ) : ?>
			<li class="benefit" style="--c1:<?php echo esc_attr( $b[2] ); ?>;--c2:<?php echo esc_attr( $b[3] ); ?>">
				<span class="benefit__mark" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( array_search( $b, $items, true ) + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
				<h3><?php echo esc_html( $b[0] ); ?></h3>
				<p><?php echo esc_html( $b[1] ); ?></p>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
