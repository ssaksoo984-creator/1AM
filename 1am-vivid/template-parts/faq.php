<?php
/**
 * FAQ 미리보기 (전체 FAQ 는 FAQ 페이지에서 '세부 정보(Details)' 블록으로 작성)
 */
$faqs = apply_filters(
	'oneam_faq_preview',
	array(
		array( 'Who can buy from 1AM?', '사업자 서류 확인을 거쳐 승인된 캐나다 소매 거래처만 주문할 수 있습니다. 일반 소비자 판매는 하지 않습니다.' ),
		array( 'How long does approval take?', '서류 제출 후 영업일 기준 1~2일 안에 검토합니다. 승인되면 이메일로 알려드립니다. (실제 기간으로 수정)' ),
		array( 'Is there a minimum order?', '최소 주문 수량과 가격은 승인 후 쇼핑몰에서 확인할 수 있습니다. (도매 정책에 맞게 수정)' ),
		array( 'Where do you ship?', '판매가 허용된 주(Province)로만 배송합니다. 해당 지역 외 주소는 주문 단계에서 선택되지 않습니다.' ),
		array( 'How do I pay?', '카드 결제 또는 Interac e-Transfer / 계좌이체로 결제할 수 있습니다. (결제 방식 확정 후 수정)' ),
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
