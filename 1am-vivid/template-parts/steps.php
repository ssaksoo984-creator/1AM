<?php
/**
 * 구매 절차 (가입 → 승인 → 주문)
 */
$steps = oneam_ctx( 'items', array() ) ? array_map( function ( $r ) { return array( $r['title'], $r['text'] ); }, oneam_ctx( 'items' ) ) : array(
	array( 'Apply', 'Complete the retailer account application to get started.' ),
	array( 'Get approved', 'Most applications are reviewed within 1–2 business days. We’ll email you once your account is approved.' ),
	array( 'Order online', 'Log in to access wholesale pricing and place orders. Ensure each product has the correct excise stamp for its intended province of sale.' ),
	array( 'Pay & ship', 'Pay by ' . ( oneam_opt( 'oneam_processor' ) ? 'Interac e-Transfer or card' : 'Interac e-Transfer' ) . '. Orders ship within ' . oneam_opt( 'oneam_dispatch_time' ) . ' of payment by Purolator, UPS or Canpar Ground and typically arrive ' . oneam_opt( 'oneam_transit_time' ) . ' after shipping.' ),
);
?>
<?php
$oneam_bg  = oneam_ctx( 'bg_image', '' );
$oneam_bg  = is_array( $oneam_bg ) ? ( $oneam_bg['url'] ?? '' ) : $oneam_bg;
$oneam_ov  = oneam_ctx( 'overlay', 'rgba(13,11,18,0.6)' );
$oneam_sty = $oneam_bg ? "--steps-img:url('" . esc_url( $oneam_bg ) . "');--steps-tint:" . esc_attr( $oneam_ov ?: 'rgba(13,11,18,0.6)' ) : '';
?>
<section class="steps<?php echo $oneam_bg ? ' has-img' : ''; ?>" id="how-to-order" style="<?php echo $oneam_sty; // phpcs:ignore ?>">
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
		<?php oneam_member_cta( '', 'Apply for a retailer account' ); ?>
		<?php if ( untrailingslashit( (string) oneam_opt( 'oneam_order_url' ) ) !== untrailingslashit( (string) get_permalink() ) ) : ?>
			<a class="link-arrow" href="<?php echo esc_url( oneam_opt( 'oneam_order_url' ) ); ?>">Full ordering guide <span>&rarr;</span></a>
		<?php endif; ?>
	</div>
</section>
