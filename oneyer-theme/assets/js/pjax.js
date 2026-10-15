/**
 * ONEYER · Pjax 渐进增强导航（AJAX切页方案）
 *
 * 原则：任何失败一律回退真实跳转；无 JS 时全站原生可用（本脚本不启用即无行为）。
 * 拦截规则（唯一事实源）：同源 <a href>、无 _blank/download、非 # 纯锚、
 * 非 wp-admin/wp-login、非 .no-pjax 逃生舱、无修饰键左键、非文件直链、非 replytocom。
 */
(function () {
	'use strict';

	if (!window.fetch || !window.history || !window.DOMParser) return; // 老浏览器直接不启用

	var MAIN_ID = 'oneyer-main';
	var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var OUT_MS = reduced ? 0 : 150;
	var IN_MS = reduced ? 0 : 250;

	var FILE_RE = /\.(jpe?g|png|gif|webp|avif|svg|ico|zip|rar|7z|tar|gz|pdf|mp4|mp3|txt|xml)(\?|#|$)/i;
	var cache = new Map();          // LRU：url → { doc }（doc 为已解析主区节点）
	var CACHE_MAX = 5;
	var controller = null;          // 竞态取消
	var pendingUrl = null;

	/* ── 工具 ─────────────────────────────── */
	function sameOrigin(url) {
		try { return new URL(url, location.href).origin === location.origin; }
		catch (e) { return false; }
	}
	function shouldIntercept(el, ev) {
		if (!el) return false;
		if (ev && (ev.defaultPrevented || ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey)) return false;
		if (el.target === '_blank' || el.hasAttribute('download')) return false;
		var href = el.getAttribute('href');
		if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) return false;
		if (el.closest('.no-pjax')) return false;
		if (/\/wp-(admin|login)\.php/i.test(href)) return false;
		if (href.indexOf('replytocom=') > -1) return false; // 评论回复走原生刷新
		if (FILE_RE.test(href)) return false;
		return sameOrigin(href);
	}

	/* ── 加载条 ───────────────────────────── */
	var bar = document.querySelector('.pjax-bar');
	var scrollBar = document.querySelector('.progress'); // 阅读进度条：pjax 转场期间让位，避免双条叠加
	function barStart() {
		if (!bar) return;
		bar.classList.add('is-loading');
		bar.style.width = '80%';
		if (scrollBar) scrollBar.style.opacity = '0';
	}
	function barEnd() {
		if (!bar) return;
		bar.style.width = '100%';
		setTimeout(function () { bar.classList.remove('is-loading'); bar.style.width = '0'; }, 200);
		if (scrollBar) setTimeout(function () { scrollBar.style.opacity = ''; }, 220);
	}
	function barFail() {
		if (!bar) return;
		bar.classList.remove('is-loading');
		bar.style.width = '0';
		if (scrollBar) scrollBar.style.opacity = '';
	}

	/* ── 抓取与解析 ───────────────────────── */
	function parseDoc(html) {
		var doc = new DOMParser().parseFromString(html, 'text/html');
		var main = doc.getElementById(MAIN_ID);
		if (!main) return null; // 目标页没有主区（异常模板）→ 触发兜底
		var na = doc.querySelector('.nav-menu > li > a.active');
		var da = doc.querySelector('.drawer a.d-link.on');
		return {
			main: main,
			title: doc.title || '',
			bodyClass: doc.body.getAttribute('class') || '',
			/* 解析期捕获导航/抽屉 active href：缓存节点复用时仍能正确同步（不依赖已并入本文档的节点） */
			activeHref: na ? na.getAttribute('href') : null,
			drawerActive: da ? da.getAttribute('href') : null
		};
	}
	function syncNav(activeHref) {
		var cur = document.querySelector('.nav-menu > li > a.active');
		if (cur) cur.classList.remove('active');
		if (activeHref) {
			var t = document.querySelector('.nav-menu a[href="' + activeHref.replace(/"/g, '\\"') + '"]');
			if (t) t.classList.add('active');
		}
	}
	/* 抽屉菜单项高亮同步（抽屉在主区外，pjax 后不替换，需手动跟随） */
	function syncDrawer(drawerActive) {
		var links = document.querySelectorAll('.drawer a.d-link');
		[].forEach.call(links, function (l) { l.classList.remove('on'); });
		if (drawerActive) {
			var t = document.querySelector('.drawer a.d-link[href="' + drawerActive.replace(/"/g, '\\"') + '"]');
			if (t) t.classList.add('on');
		}
	}

	function fetchPage(url) {
		if (cache.has(url)) {
			var hit = cache.get(url);
			cache.delete(url); cache.set(url, hit); // LRU touch
			return Promise.resolve(hit.doc);
		}
		if (controller) controller.abort();
		controller = new AbortController();
		/* M2：慢网超时兜底 —— 8s 拿不到就 abort，走 location.href 整页跳转，弱网用户不卡死 */
		var timer = setTimeout(function () { controller.abort(); }, 8000);
		return fetch(url, {
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'oneyer-pjax' },
			signal: controller.signal
		}).then(function (res) {
			/* 404 允许走 pjax 渲染 404 页（模板有统一主区）；其余非 2xx 兜底真实跳转 */
			if (!res.ok && res.status !== 404) throw new Error('http ' + res.status);
			return res.text();
		}).then(function (html) {
			clearTimeout(timer);
			var doc = parseDoc(html);
			if (!doc) throw new Error('no main');
			cache.set(url, { doc: doc });
			if (cache.size > CACHE_MAX) cache.delete(cache.keys().next().value);
			return doc;
		}).catch(function (err) {
			clearTimeout(timer);
			throw err;
		});
	}

	/* ── 转场执行 ─────────────────────────── */
	function transition(url, doc, push) {
		var main = document.getElementById(MAIN_ID);
		var scrollTop = window.pageYOffset;
		var hadAdminBar = document.body.classList.contains('admin-bar');

		if (push) {
			/* 无条件记录离开位置（首个条目 history.state 为 null 也要记，否则回退丢滚动） */
			history.replaceState({ y: scrollTop }, '', location.href);
			history.pushState({ y: 0 }, '', url);
		}

		var doSwap = function () {
			syncNav(doc.activeHref);
			syncDrawer(doc.drawerActive);
			/* 缓存 LRU 会复用同一节点：清掉上次转场可能残留的状态 */
			doc.main.classList.remove('pjax-leaving', 'pjax-entering');
			doc.main.style.pointerEvents = '';
			main.replaceWith(doc.main);
			document.title = doc.title;
			document.body.setAttribute('class', doc.bodyClass);
			if (hadAdminBar) document.body.classList.add('admin-bar'); /* 登录态管理条类名不丢 */
			doc.main.classList.add('pjax-entering');
			requestAnimationFrame(function () {
				requestAnimationFrame(function () {
					doc.main.classList.remove('pjax-entering');
				});
			});
			/* 滚动：目标带 # 锚则滚锚，否则回顶（popstate 时恢复记录位置）。
			 * M5：一律 'instant' 强制瞬时 —— 'auto' 会遵循 CSS scroll-behavior:smooth，
			 * 在部分环境/iOS 橡皮筋下平滑滚动被吞，转场定位必须瞬时。 */
			var hash = url.indexOf('#') > -1 ? url.slice(url.indexOf('#') + 1) : '';
			if (hash) {
				var target = document.getElementById(hash);
				if (target) { target.scrollIntoView({ behavior: 'instant' }); }
			} else {
				var oy = push ? 0 : ((history.state && history.state.y) || 0);
				window.scrollTo({ top: oy, behavior: 'instant' });
			}
			/* 重新初始化主题组件（main.js 提供） */
			if (window.ONEYER_UI && window.ONEYER_UI.init) window.ONEYER_UI.init(doc.main);
			/* 无障碍：转场后把焦点移入新主区（不引起滚动） */
			doc.main.setAttribute('tabindex', '-1');
			doc.main.focus({ preventScroll: true });
			/* 埋点钩子（无则安静跳过） */
			if (window.oneyerPjaxComplete) window.oneyerPjaxComplete(url);
		};

		/* 转场退出（移动端防连点：pointer-events 在换出前屏蔽主区） */
		main.classList.add('pjax-leaving');
		main.style.pointerEvents = 'none';
		setTimeout(doSwap, OUT_MS);
	}

	/* ── 入口：导航 ───────────────────────── */
	function navigate(url, push) {
		if (pendingUrl === url) return;
		pendingUrl = url;
		barStart();
		fetchPage(url).then(function (doc) {
			barEnd();
			transition(url, doc, push);
		}).catch(function (err) {
			if (err && err.name === 'AbortError') return; // 被新请求取消，正常
			barFail();
			location.href = url; // 兜底：真实跳转
		}).then(function () { pendingUrl = null; });
	}

	/* ── 事件绑定 ─────────────────────────── */
	document.addEventListener('click', function (ev) {
		var a = ev.target.closest ? ev.target.closest('a[href]') : null;
		if (!shouldIntercept(a, ev)) return;
		ev.preventDefault();
		/* M4：抽屉内点击 → 先关抽屉再转场（ONEYER_UI.closeDrawer 由 main.js 暴露） */
		if (a.closest('.drawer') && window.ONEYER_UI && window.ONEYER_UI.closeDrawer) {
			window.ONEYER_UI.closeDrawer();
		}
		navigate(a.href, true);
	});

	/* M1：tap 即预取 —— touchstart 比 click 早 50~100ms，移动端收益核心；
	   M6：Save-Data 开启时跳过（省流模式不偷流量） */
	var saveData = navigator.connection && navigator.connection.saveData;
	document.addEventListener('touchstart', function (ev) {
		if (saveData) return;
		var a = ev.target.closest ? ev.target.closest('a[href]') : null;
		if (!a || !shouldIntercept(a, null)) return;
		if (!cache.has(a.href)) fetchPage(a.href).catch(function () {});
	}, { passive: true });

	/* 搜索表单（导航/抽屉/搜索页大框/404 框统一按 role=search 拦截）→ pjax */
	document.addEventListener('submit', function (ev) {
		var form = ev.target.closest ? ev.target.closest('form[role="search"]') : null;
		if (!form || !window.fetch || ev.defaultPrevented) return;
		var input = form.querySelector('input[name="s"]');
		if (!input) return;
		ev.preventDefault();
		if (form.closest('.drawer') && window.ONEYER_UI && window.ONEYER_UI.closeDrawer) {
			window.ONEYER_UI.closeDrawer();
		}
		var url = form.getAttribute('action') || location.origin + '/';
		url += (url.indexOf('?') > -1 ? '&' : '?') + 's=' + encodeURIComponent(input.value.trim());
		navigate(url, true);
	}, true);

	/* 后退/前进（含 iOS 边缘右滑 / Android 返回键系统手势） */
	history.scrollRestoration = 'manual';
	window.addEventListener('popstate', function () {
		navigate(location.href, false);
	});

	/* 桌面 hover 预取（(hover:none) 时自动失效，移动端走 touchstart 分支） */
	var hoverTimer = null;
	document.addEventListener('mouseover', function (ev) {
		if (window.matchMedia('(hover: none)').matches || saveData) return;
		var a = ev.target.closest ? ev.target.closest('a[href]') : null;
		if (!a || !shouldIntercept(a, null)) return;
		var url = a.href;
		if (cache.has(url)) return;
		hoverTimer = setTimeout(function () { fetchPage(url).catch(function () {}); }, 150);
	});
	document.addEventListener('mouseout', function () { clearTimeout(hoverTimer); });
})();
