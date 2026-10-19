<?php
/**
 * 구매 절차 (가입 → 승인 → 주문)
 */
$steps = oneam_ctx( 'items', array() ) ? array_map( function ( $r ) { return array( $r['title'], $r['text'] ); }, oneam_ctx( 'items' ) ) : array(
	array( 'Apply', 'Submit the retail account application with your business information and documents confirming an authorized adult-only vape store.' ),
	array( 'Get verified', 'Our team reviews your application, usually within 1–2 business days, and emails you once your account is approved.' ),
	array( 'Order online', 'Log in to see wholesale pricing. Choose products with the correct excise stamps for your province and confirm compliance at checkout.' ),
	array( 'Pay & ship', 'Pay by Interac e-Transfer or card. Orders ship by Purolator, UPS or Canpar Ground, typically arriving in 1–5 business days.' ),
);
?>
<section class="steps" id="how-to-order" data-header="light">
	<div class="steps__head">
		<p class="eyebrow"><?php echo esc_html( oneam_ctx( 'eyebrow', 'How to order' ) ); ?></p>
		<h2 class="section-title section-title--md" data-reveal><?php echo oneam_lines( oneam_ctx( 'title', "Four steps\n*to your first* order" ) ); // phpcs:ignore ?></h2>
	</div>
	<div class="steps__track">
	<span class="steps__line" aria-hidden="true"><i></i></span>
	<ol class="steps__list" style="--n:<?php echo (int) min( 4, max( 1, count( $steps ) ) ); ?>">
		<?php foreach ( $steps as $i => $s ) : ?>
			<li class="step">
				<span class="step__num"><?php echo (int) $i + 1; ?></span>
				<h3><?php echo esc_html( $s[0] ); ?></h3>
				<p><?php echo esc_html( $s[1] ); ?></p>
			</li>
		<?php endforeach; ?>
	</ol>
	</div>
	<div class="steps__foot">
		<?php oneam_member_cta(); ?>
		<?php if ( untrailingslashit( (string) oneam_opt( 'oneam_order_url' ) ) !== untrailingslashit( (string) get_permalink() ) ) : ?>
			<a class="link-arrow" href="<?php echo esc_url( oneam_opt( 'oneam_order_url' ) ); ?>">Full ordering guide <span>&rarr;</span></a>
		<?php endif; ?>
	</div>
</section>
