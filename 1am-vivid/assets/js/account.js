/**
 * 내 계정: 왼쪽 탭을 누르면 페이지를 새로 불러오지 않고 오른쪽 내용만 바꿉니다.
 * (로그아웃 · 다른 페이지로 가는 링크는 평소처럼 이동)
 */
(function () {
	'use strict';

	var nav = document.querySelector('.woocommerce-MyAccount-navigation');
	var content = document.querySelector('.woocommerce-MyAccount-content');
	if (!nav || !content || !window.fetch || !window.DOMParser) return;

	var busy = null;

	function setActive(url) {
		var path = new URL(url, location.href).pathname.replace(/\/$/, '');
		var best = null;
		nav.querySelectorAll('li').forEach(function (li) {
			var a = li.querySelector('a');
			li.classList.remove('is-active');
			if (!a) return;
			var p = new URL(a.href, location.href).pathname.replace(/\/$/, '');
			if (path === p || path.indexOf(p + '/') === 0) {
				if (!best || p.length > best.p.length) best = { li: li, p: p };
			}
		});
		if (best) best.li.classList.add('is-active');
	}

	function load(url, push) {
		if (busy) busy.abort();
		busy = new AbortController();
		content.classList.add('is-loading');
		fetch(url, { credentials: 'same-origin', signal: busy.signal })
			.then(function (r) {
				if (!r.ok) throw new Error(r.status);
				return r.text();
			})
			.then(function (html) {
				var doc = new DOMParser().parseFromString(html, 'text/html');
				var next = doc.querySelector('.woocommerce-MyAccount-content');
				if (!next) throw new Error('no content');
				content.innerHTML = next.innerHTML;
				content.classList.remove('is-loading');
				if (doc.title) document.title = doc.title;
				if (push) history.pushState({ oneamAcct: 1 }, '', url);
				setActive(url);
				bind(content);
				document.body.dispatchEvent(new Event('wc-account-tab', { bubbles: true }));
				var top = content.getBoundingClientRect().top + window.scrollY - 140;
				if (window.scrollY > top) window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
			})
			.catch(function (e) {
				if (e && e.name === 'AbortError') return;
				location.href = url; // 문제가 생기면 평소처럼 이동
			});
	}

	function intercept(e) {
		var a = e.currentTarget;
		if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
		if (a.target && a.target !== '_self') return;
		if (a.closest('.woocommerce-MyAccount-navigation-link--customer-logout')) return;
		if (/customer-logout|[?&]_wpnonce=/.test(a.href)) return;
		if (a.origin !== location.origin || a.hash && a.pathname === location.pathname) return;
		e.preventDefault();
		load(a.href, true);
	}

	function isAccountLink(a) {
		var base = nav.querySelector('a');
		if (!base) return false;
		var root = new URL(nav.querySelector('li a').href, location.href).pathname;
		// 계정 첫 탭(대시보드) 주소 아래에 있는 링크만
		root = root.replace(/\/$/, '');
		return a.origin === location.origin && (a.pathname.replace(/\/$/, '') === root || a.pathname.indexOf(root + '/') === 0);
	}

	function bind(scope) {
		scope.querySelectorAll('a[href]').forEach(function (a) {
			if (a.dataset.oneamAcct) return;
			if (scope === nav || isAccountLink(a)) {
				a.dataset.oneamAcct = '1';
				a.addEventListener('click', intercept);
			}
		});
	}

	bind(nav);
	bind(content);
	history.replaceState({ oneamAcct: 1 }, '', location.href);
	window.addEventListener('popstate', function (e) {
		if (e.state && e.state.oneamAcct) load(location.href, false);
	});
})();
