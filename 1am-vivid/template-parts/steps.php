<?php
/**
 * 구매 절차 (가입 → 승인 → 주문)
 */
$steps = array(
	array( 'Apply', '도매 가입 신청서와 사업자 서류(사업자 등록증, 소매 판매 관련 서류)를 제출합니다.' ),
	array( 'Get approved', '관리자가 서류를 확인하고 승인하면 알림 이메일이 발송됩니다.' ),
	array( 'Order', '로그인 후 비공개 쇼핑몰에서 도매가를 확인하고 바로 주문합니다.' ),
);
?>
<section class="steps" id="how-to-order" data-header="light">
	<div class="steps__head">
		<p class="eyebrow">How to order</p>
		<h2 class="section-title section-title--md" data-reveal>
			<span class="line"><span>Three steps</span></span>
			<span class="line"><span><em>to your first</em> order</span></span>
		</h2>
	</div>
	<div class="steps__track">
	<span class="steps__line" aria-hidden="true"><i></i></span>
	<ol class="steps__list">
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
		<a class="link-arrow" href="<?php echo esc_url( oneam_opt( 'oneam_order_url' ) ); ?>">Full ordering guide <span>&rarr;</span></a>
	</div>
</section>
