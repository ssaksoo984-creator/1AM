/* 1AM 쇼핑몰 상품 페이지
 * - 옵션(맛 등)을 버튼으로 (원래 선택 상자는 숨기고 연결)
 * - 장바구니 담기: 페이지 이동 없이 바로 아래에 "담김" 표시 + 헤더 장바구니 숫자 갱신
 */
(function ($) {
	'use strict';
	var wrap = document.querySelector('.spdp__buy');
	if (!wrap || !$) return;
	var cfg = window.ONEAM_POP || {};
	var colours = {};
	try { colours = JSON.parse(wrap.getAttribute('data-swatches') || '{}'); } catch (e) { /* noop */ }
	var form = wrap.querySelector('form.cart');
	if (!form) return;

	/* ---------- 옵션 버튼 ---------- */
	function buildSwatches() {
		$(form).find('table.variations select').each(function () {
			var select = this;
			if (select.dataset.swatched) return;
			select.dataset.swatched = '1';
			var box = document.createElement('div');
			box.className = 'spdp-sw';
			box.setAttribute('role', 'radiogroup');
			Array.prototype.forEach.call(select.options, function (opt) {
				if (!opt.value) return;
				var b = document.createElement('button');
				b.type = 'button';
				b.className = 'spdp-sw__btn';
				b.dataset.value = opt.value;
				b.setAttribute('role', 'radio');
				b.setAttribute('aria-checked', 'false');
				var c = colours[opt.text.toLowerCase()];
				if (c) {
					b.classList.add('has-colour');
					b.style.setProperty('--c1', c[0]);
					b.style.setProperty('--c2', c[1]);
				}
				b.innerHTML = '<span class="spdp-sw__dot" aria-hidden="true"></span><span>' + opt.text + '</span>';
				b.addEventListener('click', function () {
					$(select).val(opt.value).trigger('change');
				});
				box.appendChild(b);
			});
			select.classList.add('spdp-sw__select');
			select.parentNode.insertBefore(box, select);
			$(select).on('change', function () { sync(select, box); });
			sync(select, box);
		});
	}
	function sync(select, box) {
		Array.prototype.forEach.call(box.children, function (b) {
			var on = b.dataset.value === select.value;
			b.classList.toggle('is-on', on);
			b.setAttribute('aria-checked', on ? 'true' : 'false');
			var opt = select.querySelector('option[value="' + CSS.escape(b.dataset.value) + '"]');
			b.disabled = !!(opt && opt.disabled);
		});
		// 선택한 맛 색으로 페이지 강조색 바꾸기
		var c = colours[(select.options[select.selectedIndex] || {}).text ? select.options[select.selectedIndex].text.toLowerCase() : ''];
		if (c) {
			var root = document.querySelector('.spdp');
			root.style.setProperty('--c1', c[0]);
			root.style.setProperty('--c2', c[1]);
		}
	}
	buildSwatches();
	$(form).on('woocommerce_update_variation_values', function () {
		$(form).find('table.variations select').each(function () {
			var box = this.previousElementSibling;
			if (box && box.classList.contains('spdp-sw')) sync(this, box);
		});
	});

	/* ---------- 수량 +/- ---------- */
	$(form).find('.quantity').each(function () {
		var input = this.querySelector('input.qty');
		if (!input || this.querySelector('.spdp-q')) return;
		var minus = document.createElement('button'), plus = document.createElement('button');
		minus.type = plus.type = 'button';
		minus.className = 'spdp-q spdp-q--minus'; minus.setAttribute('aria-label', 'Less'); minus.textContent = '−';
		plus.className = 'spdp-q spdp-q--plus'; plus.setAttribute('aria-label', 'More'); plus.textContent = '+';
		this.insertBefore(minus, input);
		this.appendChild(plus);
		var min = parseFloat(input.min) || 1, step = parseFloat(input.step) || 1;
		minus.addEventListener('click', function () { input.value = Math.max(min, (parseFloat(input.value) || min) - step); $(input).trigger('change'); });
		plus.addEventListener('click', function () { input.value = (parseFloat(input.value) || 0) + step; $(input).trigger('change'); });
	});

	/* ---------- 장바구니 담기 (페이지 이동 없음) ---------- */
	var box = wrap.querySelector('.spdp__added');
	form.addEventListener('submit', function (e) {
		var btn = form.querySelector('.single_add_to_cart_button');
		if (!btn || btn.classList.contains('disabled')) return; // 옵션 미선택 → 우커머스가 안내
		e.preventDefault();
		var fd = new FormData(form);
		if (!fd.get('add-to-cart')) fd.append('add-to-cart', btn.value);
		var qty = parseInt(fd.get('quantity'), 10) || 1;
		var chosen = $(form).find('table.variations select').map(function () { return this.options[this.selectedIndex].text; }).get().join(' · ');
		btn.classList.add('loading');
		btn.disabled = true;
		fetch(cfg.addUrl || window.location.href, { method: 'POST', body: fd, credentials: 'same-origin' })
			.then(function (r) { return r.text(); })
			.then(function (html) {
				var doc = new DOMParser().parseFromString(html, 'text/html');
				var err = doc.querySelector('.woocommerce-error li, .woocommerce-error, .wc-block-components-notice-banner.is-error');
				if (err) throw new Error(err.textContent.trim());
				return fetch(cfg.fragments, { method: 'POST', credentials: 'same-origin' }).then(function (r) { return r.json(); });
			})
			.then(function (res) {
				var frags = (res && res.fragments) || {};
				var count = frags['span.site-nav__count'];
				var el = document.querySelector('.site-nav__count');
				if (count && el) el.outerHTML = count;
				var mini = document.createElement('div');
				mini.innerHTML = frags['div.widget_shopping_cart_content'] || '';
				var subtotal = mini.querySelector('.woocommerce-mini-cart__total .amount');
				var items = mini.querySelectorAll('.woocommerce-mini-cart-item').length;
				box.querySelector('.spdp__added-msg').textContent = '✓ Added ' + qty + ' × ' + (chosen || document.querySelector('.spdp__name').textContent.trim()) + ' to your cart';
				box.querySelector('.spdp__added-cart').textContent = subtotal ? 'Cart: ' + items + ' line' + (items === 1 ? '' : 's') + ' · Subtotal ' + subtotal.textContent + ' + GST' : '';
				box.hidden = false;
				box.classList.remove('is-error');
				box.classList.remove('is-pop'); void box.offsetWidth; box.classList.add('is-pop');
			})
			.catch(function (err) {
				box.querySelector('.spdp__added-msg').textContent = err && err.message && err.message !== 'Failed to fetch' ? err.message : 'Could not add this item. Please try again.';
				box.querySelector('.spdp__added-cart').textContent = '';
				box.hidden = false;
				box.classList.add('is-error');
			})
			.then(function () { btn.classList.remove('loading'); btn.disabled = false; });
	});

	/* ---------- 판매 지역(주) 선택: 누르면 가격이 바로 바뀌고, 결제 화면 배송 주로 들어감 ---------- */
	var regions = document.querySelector('.spdp__regions .pinfo__regions.is-pick');
	var priceNow = null;
	function paintPrice() {
		if (!priceNow) return;
		wrap.querySelectorAll('.spdp__price .price .woocommerce-Price-amount, .woocommerce-variation-price .woocommerce-Price-amount').forEach(function (el) { el.outerHTML = priceNow; });
	}
	if (regions) {
		$(form).on('show_variation', function () { setTimeout(paintPrice, 0); });
		regions.addEventListener('click', function (e) {
			var b = e.target.closest('button[data-prov]');
			if (!b || b.getAttribute('aria-pressed') === 'true') return;
			regions.querySelectorAll('li').forEach(function (li) {
				var on = li.contains(b);
				li.classList.toggle('is-you', on);
				var x = li.querySelector('button'); if (x) x.setAttribute('aria-pressed', on ? 'true' : 'false');
			});
			priceNow = b.dataset.price;
			paintPrice();
			var note = wrap.querySelector('.spdp__prov');
			if (note) note.innerHTML = 'Price for <strong>' + b.dataset.name + ' (' + b.dataset.prov + ')</strong>. This order ships to ' + b.dataset.prov + ' with ' + b.dataset.prov + ' excise stamps.';
			var body = new URLSearchParams();
			body.set('action', 'oneam_set_province');
			body.set('prov', b.dataset.prov);
			body.set('nonce', regions.dataset.nonce || '');
			fetch(cfg.ajax || '/wp-admin/admin-ajax.php', { method: 'POST', credentials: 'same-origin', body: body }).catch(function () {});
		});
	}
})(window.jQuery);
