/* ==========================================================================
   1AM Vivid — main.js
   GSAP + ScrollTrigger + Lenis
   ========================================================================== */
(function () {
	'use strict';

	var cfg = window.ONEAM || { ageGate: true };
	var gsap = window.gsap;
	var ST = window.ScrollTrigger;
	var $ = function (s, c) { return (c || document).querySelector(s); };
	var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
	var body = document.body;
	var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

	if (!gsap) { body.classList.remove('is-loading'); return; }
	gsap.registerPlugin(ST);

	var store = {
		get: function (k) { try { return window.localStorage.getItem(k); } catch (e) { return null; } },
		set: function (k, v) { try { window.localStorage.setItem(k, v); } catch (e) { /* noop */ } }
	};

	/* ---------------------------------------------------------------- Lenis */
	var lenis = null;
	if (window.Lenis && !reduce) {
		lenis = new window.Lenis({ lerp: 0.1, smoothWheel: true });
		lenis.on('scroll', ST.update);
		gsap.ticker.add(function (t) { lenis.raf(t * 1000); });
		gsap.ticker.lagSmoothing(0);
	}
	function lock(on) {
		body.classList.toggle('is-locked', on);
		if (lenis) { on ? lenis.stop() : lenis.start(); }
	}

	// 앵커 링크 부드럽게
	$$('a[href*="#"]').forEach(function (a) {
		a.addEventListener('click', function (e) {
			var url = new URL(a.href, location.href);
			if (url.pathname !== location.pathname || !url.hash) return;
			var target = $(url.hash);
			if (!target) return;
			e.preventDefault();
			closeMenu();
			if (lenis) lenis.scrollTo(target, { offset: 0, duration: 1.4 });
			else target.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth' });
		});
	});

	/* ------------------------------------------------------------- Age gate */
	function ageGate() {
		return new Promise(function (resolve) {
			var gate = $('#agegate');
			if (!gate || !cfg.ageGate || store.get('oneam_age') === '1') { resolve(); return; }
			gate.hidden = false;
			lock(true);
			gsap.from('.agegate__box', { y: 60, opacity: 0, duration: 1, ease: 'expo.out' });
			gate.addEventListener('click', function (e) {
				var btn = e.target.closest('[data-age]');
				if (!btn) return;
				if (btn.dataset.age === 'no') {
					gate.classList.add('is-denied');
					$('.agegate__txt', gate).textContent = '죄송합니다. 성인만 이용할 수 있습니다.';
					gsap.fromTo('.agegate__box', { x: -10 }, { x: 0, duration: .5, ease: 'elastic.out(1, .3)' });
					return;
				}
				store.set('oneam_age', '1');
				gsap.to(gate, {
					clipPath: 'inset(0 0 100% 0)', duration: .9, ease: 'expo.inOut',
					onComplete: function () { gate.remove(); lock(false); resolve(); }
				});
			});
		});
	}

	/* --------------------------------------------------------------- Loader */
	function loader() {
		return new Promise(function (resolve) {
			var el = $('#loader');
			if (!el || reduce) { resolve(); return; }
			var num = $('#loader-num');
			var o = { m: 0 };
			var tl = gsap.timeline({ onComplete: resolve });
			tl.from('.loader__logo', { scale: .6, opacity: 0, duration: .8, ease: 'expo.out' })
				.to(o, {
					m: 60, duration: 1.1, ease: 'power2.inOut',
					onUpdate: function () {
						var m = Math.round(o.m);
						num.textContent = (m === 60 ? '01:00' : '00:' + String(m).padStart(2, '0')) + ' AM';
					}
				}, '<.1')
				.to('.loader__inner', { y: -40, opacity: 0, duration: .5, ease: 'power3.in' }, '+=.15')
				.to('.loader__bg', { clipPath: 'circle(0% at 50% 50%)', duration: 1, ease: 'expo.inOut' }, '-=.2');
		});
	}

	/* --------------------------------------------------------------- Header */
	var header = $('#site-header');
	var lastY = 0;
	function onScrollHeader(y) {
		if (!header || body.classList.contains('menu-open')) return;
		header.classList.toggle('is-hidden', y > lastY && y > 200);
		lastY = y;
	}
	if (lenis) lenis.on('scroll', function (l) { onScrollHeader(l.scroll); });
	else window.addEventListener('scroll', function () { onScrollHeader(window.scrollY); }, { passive: true });

	/* ----------------------------------------------------------------- Menu */
	var menuBtn = $('#menu-btn');
	function closeMenu() {
		if (!body.classList.contains('menu-open')) return;
		body.classList.remove('menu-open');
		menuBtn && menuBtn.setAttribute('aria-expanded', 'false');
		$('#menu') && $('#menu').setAttribute('aria-hidden', 'true');
		lock(false);
	}
	if (menuBtn) {
		menuBtn.addEventListener('click', function () {
			if (body.classList.contains('menu-open')) { closeMenu(); return; }
			body.classList.add('menu-open');
			menuBtn.setAttribute('aria-expanded', 'true');
			$('#menu').setAttribute('aria-hidden', 'false');
			lock(true);
		});
		document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeMenu(); });
	}

	/* --------------------------------------------------------------- Cursor */
	if (finePointer && !reduce) {
		var cursor = $('.cursor');
		var label = $('.cursor__label');
		var xTo = gsap.quickTo(cursor, 'x', { duration: .35, ease: 'power3' });
		var yTo = gsap.quickTo(cursor, 'y', { duration: .35, ease: 'power3' });
		window.addEventListener('pointermove', function (e) { xTo(e.clientX); yTo(e.clientY); });
		document.addEventListener('pointerover', function (e) {
			var t = e.target.closest('[data-cursor], a, button');
			cursor.classList.toggle('is-hover', !!t);
			var lbl = t && t.closest('[data-cursor]');
			cursor.classList.toggle('has-label', !!lbl);
			label.textContent = lbl ? lbl.dataset.cursor : '';
		});

		// 마그네틱 버튼
		$$('[data-magnetic]').forEach(function (el) {
			var mx = gsap.quickTo(el, 'x', { duration: .6, ease: 'elastic.out(1, .4)' });
			var my = gsap.quickTo(el, 'y', { duration: .6, ease: 'elastic.out(1, .4)' });
			el.addEventListener('pointermove', function (e) {
				var r = el.getBoundingClientRect();
				mx((e.clientX - r.left - r.width / 2) * .35);
				my((e.clientY - r.top - r.height / 2) * .35);
			});
			el.addEventListener('pointerleave', function () { mx(0); my(0); });
		});
	}

	/* ----------------------------------------------------------------- Hero */
	function setBodyColors(c1, c2, dur) {
		gsap.to(body, { '--c1': c1, '--c2': c2, duration: dur == null ? 1 : dur, ease: 'power2.out', overwrite: 'auto' });
	}

	function hero() {
		var root = $('#hero');
		if (!root) return { intro: function () {} };
		var devices = $$('.hero__device', root);
		var names = $$('.hero__name', root);
		var dots = $$('.hero__dots button', root);
		var idxEl = $('#hero-idx');
		var nameEl = $('#hero-flavor');
		var descEl = $('#hero-desc');
		var bar = $('.hero__progress span', root);
		var cur = 0;
		var busy = false;
		var DURATION = 5;
		var autoplay;

		// 첫 번째 외의 디바이스는 GSAP 가 제어
		devices.forEach(function (d, i) { if (i) gsap.set(d, { opacity: 0 }); });
		gsap.set(devices[0], { opacity: 1, rotate: -6, y: 0, scale: 1 });

		function go(next, dir) {
			if (busy || next === cur) return;
			busy = true;
			dir = dir || (next > cur ? 1 : -1);
			var from = devices[cur];
			var to = devices[next];
			var d = to.dataset;

			setBodyColors(d.c1, d.c2, 1);

			gsap.to(from, { y: -120 * dir, rotate: -6 + 30 * dir, opacity: 0, scale: .85, duration: .7, ease: 'power3.in' });
			gsap.fromTo(to,
				{ y: 160 * dir, rotate: -6 - 40 * dir, opacity: 0, scale: .8 },
				{ y: 0, rotate: -6, opacity: 1, scale: 1, duration: 1.2, delay: .35, ease: 'elastic.out(1, .6)', onComplete: function () { busy = false; } });

			names[cur].classList.remove('is-active');
			names[cur].classList.add('is-leaving');
			(function (n) { setTimeout(function () { n.classList.remove('is-leaving'); }, 900); })(names[cur]);
			names[next].classList.add('is-active');

			dots.forEach(function (b, i) { b.classList.toggle('is-active', i === next); });

			gsap.to([nameEl, descEl], {
				y: -14, opacity: 0, duration: .25, ease: 'power2.in', onComplete: function () {
					nameEl.textContent = d.name;
					descEl.textContent = d.desc;
					idxEl.textContent = String(next + 1).padStart(2, '0');
					gsap.fromTo([nameEl, descEl], { y: 14, opacity: 0 }, { y: 0, opacity: 1, duration: .5, stagger: .06, ease: 'power3.out' });
				}
			});

			cur = next;
			restart();
		}
		function nextSlide() { go((cur + 1) % devices.length, 1); }
		function prevSlide() { go((cur - 1 + devices.length) % devices.length, -1); }

		function restart() {
			if (autoplay) autoplay.kill();
			autoplay = gsap.fromTo(bar, { scaleX: 0 }, { scaleX: 1, duration: DURATION, ease: 'none', onComplete: nextSlide });
		}

		$$('[data-hero]', root).forEach(function (b) {
			b.addEventListener('click', function () { b.dataset.hero === 'next' ? nextSlide() : prevSlide(); });
		});
		dots.forEach(function (b) { b.addEventListener('click', function () { go(+b.dataset.idx); }); });
		root.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowRight') nextSlide();
			if (e.key === 'ArrowLeft') prevSlide();
		});

		// 스와이프 / 드래그
		var sx = null;
		root.addEventListener('pointerdown', function (e) { if (!e.target.closest('button, a')) sx = e.clientX; });
		window.addEventListener('pointerup', function (e) {
			if (sx === null) return;
			var dx = e.clientX - sx;
			sx = null;
			if (Math.abs(dx) > 50) (dx < 0 ? nextSlide : prevSlide)();
		});

		// 마우스 패럴랙스
		if (finePointer && !reduce) {
			var stage = $('.hero__stage', root);
			var rx = gsap.quickTo(stage, 'rotationY', { duration: .8, ease: 'power3' });
			var ry = gsap.quickTo(stage, 'rotationX', { duration: .8, ease: 'power3' });
			var sxTo = gsap.quickTo(stage, 'x', { duration: 1, ease: 'power3' });
			var orbs = $$('.orb', root);
			root.addEventListener('pointermove', function (e) {
				var px = e.clientX / window.innerWidth - .5;
				var py = e.clientY / window.innerHeight - .5;
				rx(px * 30); ry(-py * 20); sxTo(px * 40);
				orbs.forEach(function (o, i) { gsap.to(o, { x: px * (40 + i * 30), y: py * (40 + i * 30), duration: 1.2, ease: 'power3', overwrite: 'auto' }); });
			});
		}

		// 스크롤 시 히어로 빠져나가기
		gsap.timeline({ scrollTrigger: { trigger: root, start: 'top top', end: 'bottom top', scrub: true } })
			.to('.hero__names', { yPercent: 30, scale: 1.2 }, 0)
			.to('.hero__stage', { y: -120, scale: .9 }, 0)
			.to('.hero__copy, .hero__ctrl', { y: -80, opacity: 0 }, 0);

		// 화면 밖이면 자동재생 일시정지
		ST.create({ trigger: root, start: 'top bottom', end: 'bottom top', onToggle: function (s) { autoplay && (s.isActive ? autoplay.resume() : autoplay.pause()); } });

		return {
			intro: function () {
				var tl = gsap.timeline({ onComplete: restart });
				tl.from('.hero__title .line > span', { yPercent: 110, duration: 1.1, stagger: .1, ease: 'expo.out' })
					.from('.hero__kicker', { y: 20, opacity: 0, duration: .8, ease: 'expo.out' }, 0.1)
					.from(devices[0], { y: 300, rotate: 30, opacity: 0, duration: 1.6, ease: 'elastic.out(1, .7)' }, 0)
					.from('.hero__ctrl > *, .hero__dots, .hero__scroll', { y: 30, opacity: 0, duration: .8, stagger: .06, ease: 'expo.out' }, .3)
					.from('.orb', { scale: 0, duration: 1.2, stagger: .1, ease: 'back.out(2)' }, .2);
			}
		};
	}

	/* -------------------------------------------------------------- Marquee */
	function marquee() {
		var bands = $$('[data-marquee]');
		if (!bands.length) return;
		var items = bands.map(function (b) {
			var track = $('.marquee__track', b);
			return { track: track, dir: +b.dataset.marquee, x: 0, w: 0 };
		});
		function measure() { items.forEach(function (it) { it.w = it.track.scrollWidth / 4; }); }
		measure();
		window.addEventListener('resize', measure);
		var boost = 0;
		if (lenis) lenis.on('scroll', function (l) { boost = gsap.utils.clamp(-12, 12, l.velocity * .4); });
		gsap.ticker.add(function (t, dt) {
			var f = dt / 16.67;
			boost *= .94;
			items.forEach(function (it) {
				var speed = (1.2 + Math.abs(boost)) * it.dir * (boost < -0.5 ? -1 : 1);
				it.x -= speed * f;
				if (it.x <= -it.w) it.x += it.w;
				if (it.x > 0) it.x -= it.w;
				it.track.style.transform = 'translate3d(' + it.x + 'px,0,0)';
			});
		});
	}

	/* ------------------------------------------------------------------ Lab */
	function lab() {
		var root = $('#lab');
		if (!root) return;
		var track = $('.lab__track', root);
		var panels = $$('.lab__panel', root);
		var bg = $('.lab__bg', root);
		var dist = function () { return track.scrollWidth - window.innerWidth; };

		gsap.set(root, { '--c1': panels[0].dataset.c1, '--c2': panels[0].dataset.c2 });

		var tween = gsap.to(track, {
			x: function () { return -dist(); },
			ease: 'none',
			scrollTrigger: {
				trigger: root,
				start: 'top top',
				end: function () { return '+=' + dist(); },
				pin: true,
				scrub: 1,
				invalidateOnRefresh: true,
				onUpdate: function (s) {
					gsap.set('.lab__bar span', { scaleX: s.progress });
					// 패널 사이에서 배경 컬러 보간
					var p = s.progress * (panels.length - 1);
					var i = Math.min(Math.floor(p), panels.length - 2);
					var t = p - i;
					var a = panels[i].dataset, b = panels[i + 1].dataset;
					root.style.setProperty('--c1', gsap.utils.interpolate(a.c1, b.c1, t));
					root.style.setProperty('--c2', gsap.utils.interpolate(a.c2, b.c2, t));
				}
			}
		});

		panels.forEach(function (panel) {
			var chars = $$('.lab__name .ch', panel);
			var st = { trigger: panel, containerAnimation: tween, start: 'left 80%', end: 'left 20%', scrub: 1 };
			gsap.from(chars, { yPercent: 100, rotate: 12, opacity: 0, stagger: .03, ease: 'power3.out', scrollTrigger: st });
			gsap.fromTo($('.lab__img', panel), { rotate: 25, yPercent: 20 }, { rotate: -10, yPercent: 0, ease: 'none', scrollTrigger: { trigger: panel, containerAnimation: tween, start: 'left right', end: 'center center', scrub: true } });
			gsap.fromTo($('.lab__num', panel), { xPercent: 40 }, { xPercent: -20, ease: 'none', scrollTrigger: { trigger: panel, containerAnimation: tween, start: 'left right', end: 'right left', scrub: true } });
		});
		// 배경 자체는 CSS 변수로 갱신되므로 bg 참조만 유지
		return bg;
	}

	/* -------------------------------------------------------------- Flavors */
	function flavors() {
		var cards = $$('.card');
		if (!cards.length) return;

		gsap.from(cards, {
			y: 80, opacity: 0, duration: 1, stagger: { each: .05, grid: 'auto', from: 'start' }, ease: 'expo.out',
			scrollTrigger: { trigger: '.grid', start: 'top 85%' }
		});

		$$('.chip').forEach(function (chip) {
			chip.addEventListener('click', function () {
				$$('.chip').forEach(function (c) { c.classList.toggle('is-active', c === chip); });
				var f = chip.dataset.filter;
				gsap.to(cards, {
					opacity: 0, y: 20, duration: .25, stagger: .015, ease: 'power2.in', onComplete: function () {
						var shown = cards.filter(function (c) {
							var on = f === 'all' || c.dataset.cat === f;
							c.classList.toggle('is-hidden', !on);
							return on;
						});
						gsap.fromTo(shown, { opacity: 0, y: 40, scale: .95 }, { opacity: 1, y: 0, scale: 1, duration: .7, stagger: .04, ease: 'expo.out' });
						ST.refresh();
					}
				});
			});
		});

		// 틸트 + 컬러 채움 시작점
		cards.forEach(function (card) {
			var a = $('a', card);
			a.addEventListener('pointerenter', function (e) { setOrigin(e); });
			a.addEventListener('pointerleave', function (e) {
				setOrigin(e);
				gsap.to(a, { rotationX: 0, rotationY: 0, duration: .8, ease: 'elastic.out(1, .5)' });
			});
			if (finePointer && !reduce) {
				a.addEventListener('pointermove', function (e) {
					var r = a.getBoundingClientRect();
					var px = (e.clientX - r.left) / r.width - .5;
					var py = (e.clientY - r.top) / r.height - .5;
					gsap.to(a, { rotationY: px * 14, rotationX: -py * 14, duration: .5, ease: 'power3' });
				});
			}
			function setOrigin(e) {
				var r = a.getBoundingClientRect();
				a.style.setProperty('--mx', ((e.clientX - r.left) / r.width * 100) + '%');
				a.style.setProperty('--my', ((e.clientY - r.top) / r.height * 100) + '%');
			}
		});
	}

	/* --------------------------------------------------------------- Device */
	function device() {
		var root = $('#device');
		if (!root) return;
		var imgs = $$('.device__stack img', root);
		var tl = gsap.timeline({
			scrollTrigger: { trigger: root, start: 'top top', end: '+=250%', pin: '.device__pin', scrub: 1 }
		});
		tl.from('.device__title .line > span', { yPercent: 110, stagger: .1, duration: .6 }, 0)
			.from('.device__stack', { scale: .5, rotate: -30, y: 200, duration: 1 }, 0)
			.from('.callout', { opacity: 0, x: function (i) { return i % 2 ? 60 : -60; }, stagger: .3, duration: .5 }, .6);

		imgs.forEach(function (img, i) {
			if (!i) return;
			var at = .8 + i * .5;
			tl.to(imgs[i - 1], { opacity: 0, rotate: 12, duration: .4 }, at)
				.fromTo(img, { opacity: 0, rotate: -12 }, { opacity: 1, rotate: 0, duration: .4 }, at)
				.to(root, { '--c1': img.dataset.c1, '--c2': img.dataset.c2, duration: .4 }, at);
		});

		$$('[data-count]', root).forEach(function (dd) {
			var end = parseFloat(dd.dataset.count);
			if (isNaN(end)) return;
			var o = { v: 0 };
			gsap.to(o, {
				v: end, duration: 1.6, ease: 'power3.out',
				scrollTrigger: { trigger: dd, start: 'top bottom', once: true },
				onUpdate: function () { dd.textContent = Math.round(o.v); }
			});
		});
	}

	/* ------------------------------------------------------------------ CTA */
	function cta() {
		var root = $('#find');
		if (!root) return;
		var imgs = $$('.cta__fan img', root);
		gsap.fromTo(imgs,
			{ rotate: 0, x: 0, y: 80 },
			{
				rotate: function (i, el) { return +getComputedStyle(el).getPropertyValue('--o') * 11; },
				x: function (i, el) { return +getComputedStyle(el).getPropertyValue('--o') * Math.min(60, window.innerWidth / 18); },
				y: function (i, el) { return Math.abs(+getComputedStyle(el).getPropertyValue('--o')) * 14; },
				ease: 'none',
				scrollTrigger: { trigger: root, start: 'top 80%', end: 'center center', scrub: 1 }
			});
		var title = $('[data-fill]', root);
		gsap.fromTo(title, { '--fill': '0%' }, { '--fill': '100%', ease: 'none', scrollTrigger: { trigger: title, start: 'top 85%', end: 'bottom 45%', scrub: true } });
	}

	/* --------------------------------------------------------- 공통 리빌 */
	function reveals() {
		$$('[data-reveal]').forEach(function (el) {
			gsap.from($$('.line > span', el), { yPercent: 110, duration: 1.1, stagger: .1, ease: 'expo.out', scrollTrigger: { trigger: el, start: 'top 85%' } });
		});
		var logo = $('[data-footer-logo] img');
		if (logo) gsap.from(logo, { yPercent: 40, opacity: 0, ease: 'none', scrollTrigger: { trigger: '.site-footer', start: 'top bottom', end: 'bottom bottom', scrub: true } });

		// 상세 페이지
		if ($('.pdp')) {
			gsap.from('.pdp__device img', { y: 200, rotate: 25, opacity: 0, duration: 1.4, ease: 'elastic.out(1, .7)' });
			gsap.from('.pdp__name .ch', { yPercent: 100, opacity: 0, stagger: .03, duration: 1, ease: 'expo.out' });
			gsap.from('.pdp__copy > *', { y: 40, opacity: 0, stagger: .08, duration: 1, ease: 'expo.out', delay: .2 });
		}
	}

	function headerTheme() {
		// 어두운 섹션 위에서는 헤더 로고를 흰색으로
		$$('[data-header="light"]').forEach(function (sec) {
			ST.create({
				trigger: sec, start: 'top 40px', end: 'bottom 40px',
				onToggle: function (s) { header && header.classList.toggle('is-light', s.isActive); }
			});
		});
	}

	/* ------------------------------------------------------------------ Run */
	var h = hero();
	marquee();
	if (!reduce) {
		lab();
		flavors();
		device();
		cta();
		reveals();
	}
	// 핀 섹션이 모두 만들어진 뒤에 생성해야 위치 계산이 맞음
	headerTheme();
	if (reduce) {
		flavors();
	}

	ageGate()
		.then(loader)
		.then(function () {
			body.classList.remove('is-loading');
			h.intro();
			ST.refresh();
		});

	window.addEventListener('load', function () { ST.refresh(); });
})();
