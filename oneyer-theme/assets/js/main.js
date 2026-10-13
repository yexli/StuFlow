/**
 * ONEYER · 全站脚本（渐进增强）
 *
 * 原则：
 *  - 所有组件挂载点不存在时安静跳过；禁用 JS 时导航/搜索/分页/评论完全可用。
 *  - 尊重 prefers-reduced-motion：平滑滚动与数字动画降级为直接呈现。
 *  - 滚动监听统一 rAF 节流，不执行昂贵 DOM 查询。
 */
(function () {
	'use strict';

	var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var $ = function (sel, ctx) { return (ctx || document).querySelector(sel); };
	var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); };

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

	/* ── Ctrl / Cmd + K 聚焦搜索 ───────────────────────── */
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

	/* ── 数字滚动（Hero 统计） ──────────────────────────── */
	$$('[data-count]').forEach(function (el) {
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

	/* ── 滚动渐入 + 技能条生长（关于页） ────────────────── */
	var revealEls = $$('.reveal');
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

	/* ── 文章目录 scroll-spy（仅单篇文章存在 #toc 时） ──── */
	var toc = $('#toc');
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

	/* ── 归档年份锚点高亮（#yearTabs） ─────────────────── */
	var yearTabs = $('#yearTabs');
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

	/* ── 复制按钮：友链信息卡（data-copy）+ 代码块（.codeblock） ── */
	function bindCopy(btn, getText, doneText, idleText) {
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var txt = getText(btn);
			if (!txt) return;
			var done = function () {
				btn.textContent = doneText;
				if (btn.classList.contains('copy')) btn.classList.add('done');
				setTimeout(function () {
					btn.textContent = idleText;
					if (btn.classList.contains('copy')) btn.classList.remove('done');
				}, 1500);
			};
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(txt).then(done, function () {});
			} else {
				var ta = document.createElement('textarea');
				ta.value = txt;
				document.body.appendChild(ta);
				ta.select();
				try { document.execCommand('copy'); done(); } catch (err) {}
				document.body.removeChild(ta);
			}
		});
	}
	$$('[data-copy]').forEach(function (btn) {
		bindCopy(btn, function (b) { return b.getAttribute('data-copy'); }, '已复制', '复制');
	});
	/* 代码块复制：委托绑定，兼容 AJAX 追加的内容（S-01 契约） */
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

	/* 站点 Logo 图片加载失败 → 回退品牌渐变字符方块（data-oneyer-fb-char 存兜底字符） */
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
	/* 兜底二：main.js 在页脚延迟执行，首屏图片可能已失败完毕——扫描已加载失败的 Logo 图 */
	$$('img.logo-mark').forEach(function (img) {
		if (img.complete && img.naturalWidth === 0) oneyerLogoFallback(img);
		else if (!img.complete) {
			img.addEventListener('load', function () {}, { once: true });
			img.addEventListener('error', function () { oneyerLogoFallback(img); }, { once: true });
		}
	});

	/* 文章页「复制链接」按钮：保留图标，仅临时替换提示文本 */
	$$('[data-share-copy]').forEach(function (btn) {
		var original = btn.innerHTML;
		btn.addEventListener('click', function () {
			var url = btn.getAttribute('data-share-copy');
			if (!url) return;
			var done = function () {
				btn.textContent = '已复制';
				setTimeout(function () { btn.innerHTML = original; }, 1500);
			};
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(url).then(done, function () {});
			} else {
				var ta = document.createElement('textarea');
				ta.value = url;
				document.body.appendChild(ta);
				ta.select();
				try { document.execCommand('copy'); done(); } catch (err) {}
				document.body.removeChild(ta);
			}
		});
	});

	/* ── 首页「加载更多」：AJAX 追加下一页卡片 ───────────── */
	var loadWrap = $('#loadWrap');
	if (loadWrap && window.ONEYER) {
		var btn = $('#loadMore');
		var hint = $('#loadHint');
		var grid = $('#grid');
		var pager = $('#homePager');
		var maxPages = parseInt(loadWrap.getAttribute('data-max'), 10) || 1;
		var found = parseInt(loadWrap.getAttribute('data-found'), 10) || 0;
		var perPage = parseInt(loadWrap.getAttribute('data-per-page'), 10) || 9;
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
						// 仅移动元素节点，跳过响应中的空白文本节点。
						$$('article.card', tmp).forEach(function (card) {
							card.style.animation = 'rise .55s both';
							grid.appendChild(card);
						});
						if (hint) {
							hint.textContent = '已展示 ' + Math.min(current * perPage, found) + ' / ' + found + ' 篇';
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
})();
