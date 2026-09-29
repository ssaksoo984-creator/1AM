<?php
/**
 * FAQ 미리보기 (전체 FAQ 는 FAQ 페이지에서 '세부 정보(Details)' 블록으로 작성)
 */
$faqs = apply_filters(
	'oneam_faq_preview',
	array(
		array( 'Who can buy from 1AM?', 'Only Canadian retailers approved after a business document check can order. We do not sell to consumers.' ),
		array( 'How long does approval take?', 'We review applications within 1–2 business days and email you once you are approved.' ),
		array( 'Is there a minimum order?', 'Minimum order quantities and pricing are shown in the wholesale shop after approval.' ),
		array( 'Where do you ship?', 'We ship only to provinces where our products can be sold. Other addresses cannot be selected at checkout.' ),
		array( 'How do I pay?', 'Pay by credit card or Interac e-Transfer / bank transfer.' ),
	)
);
?>
<section class="faq" id="faq">
	<div class="faq__head">
		<p class="eyebrow">FAQ</p>
		<h2 class="section-title section-title--md" data-reveal>
			<span class="line"><span>Questions,</span></span>
			<span class="line"><span><em>answered.</em></span></span>
		</h2>
		<a class="link-arrow" href="<?php echo esc_url( oneam_opt( 'oneam_faq_url' ) ); ?>">All FAQ <span>&rarr;</span></a>
	</div>
	<div class="faq__list">
		<?php foreach ( $faqs as $i => $q ) : ?>
			<details class="faq__item" <?php echo 0 === $i ? 'open' : ''; ?>>
				<summary><?php echo esc_html( $q[0] ); ?><i aria-hidden="true"></i></summary>
				<div class="faq__a"><p><?php echo esc_html( $q[1] ); ?></p></div>
			</details>
		<?php endforeach; ?>
	</div>
</section>
