<?php
/**
 * 결제 링크 화면 (관리자가 만든 주문 → 손님이 "Pay for this order" 로 결제)
 *
 * Moneris 플러그인은 이 화면에서 카드 입력창을 결제 수단 칸 안에 바로 펼쳐 놓아서
 * 약관 동의 체크가 아래로 밀리고, 손님이 동의 없이 카드 입력부터 하게 됨.
 * → 순서를 "주문 금액 → 약관 동의 → 결제 수단 → [Pay by credit card]" 로 바꾸고,
 *   카드 입력창은 버튼을 누르면 장바구니 결제처럼 화면 전체 팝업으로 열리게 함.
 *   (Moneris 창 자체는 옮기지 않고 보이는 방식만 바꿈 — 옮기면 결제창이 다시 로딩됨)
 *
 * Moneris 창을 못 찾으면 아무것도 바꾸지 않음 (기존 화면 그대로).
 *
 * @package 1am-vivid
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-pay' ) ) {
			return;
		}
		wp_register_script( 'oneam-order-pay', '', array( 'jquery' ), ONEAM_VERSION, true );
		wp_enqueue_script( 'oneam-order-pay' );
		wp_add_inline_script(
			'oneam-order-pay',
			<<<'JS'
jQuery(function ($) {
	var $form = $('#order_review');
	if (!$form.length) return;
	var $pay = $form.find('#payment');

	// 1) 약관 동의를 결제 수단 위로
	var $terms = $form.find('.woocommerce-terms-and-conditions-wrapper').first();
	var $methods = $pay.find('ul.payment_methods').first();
	if ($terms.length && $methods.length) {
		$terms.addClass('oneam-pay-terms').insertBefore($methods);
	}
	var $tick = $form.find('input#terms, input[name="terms"]').first();

	// 2) 카드 결제 칸 안의 Moneris 창 찾기
	var $box = $pay.find('.payment_box[class*="payment_method_moneris"]').first();
	if (!$box.length) return;
	var $wrap = $box.children().filter(function () {
		var el = this;
		if (el.tagName === 'P' || el.tagName === 'INPUT' || el.tagName === 'SCRIPT' || el.tagName === 'STYLE') return false;
		return el.tagName === 'IFRAME' || /moneris/i.test(el.id + ' ' + el.className) || $(el).find('iframe').length > 0 || /moneris/i.test(el.innerHTML.slice(0, 2000));
	});
	if (!$wrap.length) return;

	$('html').addClass('oneam-pay-ready');
	$wrap.addClass('oneam-mco-pop');
	$wrap.find('iframe').each(function () { $(this).parentsUntil($box).addClass('oneam-mco-fill'); });

	var total = $.trim($form.find('table.shop_table tfoot tr:last td:last').text());
	var $btn = $('<button type="button" class="button alt oneam-card-pay"></button>').text('Pay ' + (total ? total + ' ' : '') + 'by credit card →');
	var $hint = $('<p class="oneam-card-hint" role="alert" hidden>Please tick the box above to agree to the terms first.</p>');
	var $close = $('<button type="button" class="oneam-mco-close" aria-label="Close card payment">×</button>');
	$box.append($btn, $hint);
	$('body').append($close);

	function cardChosen() { return $pay.find('input[name="payment_method"]:checked').val() && /moneris/i.test($pay.find('input[name="payment_method"]:checked').val()); }
	function sync() {
		var card = cardChosen();
		$('html').toggleClass('oneam-pay-card', !!card);
		var ok = !$tick.length || $tick.is(':checked');
		$btn.toggleClass('is-locked', !ok);
		if (ok) $hint.prop('hidden', true);
	}
	function open() {
		if ($tick.length && !$tick.is(':checked')) {
			$hint.prop('hidden', false);
			$('html,body').animate({ scrollTop: Math.max(0, $tick.offset().top - 160) }, 250);
			$tick.trigger('focus');
			return;
		}
		$('html').addClass('oneam-mco-show');
	}
	function close() { $('html').removeClass('oneam-mco-show'); }

	$btn.on('click', open);
	$close.on('click', close);
	$(document).on('keydown', function (e) { if (e.key === 'Escape') close(); });
	$form.on('change', 'input[name="payment_method"], input#terms, input[name="terms"]', sync);
	// 동의를 풀면 결제창도 닫음
	$form.on('change', 'input#terms, input[name="terms"]', function () { if (!this.checked) close(); });
	sync();
});
JS
		);
		wp_register_style( 'oneam-order-pay', false, array(), ONEAM_VERSION );
		wp_enqueue_style( 'oneam-order-pay' );
		wp_add_inline_style(
			'oneam-order-pay',
			'.oneam-pay-terms{margin:0 0 22px;padding:16px 18px;border-radius:12px;background:#fff;border:1px solid rgba(0,0,0,.12)}'
			. '.oneam-pay-terms .form-row{margin:0}'
			// 카드 결제창: 평소에는 숨김 (자리 차지 X), 버튼 누르면 화면 전체
			. '.oneam-pay-ready .oneam-mco-pop{position:fixed!important;inset:0!important;width:100%!important;height:100%!important;max-width:none!important;margin:0!important;padding:0!important;z-index:2147483000!important;background:rgba(14,13,18,.72);opacity:0;visibility:hidden;pointer-events:none;transition:opacity .2s}'
			. '.oneam-mco-show .oneam-mco-pop{opacity:1;visibility:visible;pointer-events:auto}'
			. '.oneam-mco-pop iframe,.oneam-mco-pop .oneam-mco-fill{width:100%!important;height:100%!important;max-width:none!important;max-height:none!important;border:0!important}'
			. '.oneam-mco-close{display:none}'
			. '.oneam-mco-show .oneam-mco-close{display:flex;align-items:center;justify-content:center;position:fixed;top:14px;right:14px;z-index:2147483001;width:44px;height:44px;border-radius:50%;border:0;background:#fff;color:#0e0d12;font-size:28px;line-height:1;cursor:pointer;box-shadow:0 4px 16px rgba(0,0,0,.25)}'
			. '.oneam-mco-show,.oneam-mco-show body{overflow:hidden}'
			. '.oneam-mco-show .site-header,.oneam-mco-show .topbar{z-index:1!important}'
			// 카드 선택 시: 기본 결제 버튼 대신 "Pay by credit card"
			. '.oneam-pay-ready.oneam-pay-card #place_order{display:none!important}'
			. '.oneam-card-pay{display:block;width:100%;margin:16px 0 0;padding:16px 24px;border-radius:999px;background:#0e0d12;color:#fff;font-weight:700;font-size:16px;border:0;cursor:pointer}'
			. '.oneam-card-pay.is-locked{opacity:.45}'
			. '.oneam-card-hint{margin:10px 0 0;color:#b42318;font-weight:600;font-size:14px}'
		);
	}
);
