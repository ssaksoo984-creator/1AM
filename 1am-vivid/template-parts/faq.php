<?php
/**
 * FAQ 미리보기 (전체 FAQ 는 FAQ 페이지에서 '세부 정보(Details)' 블록으로 작성)
 */
$faqs = oneam_ctx( 'items', array() ) ? array_map( function ( $r ) { return array( $r['q'], $r['a'] ); }, oneam_ctx( 'items' ) ) : apply_filters(
	'oneam_faq_preview',
	array_slice( oneam_faq_items(), 0, 5 )
);
?>
<section class="faq" id="faq">
	<div class="faq__head">
		<p class="eyebrow"><?php echo esc_html( oneam_ctx( 'eyebrow', 'FAQ' ) ); ?></p>
		<h2 class="section-title section-title--md" data-reveal><?php echo oneam_lines( oneam_ctx( 'title', "Questions,\n*answered.*" ) ); // phpcs:ignore ?></h2>
		<?php if ( untrailingslashit( (string) oneam_opt( 'oneam_faq_url' ) ) !== untrailingslashit( (string) get_permalink() ) ) : ?>
			<a class="link-arrow" href="<?php echo esc_url( oneam_opt( 'oneam_faq_url' ) ); ?>">All FAQ <span>&rarr;</span></a>
		<?php endif; ?>
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
