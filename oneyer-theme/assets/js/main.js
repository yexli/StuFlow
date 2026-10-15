/**
 * ONEYER · 全站脚本（渐进增强 · 可重入）
 *
 * 原则：
 *  - 所有组件挂载点不存在时安静跳过；禁用 JS 时导航/搜索/分页/评论完全可用。
 *  - 尊重 prefers-reduced-motion：平滑滚动与数字动画降级为直接呈现。
 *  - 滚动监听统一 rAF 节流，不执行昂贵 DOM 查询。
 *
 * 结构（配合 pjax.js）：
 *  - 全局区：绑定一次，元素位于导航/页脚等 pjax 不替换区域（进度条/抽屉/快捷键/委托复制/Logo 兜底）。
 *  - 视图区 mountAll(ctx)：随 #oneyer-main 替换重建的组件（数字滚动/渐入/目录/加载更多/…），
 *    由 pjax 转场后经 ONEYER_UI.init(ctx) 重新挂载。
 */
(function () {
	'use strict';

	var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var $ = function (sel, ctx) { return (ctx || document).querySelector(sel); };
	var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); };

	/* ═══════════════ 全局区（只绑一次） ═══════════════ */

	/* ── 阅读进度条 + 导航投影 + 返回顶部 ───────────────── */
	var progress = $('#progress');
	var nav = $('#nav');
	var backtop = $('#backtop');
	var ticking = false;

	function onScroll() {
		if (ticking) return;
		ticking = true;
		requestAnimationFrame(function () {
			ticking = false;
			var h = document.documentElement;
			var max = h.scrollHeight - h.clientHeight;
			if (progress && max > 0) {
				progress.style.width = (h.scrollTop / max * 100) + '%';
			}
			if (nav) nav.classList.toggle('scrolled', h.scrollTop > 10);
			if (backtop) backtop.classList.toggle('show', h.scrollTop > 600);
			if (window.ONEYER_SB) window.ONEYER_SB.update();
		});
	}
	window.addEventListener('scroll', onScroll, { passive: true });
	onScroll();

	if (backtop) {
		backtop.addEventListener('click', function () {
			window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' });
		});
	}

	/* ── 移动端抽屉 ─────────────────────────────────────── */
	var drawer = $('#drawer');
	var mask = $('#drawerMask');
	var burger = $('#burger');
	var drawerClose = $('#drawerClose');

	function openDrawer() {
		if (!drawer) return;
		drawer.classList.add('open');
		drawer.setAttribute('aria-hidden', 'false');
		if (mask) mask.classList.add('open');
		if (burger) burger.setAttribute('aria-expanded', 'true');
		document.body.style.overflow = 'hidden';
		var first = drawerClose || drawer.querySelector('a, input, button');
		if (first) first.focus();
	}
	function closeDrawer() {
		if (!drawer) return;
		drawer.classList.remove('open');
		drawer.setAttribute('aria-hidden', 'true');
		if (mask) mask.classList.remove('open');
		if (burger) burger.setAttribute('aria-expanded', 'false');
		document.body.style.overflow = '';
		if (burger) burger.focus();
	}
	if (burger && drawer) {
		burger.addEventListener('click', openDrawer);
		if (drawerClose) drawerClose.addEventListener('click', closeDrawer);
		if (mask) mask.addEventListener('click', closeDrawer);
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && drawer.classList.contains('open')) closeDrawer();
		});
	}

	/* ── 快捷键：Ctrl/Cmd + K 聚焦搜索；搜索框回车显式提交 ── */
	document.addEventListener('keydown', function (e) {
		if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
			var t = e.target;
			var typing = t && (/(INPUT|TEXTAREA|SELECT)/.test(t.tagName) || t.isContentEditable);
			if (typing) return;
			var box = $('.nav-search input[name="s"]') || $('input[name="s"]');
			if (box) {
				e.preventDefault();
				box.focus();
				box.select();
			}
		}
		// 搜索框回车显式提交（原生隐式提交在个别环境不触发）。
		if (e.key === 'Enter' && e.target && e.target.matches && e.target.matches('input[name="s"]') && e.target.form && !e.isComposing) {
			e.preventDefault();
			if (e.target.form.requestSubmit) e.target.form.requestSubmit();
			else e.target.form.submit();
		}
	});

	/* ── 复制工具（纯函数，供各复制组件复用） ────────────── */
	function copyTextFallback(txt) {
		var ta = document.createElement('textarea');
		ta.value = txt;
		ta.setAttribute('readonly', '');
		ta.style.position = 'fixed';
		ta.style.left = '-9999px';
		document.body.appendChild(ta);
		ta.select();
		var ok = false;
		try { ok = document.execCommand('copy'); } catch (err) { ok = false; }
		document.body.removeChild(ta);
		return ok;
	}

	/* ── 代码块复制：委托绑定（兼容 pjax/AJAX 追加的内容） ── */
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.codeblock .copy');
		if (!btn) return;
		var block = btn.closest('.codeblock');
		var pre = block ? block.querySelector('pre') : null;
		var code = pre ? pre.innerText : '';
		if (!code) return;
		var restore = function () { setTimeout(function () { btn.textContent = 'copy'; }, 1500); };
		var done = function (ok) {
			btn.textContent = ok ? '已复制' : '失败';
			restore();
		};
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(code).then(
				function () { done(true); },
				function () { done(copyTextFallback(code)); }
			);
		} else {
			done(copyTextFallback(code));
		}
	});

	/* ── 站点 Logo 图片加载失败 → 回退品牌渐变字符方块 ────── */
	function oneyerLogoFallback(img) {
		if (img.dataset.oneyerFbDone) return;
		img.dataset.oneyerFbDone = '1';
		var span = document.createElement('span');
		span.className = img.className.replace(/\bis-img\b/g, '').trim();
		span.setAttribute('aria-hidden', 'true');
		span.textContent = img.dataset.oneyerFbChar || 'S';
		/* 行内 style 是 img 的 width/height/font-size，span 直接继承可用，仅补文字色/布局/背景 */
		span.style.background = 'linear-gradient(135deg,#5aabff 0%,#2f6fd6 60%,#1b4f9e 100%)';
		span.style.color = '#fff';
		span.style.display = 'grid';
		span.style.placeItems = 'center';
		img.replaceWith(span);
	}
	document.addEventListener('error', function (e) {
		var img = e.target;
		if (!(img instanceof HTMLImageElement) || !img.classList.contains('logo-mark')) return;
		oneyerLogoFallback(img);
	}, true); /* capture：img 的 error 事件不冒泡 */

	/* ═══════════════ 视图区（随 #oneyer-main 重建） ═══════════════ */

	function mountAll(ctx) {
		ctx = ctx || document;

		/* ── 数字滚动（Hero/关于页统计） ──────────────────── */
		$$('[data-count]', ctx).forEach(function (el) {
			var target = parseInt(el.getAttribute('data-count'), 10) || 0;
			if (reduced) { el.textContent = target.toLocaleString('en-US'); return; }
			var dur = 1300;
			var t0 = performance.now();
			(function tick(t) {
				var p = Math.min((t - t0) / dur, 1);
				var e = 1 - Math.pow(1 - p, 3);
				el.textContent = Math.round(target * e).toLocaleString('en-US');
				if (p < 1) requestAnimationFrame(tick);
			})(t0);
		});

		/* ── 滚动渐入 + 技能条生长（关于页） ──────────────── */
		var revealEls = $$('.reveal', ctx);
		if (revealEls.length && 'IntersectionObserver' in window) {
			var io = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (!entry.isIntersecting) return;
					entry.target.classList.add('in');
					$$('.s-bar i', entry.target).forEach(function (bar, i) {
						setTimeout(function () { bar.style.width = bar.getAttribute('data-w'); }, reduced ? 0 : i * 120);
					});
					io.unobserve(entry.target);
				});
			}, { threshold: 0, rootMargin: '0px 0px -8% 0px' });
			revealEls.forEach(function (el) { io.observe(el); });
		} else {
			revealEls.forEach(function (el) { el.classList.add('in'); });
		}

		/* ── 文章目录 scroll-spy（仅单篇文章存在 #toc 时） ── */
		var toc = $('#toc', ctx);
		if (toc && 'IntersectionObserver' in window) {
			var tocLinks = $$('a', toc);
			var heads = tocLinks
				.map(function (a) { return document.getElementById((a.getAttribute('href') || '').slice(1)); })
				.filter(Boolean);
			if (heads.length) {
				var spy = new IntersectionObserver(function (entries) {
					entries.forEach(function (entry) {
						if (!entry.isIntersecting) return;
						tocLinks.forEach(function (a) {
							a.classList.toggle('on', a.getAttribute('href') === '#' + entry.target.id);
						});
					});
				}, { rootMargin: '-20% 0px -70% 0px' });
				heads.forEach(function (h) { spy.observe(h); });
			}
		}

		/* ── 归档年份锚点高亮（#yearTabs） ────────────────── */
		var yearTabs = $('#yearTabs', ctx);
		if (yearTabs && 'IntersectionObserver' in window) {
			var ytabs = $$('.ytab', yearTabs);
			var years = ytabs
				.map(function (a) { return document.getElementById((a.getAttribute('href') || '').slice(1)); })
				.filter(Boolean);
			if (years.length) {
				var yspy = new IntersectionObserver(function (entries) {
					entries.forEach(function (entry) {
						if (!entry.isIntersecting) return;
						ytabs.forEach(function (a) {
							a.classList.toggle('on', a.getAttribute('href') === '#' + entry.target.id);
						});
					});
				}, { rootMargin: '-20% 0px -60% 0px' });
				years.forEach(function (y) { yspy.observe(y); });
			}
		}

		/* ── 友链信息卡复制（data-copy） ──────────────────── */
		function bindCopy(btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var txt = btn.getAttribute('data-copy');
				if (!txt) return;
				var done = function () {
					btn.textContent = '已复制';
					if (btn.classList.contains('copy')) btn.classList.add('done');
					setTimeout(function () {
						btn.textContent = '复制';
						if (btn.classList.contains('copy')) btn.classList.remove('done');
					}, 1500);
				};
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(txt).then(done, function () {});
				} else {
					if (copyTextFallback(txt)) done();
				}
			});
		}
		$$('[data-copy]', ctx).forEach(bindCopy);

		/* ── 文章页「复制链接」按钮：保留图标，仅临时替换提示 ─ */
		$$('[data-share-copy]', ctx).forEach(function (btn) {
			var original = btn.innerHTML;
			btn.addEventListener('click', function () {
				var url = btn.getAttribute('data-share-copy');
				if (!url) return;
				var done = function () {
					btn.textContent = '已复制';
					setTimeout(function () { btn.innerHTML = original; }, 1500);
				};
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(url).then(done, function () {
						if (copyTextFallback(url)) done();
					});
				} else {
					if (copyTextFallback(url)) done();
				}
			});
		});

		/* ── 首页「加载更多」：AJAX 追加下一页卡片 ────────── */
		var loadWrap = $('#loadWrap', ctx);
		if (loadWrap && window.ONEYER) {
			var btn = $('#loadMore', ctx);
			var hint = $('#loadHint', ctx);
			var grid = $('#grid', ctx);
			var pager = $('#homePager', ctx);
			var maxPages = parseInt(loadWrap.getAttribute('data-max'), 10) || 1;
			var found = parseInt(loadWrap.getAttribute('data-found'), 10) || 0;
			var shown = $$('#grid .card', ctx).length; /* 以实际卡片数计提示，兼容追加条数设置 */
			var current = 1;

			// 有 JS 才切换到加载更多模式；无 JS 时保留常规分页。
			if (grid && maxPages > 1) {
				if (pager) pager.style.display = 'none';
				btn.style.display = '';
			} else if (btn) {
				btn.style.display = 'none';
			}

			if (btn && grid && maxPages > 1) {
				btn.addEventListener('click', function () {
					if (btn.classList.contains('loading')) return;
					btn.classList.add('loading');
					var body = new URLSearchParams();
					body.append('action', 'oneyer_loadmore');
					body.append('nonce', window.ONEYER.nonce);
					body.append('paged', String(current + 1));

					fetch(window.ONEYER.ajax, {
						method: 'POST',
						credentials: 'same-origin',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
						body: body.toString()
					})
						.then(function (res) { return res.json(); })
						.then(function (json) {
							btn.classList.remove('loading');
							if (!json || !json.success) throw new Error('bad response');
							current++;
							var tmp = document.createElement('div');
							tmp.innerHTML = json.data.html;
							var added = 0;
							// 仅移动元素节点，跳过响应中的空白文本节点。
							$$('article.card', tmp).forEach(function (card) {
								card.style.animation = 'rise .55s both';
								grid.appendChild(card);
								added++;
							});
							shown += Math.min(added, found - shown);
							if (hint) {
								hint.textContent = '已展示 ' + shown + ' / ' + found + ' 篇';
							}
							if (json.data.done || current >= maxPages) {
								btn.textContent = '— 已经到底啦 —';
								btn.style.borderColor = 'var(--line)';
								btn.style.color = 'var(--ink-3)';
								btn.style.cursor = 'default';
								btn.disabled = true;
								if (hint) hint.textContent = '共 ' + found + ' 篇，去分类页翻翻旧文吧';
							}
						})
						.catch(function () {
							btn.classList.remove('loading');
							// 出错时回退到常规分页，保证可继续浏览。
							if (pager) { pager.style.display = ''; btn.style.display = 'none'; }
						});
				});
			}
		}
		if (window.ONEYER_HOMECAT && window.ONEYER_HOMECAT.mount) window.ONEYER_HOMECAT.mount(ctx);
	}

	/* ── 自绘滚动指示条：自动显隐 + 拖拽定位（无 JS 回退原生滚动条） ──
	 * 仅桌面精确指针环境显示（触屏由 CSS 隐藏，原生 overlay 已有）。 */
	(function () {
		if (!window.matchMedia || window.matchMedia('(hover: none)').matches) return;
		var root = document.createElement('div');
		root.className = 'oy-scrollbar';
		root.setAttribute('aria-hidden', 'true');
		var thumb = document.createElement('div');
		thumb.className = 'oy-scrollbar__thumb';
		root.appendChild(thumb);
		document.body.appendChild(root);

		var hideTimer = null, dragging = false, dragY = 0, dragTop = 0;

		function metrics() {
			var h = document.documentElement;
			var max = h.scrollHeight - h.clientHeight;
			return { h: h, max: max, ratio: max > 0 ? h.scrollTop / max : 0 };
		}
		function layout() {
			var m = metrics();
			var trackH = root.clientHeight || window.innerHeight;
			var th = Math.max(40, Math.round(trackH * (m.h.clientHeight / Math.max(1, m.h.scrollHeight))));
			thumb.style.height = th + 'px';
			thumb.style.top = Math.round((trackH - th) * m.ratio) + 'px';
		}
		function show() {
			layout();
			root.classList.add('is-visible');
			clearTimeout(hideTimer);
			hideTimer = setTimeout(function () {
				if (!dragging) root.classList.remove('is-visible');
			}, 900);
		}
		root.addEventListener('pointerdown', function (e) {
			if (e.target !== thumb) return; // 点空白区域不响应，保持滚动条惯常行为
			dragging = true;
			root.classList.add('is-dragging', 'is-visible');
			dragY = e.clientY;
			dragTop = (document.documentElement.scrollHeight - document.documentElement.clientHeight) * metrics().ratio;
			root.setPointerCapture && root.setPointerCapture(e.pointerId);
			e.preventDefault();
		});
		document.addEventListener('pointermove', function (e) {
			if (!dragging) return;
			var m = metrics();
			var trackH = root.clientHeight || window.innerHeight;
			var th = parseFloat(thumb.style.height) || 40;
			var dy = e.clientY - dragY;
			var top = Math.max(0, Math.min(trackH - th, dragTop + dy));
			thumb.style.top = Math.round(top) + 'px';
			m.h.scrollTop = (trackH - th) > 0 ? (top / (trackH - th)) * m.max : 0;
		});
		document.addEventListener('pointerup', function () {
			if (!dragging) return;
			dragging = false;
			root.classList.remove('is-dragging');
			show(); // 重新进入「停止后淡出」计时
		});
		window.addEventListener('resize', layout);

		window.ONEYER_SB = { update: show, layout: layout };
	})();


	/* ═══════════════ 导出与首挂 ═══════════════ */
	/* init 供 pjax.js 转场后重挂视图组件；closeDrawer 供抽屉内 pjax 点击联动（M4）。 */
	/* skip-link 目标可聚焦（键盘跳转主内容） */
	var mainEl = document.getElementById('oneyer-main');
	if (mainEl && !mainEl.hasAttribute('tabindex')) mainEl.setAttribute('tabindex', '-1');

	window.ONEYER_UI = {
		init: function (ctx) { mountAll(ctx || document); },
		closeDrawer: closeDrawer
	};
	mountAll(document);
})();
