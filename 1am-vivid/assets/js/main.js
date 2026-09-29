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
	var lite = !!cfg.lite; // 쇼핑몰(장바구니·결제·내 계정) 페이지
	var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

	if (!gsap) { body.classList.remove('is-loading'); return; }
	gsap.registerPlugin(ST);

	var store = {
		get: function (k) { try { return window.localStorage.getItem(k); } catch (e) { return null; } },
		set: function (k, v) { try { window.localStorage.setItem(k, v); } catch (e) { /* noop */ } }
	};

	/* ---------------------------------------------------------------- Lenis */
	var lenis = null;
	if (window.Lenis && !reduce && !lite) {
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
					$('.agegate__txt', gate).textContent = 'Sorry, this site is for adults only.';
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
		if (!header || body.classList.contains('nav-open')) return;
		header.classList.toggle('is-hidden', y > lastY && y > 200);
		lastY = y;
	}
	if (lenis) lenis.on('scroll', function (l) { onScrollHeader(l.scroll); });
	else window.addEventListener('scroll', function () { onScrollHeader(window.scrollY); }, { passive: true });

	/* ----------------------------------------------------------------- Menu */
	var menuBtn = $('#menu-btn');
	function closeMenu() {
		if (!body.classList.contains('nav-open')) return;
		body.classList.remove('nav-open');
		menuBtn && menuBtn.setAttribute('aria-expanded', 'false');
	}
	if (menuBtn) {
		menuBtn.addEventListener('click', function () {
			if (body.classList.contains('nav-open')) { closeMenu(); return; }
			body.classList.add('nav-open');
			menuBtn.setAttribute('aria-expanded', 'true');
		});
		document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeMenu(); });
	}

	/* --------------------------------------------------------------- Cursor */
	if (finePointer && !reduce && !lite) {
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

	/* ------------------------------------------------------------------ Lab */
	function lab() {
		var root = $('#products');
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
	}

	/* ------------------------------------------------------------------ CTA */
	function cta() {
		var root = $('#wholesale');
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
		// 숫자 카운트 (회사 소개 / 스펙)
		$$('[data-count]').forEach(function (el) {
			var end = parseFloat(el.dataset.count);
			if (isNaN(end)) return;
			var suffix = el.textContent.replace(/^[\d.,\s]+/, '');
			var o = { v: 0 };
			gsap.to(o, {
				v: end, duration: 1.6, ease: 'power3.out',
				scrollTrigger: { trigger: el, start: 'top 90%', once: true },
				onUpdate: function () { el.textContent = Math.round(o.v) + suffix; }
			});
		});

		// 서브 페이지 제목
		if ($('.page-head')) gsap.from('.page-head__title .ch', { yPercent: 100, opacity: 0, stagger: .025, duration: 1, ease: 'expo.out', delay: .2 });

		// 상세 페이지
		if ($('.pdp')) {
			gsap.from('.pdp__device > *', { y: 200, rotate: 25, opacity: 0, duration: 1.4, ease: 'elastic.out(1, .7)' });
			gsap.from('.pdp__name .ch', { yPercent: 100, opacity: 0, stagger: .03, duration: 1, ease: 'expo.out' });
			gsap.from('.pdp__copy > *', { y: 40, opacity: 0, stagger: .08, duration: 1, ease: 'expo.out', delay: .2 });
		}
	}

	/* ------------------------------------------------------ Film (intro) */
	function film() {
		var root = $('#top');
		if (!root) return;
		var frame = $('.film__frame', root);
		// 스크롤하면 전체 화면 영상이 둥근 카드로 살짝 줄어들며 넘어감
		gsap.timeline({ scrollTrigger: { trigger: root, start: 'top top', end: 'bottom top', scrub: true } })
			.to(frame, { clipPath: 'inset(6% 5% 6% 5% round 32px)', ease: 'none' }, 0)
			.to('.film__copy', { y: -60, opacity: 0, ease: 'none' }, 0);

		var video = $('video', root);
		var btn = $('.film__sound', root);
		if (video && btn) {
			btn.addEventListener('click', function () {
				video.muted = !video.muted;
				btn.setAttribute('aria-pressed', String(!video.muted));
				btn.textContent = video.muted ? 'Sound off' : 'Sound on';
			});
		}
		if (video) ST.create({ trigger: root, start: 'top bottom', end: 'bottom top', onToggle: function (s) { s.isActive ? video.play().catch(function () {}) : video.pause(); } });
	}
	function filmIntro() {
		if (!$('#top')) return;
		gsap.timeline()
			.from('.film__title .line > span', { yPercent: 110, duration: 1.1, stagger: .1, ease: 'expo.out' })
			.from('.film__kicker, .film__text, .film__btns', { y: 24, opacity: 0, duration: .8, stagger: .08, ease: 'expo.out' }, .2)
			.from('.film__media--empty img', { y: 200, rotate: 20, opacity: 0, duration: 1.4, ease: 'elastic.out(1, .7)' }, 0);
	}

	/* ------------------------------------------- About + flavour line-up */
	function about() {
		var title = $('.about [data-words]');
		if (title) {
			title.innerHTML = title.textContent.trim().split(/\s+/).map(function (w) { return '<span class="wd">' + w + '</span>'; }).join(' ');
			gsap.fromTo($$('.wd', title), { opacity: .15 }, { opacity: 1, stagger: .1, ease: 'none', scrollTrigger: { trigger: title, start: 'top 80%', end: 'bottom 50%', scrub: true } });
		}

		var row = $('#row');
		if (!row) return;
		var items = $$('.row__item', row);
		var track = $('.row__track', row);
		var n = items.length;
		var mid = (n - 1) / 2;
		var gap = function () { return Math.min(130, Math.max(64, window.innerWidth / 11)); };

		// 한 개로 겹쳐 있다가 → 일자로 쫙 펼쳐지고 → 줄 전체가 옆으로 이동
		gsap.set(items, { x: 0, rotate: function (i) { return (i - mid) * 1.5; } });
		var tl = gsap.timeline({
			scrollTrigger: { trigger: row, start: 'top top', end: '+=180%', pin: true, scrub: 1, invalidateOnRefresh: true }
		});
		tl.to(items, { x: function (i) { return (i - mid) * gap(); }, rotate: 0, duration: 1, ease: 'power2.inOut', stagger: { each: .01, from: 'center' } }, 0)
			.fromTo('.row__caption', { opacity: 0, y: 20 }, { opacity: 1, y: 0, duration: .3 }, .7)
			.fromTo(track, { x: function () { return Math.max(0, (n * gap() - window.innerWidth) / 2 + 40); } },
				{ x: function () { return -Math.max(0, (n * gap() - window.innerWidth) / 2 + 40); }, duration: 1.2, ease: 'none' }, 1);

		// 펼쳐진 뒤 살짝 물결치듯 움직임
		items.forEach(function (it, i) {
			gsap.to($('img', it), { y: -14, duration: 1.6, ease: 'sine.inOut', yoyo: true, repeat: -1, delay: i * .12 });
		});
	}

	/* ------------------------------------------------------ Benefits/Steps */
	function blocks() {
		if ($('.benefits__list')) {
			gsap.from('.benefit', { y: 80, opacity: 0, rotate: function (i) { return i % 2 ? 3 : -3; }, stagger: .08, duration: 1, ease: 'expo.out', scrollTrigger: { trigger: '.benefits__list', start: 'top 85%' } });
		}
		$$('.steps').forEach(function (sec) {
			gsap.fromTo($('.steps__line i', sec), { scaleX: 0 }, { scaleX: 1, ease: 'none', scrollTrigger: { trigger: $('.steps__track', sec), start: 'top 75%', end: 'bottom 55%', scrub: true } });
			gsap.from($$('.step', sec), { y: 50, opacity: 0, stagger: .15, duration: .9, ease: 'expo.out', scrollTrigger: { trigger: $('.steps__track', sec), start: 'top 80%' } });
		});
		$$('.faq__item').forEach(function (d) {
			// 아코디언: 하나만 열기
			d.addEventListener('toggle', function () {
				if (d.open) $$('.faq__item').forEach(function (o) { if (o !== d) o.open = false; });
				ST.refresh();
			});
		});
	}

	/* ------------------------------------------------------------------ Run */
	var h = hero();
	if (!reduce) {
		film();
		about();
		lab();
		flavors();
		device();
		blocks();
		cta();
		reveals();
	}
	if (reduce) {
		flavors();
	}
	ST.sort();

	ageGate()
		.then(loader)
		.then(function () {
			body.classList.remove('is-loading');
			h.intro();
			filmIntro();
			ST.refresh();
		});

	window.addEventListener('load', function () { ST.refresh(); });
})();
