/* 1AM — 맛 팝업: 카드를 누르면 왼쪽 제품 · 오른쪽 맛 특징 · 장바구니(승인 회원) 또는 가입 안내 */
(function () {
	'use strict';
	var pop = document.getElementById('fpop');
	if (!pop) return;
	var cfg = window.ONEAM_POP || {};
	var $ = function (s) { return pop.querySelector(s); };
	var cards = Array.prototype.slice.call(document.querySelectorAll('[data-flavour]'));
	var current = null, lastFocus = null, closeTimer = null;

	function shown() {
		return cards.filter(function (a) { var li = a.closest('.card'); return !li || !li.classList.contains('is-hidden'); });
	}
	function lenis(on) {
		var l = window.ONEAM_LENIS;
		if (l) { on ? l.start() : l.stop(); }
		document.documentElement.classList.toggle('fpop-open', !on);
	}

	function fill(a) {
		current = a;
		var d = JSON.parse(a.getAttribute('data-flavour'));
		pop.style.setProperty('--c1', d.c1);
		pop.style.setProperty('--c2', d.c2);
		var img = $('.fpop__img'); img.src = d.img; img.alt = d.name;
		$('.fpop__name').textContent = d.name;
		$('.fpop__cat').textContent = d.cat || '';
		$('.fpop__line').textContent = d.line || '';
		var ul = $('.fpop__notes'); ul.innerHTML = '';
		(d.notes || []).forEach(function (n) { var li = document.createElement('li'); li.textContent = n; ul.appendChild(li); });
		var reg = $('.fpop__regions');
		reg.textContent = d.regions && d.regions.length ? 'Available in ' + d.regions.join(' · ') : '';
		reg.hidden = !reg.textContent;

		var form = $('.fpop__buy');
		if (form) {
			form.dataset.pid = d.pid || '';
			form.dataset.vid = d.vid || '';
			form.dataset.attr = JSON.stringify(d.attr || {});
			$('.fpop__price').innerHTML = d.price ? '<strong>' + d.price + '</strong> <small>/ ' + (d.unit || 'unit') + ' + GST' + (d.prov ? ' · ' + d.prov + ' price' : '') + '</small>' : '';
			var btn = $('.fpop__add');
			var can = !!(d.pid && d.vid && d.ok);
			btn.disabled = !can;
			$('.fpop__msg').textContent = d.pid ? (can ? '' : 'Not available for your province.') : 'Ordering opens once this product is in the shop.';
			$('.fpop__cart').hidden = true;
		}
	}

	function open(a) {
		clearTimeout(closeTimer);
		lastFocus = document.activeElement;
		fill(a);
		pop.hidden = false;
		pop.setAttribute('aria-hidden', 'false');
		requestAnimationFrame(function () { pop.classList.add('is-open'); });
		lenis(false);
		$('.fpop__close').focus();
	}
	function close() {
		pop.classList.remove('is-open');
		pop.setAttribute('aria-hidden', 'true');
		lenis(true);
		closeTimer = setTimeout(function () { pop.hidden = true; }, 350);
		if (lastFocus) lastFocus.focus();
	}
	function step(n) {
		var list = shown();
		var i = list.indexOf(current);
		if (i < 0 || !list.length) return;
		var next = list[(i + n + list.length) % list.length];
		pop.classList.add('is-swap');
		setTimeout(function () { fill(next); pop.classList.remove('is-swap'); }, 160);
	}

	cards.forEach(function (a) {
		a.addEventListener('click', function (e) { e.preventDefault(); open(a); });
	});
	pop.addEventListener('click', function (e) {
		if (e.target.closest('[data-close]')) close();
		var s = e.target.closest('[data-step]');
		if (s) step(+s.getAttribute('data-step'));
	});
	document.addEventListener('keydown', function (e) {
		if (pop.hidden) return;
		if (e.key === 'Escape') close();
		if (e.key === 'ArrowRight') step(1);
		if (e.key === 'ArrowLeft') step(-1);
	});

	// 장바구니에 담기 (페이지 이동 없이) → 헤더 장바구니 숫자 갱신
	var form = $('.fpop__buy');
	if (form) {
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var btn = $('.fpop__add'), msg = $('.fpop__msg');
			if (btn.disabled) return;
			var fd = new FormData();
			fd.append('add-to-cart', form.dataset.pid);
			fd.append('product_id', form.dataset.pid);
			fd.append('variation_id', form.dataset.vid);
			fd.append('quantity', Math.max(1, parseInt(form.quantity.value, 10) || 1));
			var attr = JSON.parse(form.dataset.attr || '{}');
			Object.keys(attr).forEach(function (k) { fd.append(k, attr[k]); });
			btn.disabled = true;
			msg.textContent = 'Adding…';
			fetch(cfg.addUrl || window.location.href, { method: 'POST', body: fd, credentials: 'same-origin' })
				.then(function (r) { return r.text(); })
				.then(function (html) {
					if (/woocommerce-error|wc-block-components-notice-banner is-error/.test(html)) throw new Error('add');
					return fetch(cfg.fragments, { method: 'POST', credentials: 'same-origin' });
				})
				.then(function (r) { return r.json(); })
				.then(function (res) {
					var frag = res && res.fragments && res.fragments['span.site-nav__count'];
					var el = document.querySelector('.site-nav__count');
					if (frag && el) el.outerHTML = frag;
					msg.textContent = 'Added to cart ✓';
					$('.fpop__cart').hidden = false;
				})
				.catch(function () { msg.textContent = 'Could not add this item. Please try again or open the product page.'; })
				.then(function () { btn.disabled = false; });
		});
	}
})();
