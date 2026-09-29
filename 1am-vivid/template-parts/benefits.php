<?php
/**
 * 도매 거래처를 위한 장점 (문구는 실제 정책에 맞게 수정)
 */
$items = apply_filters(
	'oneam_benefits',
	array(
		array( 'Wholesale pricing', 'Trade pricing for approved accounts, visible as soon as you log in.', '#FF2E4D', '#FFB321' ),
		array( 'Approved retailers only', 'We only work with stores whose business documents we have verified.', '#8A3FFC', '#1E5BFF' ),
		array( 'Ships where it\'s allowed', 'Orders ship only to provinces where our products can be sold.', '#00C2A8', '#16C75A' ),
		array( 'Clear product info', 'Specs, flavours and product details in one place.', '#FFD60A', '#FF8A4D' ),
	)
);
?>
<section class="benefits" id="why">
	<div class="benefits__head">
		<p class="eyebrow">For retailers</p>
		<h2 class="section-title section-title--md" data-reveal>
			<span class="line"><span>Why stock</span></span>
			<span class="line"><span><em>1AM</em>?</span></span>
		</h2>
	</div>
	<ul class="benefits__list">
		<?php foreach ( $items as $b ) : ?>
			<li class="benefit" style="--c1:<?php echo esc_attr( $b[2] ); ?>;--c2:<?php echo esc_attr( $b[3] ); ?>">
				<span class="benefit__mark" aria-hidden="true"></span>
				<h3><?php echo esc_html( $b[0] ); ?></h3>
				<p><?php echo esc_html( $b[1] ); ?></p>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
