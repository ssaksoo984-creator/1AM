<?php
/**
 * 1AM 정책 페이지 기본 글 (영문, 캐나다 도매 · 베이핑 기준)
 * Terms & Conditions(일반 약관 + Vaping Product Compliance) · Return Policy · Shipping Policy · Privacy Policy
 *
 * 페이지로 만들어지므로 블록 편집기 · Elementor 에서 자유롭게 수정할 수 있습니다.
 * 법률 검토 권장: 클레임 기간, 결제 기한, 준거법(현재 British Columbia), 개인정보 보관 기간.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** 회사 연락처 (카드사 요구: 상호 · 주소 · 이메일 · 전화 · GST 번호) */
function oneam_policy_contact() {
	$lines = array( '<strong>' . esc_html( oneam_opt( 'oneam_company' ) ) . '</strong>' );
	if ( oneam_opt( 'oneam_address' ) ) {
		$lines[] = nl2br( esc_html( trim( oneam_opt( 'oneam_address' ) ) ) );
	}
	$email   = esc_html( oneam_opt( 'oneam_email' ) );
	$lines[] = 'Email: <a href="mailto:' . $email . '">' . $email . '</a>';
	if ( oneam_opt( 'oneam_phone' ) ) {
		$lines[] = 'Phone: ' . esc_html( oneam_opt( 'oneam_phone' ) );
	}
	if ( oneam_licence_line() ) {
		$lines[] = esc_html( oneam_licence_line() );
	}
	return implode( '<br>', $lines );
}

/** 정책 최종 수정일: 정책 글을 새로 만든 날 (없으면 오늘) */
function oneam_policy_updated() {
	$d = get_option( 'oneam_policy_updated' );
	return esc_html( $d ? $d : wp_date( 'F j, Y' ) );
}

function oneam_policy_vars() {
	$email = esc_html( oneam_opt( 'oneam_email' ) );
	$claim = (int) oneam_opt( 'oneam_claim_days' ) ?: 3;
	return array(
		'co'       => esc_html( oneam_opt( 'oneam_company' ) ),
		'mail'     => '<a href="mailto:' . $email . '">' . $email . '</a>',
		'terms'    => '<a href="' . esc_url( home_url( '/terms/' ) ) . '">Terms &amp; Conditions</a>',
		'returns'  => '<a href="' . esc_url( home_url( '/return-policy/' ) ) . '">Return Policy</a>',
		'ship'     => '<a href="' . esc_url( home_url( '/shipping-policy/' ) ) . '">Shipping Policy</a>',
		'privacy'  => '<a href="' . esc_url( home_url( '/privacy-policy/' ) ) . '">Privacy Policy</a>',
		'updated'  => oneam_policy_updated(),
		'proc'     => esc_html( oneam_opt( 'oneam_processor' ) ?: 'our payment processor' ),
		'pay'      => esc_html( oneam_opt( 'oneam_payments' ) ),
		'contact'  => oneam_policy_contact(),
		'regions'  => esc_html( oneam_regions_phrase() ),
		'dispatch' => esc_html( oneam_opt( 'oneam_dispatch_time' ) ),
		'transit'  => esc_html( oneam_opt( 'oneam_transit_time' ) ),
		'due'      => (int) oneam_opt( 'oneam_pay_due_days' ) ?: 2,
		'claim'    => $claim,
		'defect'   => (int) oneam_opt( 'oneam_defect_days' ) ?: 30,
		'risk'     => esc_html( oneam_opt( 'oneam_risk_transfer' ) ),
		'etr'      => esc_html( function_exists( 'oneam_etransfer_email' ) ? oneam_etransfer_email() : oneam_opt( 'oneam_email' ) ),
		'gst'      => '', // GST 번호는 사이트에 표시하지 않음 (인보이스 · 주문 메일에만)
	);
}

/** 문단 여러 개 */
function oneam_ps( $items ) {
	$out = '';
	foreach ( $items as $t ) {
		$out .= oneam_b_p( $t );
	}
	return $out;
}

/* ---------------------------------------------------------------------------
 * Terms & Conditions = Wholesale Terms of Sale + Vaping Product Compliance
 * ------------------------------------------------------------------------- */
function oneam_policy_terms() {
	return oneam_policy_blocks( 'terms' ); // 원문: inc/policy-texts.php
}

/* ---------------------------------------------------------------------------
 * Return Policy
 * ------------------------------------------------------------------------- */
function oneam_policy_returns() {
	return oneam_policy_blocks( 'returns' ); // 원문: inc/policy-texts.php
}

/* ---------------------------------------------------------------------------
 * Shipping Policy
 * ------------------------------------------------------------------------- */
function oneam_policy_shipping() {
	return oneam_policy_blocks( 'shipping' ); // 원문: inc/policy-texts.php
}

/* ---------------------------------------------------------------------------
 * Privacy Policy (PIPEDA · BC PIPA)
 * ------------------------------------------------------------------------- */
function oneam_policy_privacy() {
	return oneam_policy_blocks( 'privacy' ); // 원문: inc/policy-texts.php
}

/* ---------------------------------------------------------------------------
 * 블록 결제 화면 약관 동의: 체크해야만 주문 + 정책 링크
 * ------------------------------------------------------------------------- */
function oneam_setup_checkout_terms() {
	$id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'checkout' ) : 0;
	if ( $id <= 0 ) {
		return false;
	}
	$post = get_post( $id );
	if ( ! $post || false === strpos( $post->post_content, 'woocommerce/checkout-terms-block' ) ) {
		return false;
	}
	$v    = oneam_policy_vars();
	$text = esc_html( oneam_opt( 'oneam_terms_text' ) );
	$text = str_replace(
		array( 'Terms &amp; Conditions', 'Vaping Product Compliance', 'Privacy Policy', 'Shipping Policy', 'Return Policy' ),
		array( $v['terms'], '<a href="' . esc_url( home_url( '/terms/#vape-compliance' ) ) . '">Vaping Product Compliance</a>', $v['privacy'], $v['ship'], $v['returns'] ),
		$text
	);
	$attrs   = serialize_block_attributes( array( 'checkbox' => true, 'text' => $text ) );
	$content = preg_replace( '/<!-- wp:woocommerce\/checkout-terms-block(\s+\{.*?\})?\s*(\/)?-->/s', '<!-- wp:woocommerce/checkout-terms-block ' . $attrs . ' $2-->', $post->post_content, 1 );
	// 주문 메모 칸을 청구지 주소 바로 아래로 (필수 확인 체크 두 개가 결제 방법 아래에 함께 모이도록)
	$note = '/<!-- wp:woocommerce\/checkout-order-note-block.*?<!-- \/wp:woocommerce\/checkout-order-note-block -->\s*/s';
	$bill = '<!-- /wp:woocommerce/checkout-billing-address-block -->';
	if ( $content && preg_match( $note, $content, $m ) && false !== strpos( $content, $bill ) && strpos( $content, $m[0] ) > strpos( $content, $bill ) ) {
		$content = preg_replace( $note, '', $content, 1 );
		$content = str_replace( $bill, $bill . "\n\n" . trim( $m[0] ), $content );
	}
	if ( $content && $content !== $post->post_content ) {
		wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $content ) ) );
	}
	return true;
}
// 사용자 정의하기에서 문구를 바꾸면 결제 화면에도 반영
add_action( 'customize_save_after', 'oneam_setup_checkout_terms' );
