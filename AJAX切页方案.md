# Oneyer 主题 · AJAX 丝滑切页方案（Pjax 渐进增强导航）

> 需求：AJAX 切页落地后，**桌面端与移动端都要真实拿到丝滑收益**（移动端是主要使用端，收益优先级更高）——首页/分类页切换分类必须 AJAX，其余可切页位置自行排查补齐
> 实施对象：`E:\桌面\项目总览\WP主题\oneyer\oneyer-theme`（云端最新版 dev.oneyer.cc）
> 性质：coding agent 可直接执行。核心 JS 整段复制即用，接线处全部带 file:line 锚点
> 配套：`WP模板映射文档.md` / `后台设置页方案.md` / `站点Logo方案.md`

---

## 〇、双端收益分析（为什么这套方案双端都赚）

同一套 Pjax 内核，桌面/移动的收益点不同，各自明确：

**移动端收益（主战场）**：
1. **消灭白屏长等待**：整页刷新 = 重新下载 CSS/JS/字体 + 重排重绘，4G 下典型 1.5~3s 白屏；pjax 只拉一个 HTML（gzip 后 20~40KB），转场 400ms 内完成。
2. **消灭导航/抽屉闪烁**：整页刷新时毛玻璃导航、汉堡菜单、滚动位置全部重置；pjax 后导航原地不动，抽屉里的点击也能无刷新切页（见 §二-A 移动端专项）。
3. **系统返回手势直接可用**：iOS 边缘右滑 / Android 返回键触发 popstate → 走缓存直出（0ms）或静默 fetch，**返回不再整页重载**——这是移动端体验质变最大的点。
4. **省流量**：LRU 缓存 + tap 预取（见 §二-A），来回翻阅同批页面不重复下载。

**桌面端收益**：
1. 切页不闪不重载，阅读流不断（导航 hover 态、搜索框输入、Ctrl K 焦点都保留）。
2. hover 预取（悬停 150ms 静默拉取）让点击时**响应近 0ms**。
3. 后退/前进恢复滚动位置，长列表来回对比文章不丢位置。

> 结论：**一套代码、两个场景各自受益**；所有移动端专项增强（§二-A）都是纯前端行为，不影响桌面，也不新增服务端改动。

---

## 〇-B、大白话总览

现在点击任何链接（导航、分类胶囊、卡片、分页、上下篇）都是**整页刷新**：白屏一下、导航重载、移动端尤其明显。要「丝滑」，业内成熟做法叫 **Pjax**（pushState + ajax）：

1. **拦截点击**：用户点站内链接时不真跳转，改成 `fetch()` 悄悄下载目标页面 HTML。
2. **就地换内容**：拿回来的 HTML 解析出正文区，替换掉当前页正文区——导航、页脚原地不动，所以视觉上只有内容在换。
3. **改地址栏**：`history.pushState()` 把 URL 换成目标页——刷新、后退、分享链接都和真实跳转一模一样。
4. **转场动画**：旧内容 150ms 淡出 → 替换 → 新内容 250ms 淡入 + 滚动复位，看起来像 App 切屏。

**关键技术决策——服务端零接口**：WP 每个页面本来就是完整 HTML，fetch 整页 → DOMParser 解析 → 摘出主区替换即可。**不需要为每个页面写 admin-ajax 局部渲染接口**（首页分类切换也不需要：直接 fetch `/category/xxx/` 整页摘取 grid）。这带来三个好处：改动量最小、所有页面类型天然支持、无 JS 时原样可用（渐进增强不动摇）。服务端唯一要做的是给主内容区统一一个 id。

**现状已核实**（与方案直接相关）：
- 主题已有 AJAX 基建：`ONEYER.ajax + ONEYER.nonce`（functions.php:82-83），loadmore 已走 admin-ajax（functions.php:821）
- main.js 是 IIFE 一次性绑定（357 行，头部注释明确「渐进增强、禁 JS 完全可用」）——**替换 DOM 后所有组件绑定会失效，这是本方案最大的坑，T4 卡专门解决**
- 可切页点全站排查结果见 §一

---

## 一、可切页点排查清单（双端）

| # | 位置 | 现状 | file:line | 处理 |
|---|---|---|---|---|
| 1 | **首页分类胶囊 chips** | 普通 `<a>` 整页跳转 | `index.php:59-67` | ★必须 AJAX（pjax 拦截） |
| 2 | **分类页子分类 chips** | 普通 `<a>` 整页跳转 | `category.php`（chips 区，~40-45 行） | pjax 拦截 |
| 3 | 分类页/搜索页/归档分页器 | `paginate_links` 链接 | `functions.php:492` `oneyer_pagination()` | pjax 拦截 |
| 4 | 导航菜单（桌面+抽屉） | 普通链接 | `header.php` | pjax 拦截（含 active 态同步） |
| 5 | 文章卡片（首页/分类/相关阅读） | 普通链接 | `template-parts/post-card.php` | pjax 拦截 |
| 6 | 热门榜/标签云/友链卡 | 普通链接 | `index.php:189+`（mix 区） | pjax 拦截 |
| 7 | 详情页上/下篇 | 普通链接 | `single.php:103,113` | pjax 拦截 |
| 8 | 页脚栏目/站点链接 | 普通链接 | `footer.php` | pjax 拦截 |
| 9 | **搜索表单提交** | GET 整页跳转 | `searchform.php` | T5 卡 AJAX 化 |
| 10 | 首页「加载更多」 | 已是 AJAX | `functions.php:821` + main.js | **不动**（已丝滑），但 pjax 后需重绑按钮（T4） |
| 11 | 评论提交 | 常规 POST 整页刷新 | `comments.php` | T6 卡（可选增强） |
| 12 | 归档时间线年份锚点 | 页内 `#` 跳转 | `page-archive-timeline.php` | **不拦截**（本来就是页内行为） |

**拦截规则（唯一事实源，写在 pjax.js 顶部）**：

```
拦截：同源 <a href> 且 target 无 _blank、无 download、非 # 纯锚、
     非 wp-admin / wp-login、非 .no-pjax（逃生舱类名）、左键单击且无修饰键
放行：外部链接、图片/文件直链（.jpg/.png/.webp/.zip/.pdf 等后缀）、#comment-xxx 评论锚
```

---

## 二、交互规格（转场时序 —— 双端一致）

```
用户点击链接（t=0）
 │
 ├─ 1. 立即：显示顶部加载细条 .pjax-bar（0→80% 动画，宽 2px，蓝色渐变同 --blue）
 ├─ 2. 同时：fetch(目标URL, {credentials:'same-origin'})
 │
 ├─ 成功（典型 200~600ms）：
 │    a. 旧主区加 .pjax-out（opacity 0，150ms 过渡）
 │    b. DOMParser 解析响应 → 摘取 #oneyer-main / <title> / body@class / nav active
 │    c. 等淡出完成 → 替换主区 HTML + document.title + body class + nav active 同步
 │    d. pushState(目标URL)；记录当前滚动位置进上一条 state
 │    e. 新主区 .pjax-in（opacity 0→1，250ms）+ 滚动复位到顶（或目标含 # 锚则滚到锚）
 │    f. .pjax-bar 冲到 100% 后 200ms 淡出
 │    g. 重新初始化 JS 组件（T4）+ 重发 analytics（若有）
 │
 ├─ 失败（网络错/非 200/解析不出主区）：
 │    直接 location.href = 目标URL（整页跳转兜底，用户无感知降级）
 │
 └─ 后退/前进（popstate）：
      同上 fetch 流程，但 URL 已由浏览器切换好（不再 pushState），
      并从 state 恢复滚动位置（history.scrollRestoration='manual'）
```

细节规则：
- **移动端**：点中即给主区加 `pointer-events:none`（防双击连点导致的竞态）；转场时长同桌面（150+250ms 手感已够快）。
- **竞态**：新请求发起时若上一个未完成，取消上一个（AbortController）。
- **缓存**：Map 缓存最近 5 个页面响应（LRU），后退到刚看过的页面 0ms 直出——移动端回退体验质变。
- **prefers-reduced-motion**：动画时长归零，直接替换（main.js 已有该常量，pjax.js 读取同值）。

### 二-A · 移动端专项增强（落地后移动端受益的保障条款，全部并入 pjax.js）

| # | 增强 | 规格 | 双端影响 |
|---|---|---|---|
| M1 | **tap 即预取** | `touchstart` 触发时立即静默 fetch（比 click 早 50~100ms，覆盖移动端触摸延迟窗口）；桌面 hover 预取逻辑对 `(hover:none)` 媒体自动关闭，两端互不干扰 | 移动专享 |
| M2 | **慢网超时兜底** | fetch 加 8s AbortController 超时；超时/失败 → `location.href` 整页跳转。弱网（460ms TTFB）用户仍能到达目标页，不会卡死在 loading 条 | 双端，移动收益大 |
| M3 | **系统返回手势 = pjax** | iOS 边缘右滑 / Android 返回键天然触发 popstate，方案已接管（缓存命中 0ms 直出）；**必须真机验证此路径**（模拟器/桌面 DevTools 触发不了手势） | 移动专享 |
| M4 | **抽屉菜单联动** | 抽屉（`.drawer`）内点链接走 pjax 后：① 关闭抽屉（复用 main.js `closeDrawer`）② 再执行转场。实施：pjax.js 在 navigate 前检测 `a.closest('.drawer')`，命中则先调 `window.ONEYER_UI.closeDrawer?.()` | 移动专享 |
| M5 | **滚动恢复防 iOS 弹性** | `scrollRestoration='manual'` + popstate 恢复 `state.y` 时用 `behavior:'auto'`（不用 smooth，避开 iOS 橡皮筋抖动） | 移动收益大 |
| M6 | **流量友好** | LRU 缓存上限 5（约 150~250KB HTML）；`Save-Data` 请求头开启时关闭 hover/tap 预取（省流模式不偷跑流量） | 移动收益大 |
| M7 | **首屏不回归** | pjax.js 挂 footer（`true`），不阻塞首屏 LCP；转场期间骨架不新增（避免移动端布局跳动） | 双端 |

> M4 需要main.js 暴露 `closeDrawer`（T4 改造时一并导出到 `ONEYER_UI`，改动一行）。

---

## 三、改动总览（文件清单）

| 文件 | 动作 | 卡 |
|---|---|---|
| `assets/js/pjax.js` | **新建**（全量代码见四-A） | T2 |
| `assets/js/main.js` | 重构初始化入口为可重入 `ONEYER_UI.init(ctx)`；末尾去掉自执行改为导出 | T4 |
| `functions.php` | ①注册 pjax.js（约 :70-89 资源段）②加载顺序：pjax.js 依赖 main.js | T3 |
| `index.php` / `category.php` / `search.php` / `archive.php` / `single.php` / `page.php` / `page-templates/*.php` | `<main>` 统一加 `id="oneyer-main"` | T1 |
| `header.php` | `.pjax-bar` 元素（1 行）+ nav active 标记规范化 | T1 |
| `assets/css/main.css` | 转场动画 + pjax-bar 样式（~30 行） | T3 |
| `searchform.php` | 不改文件，由 pjax.js 拦截 submit | T5 |

---

## 四、核心代码

### 四-A · `assets/js/pjax.js`（新建，全量）

```javascript
/**
 * ONEYER · Pjax 渐进增强导航
 * 原则：任何失败一律回退真实跳转；无 JS 时全站原生可用（不注册即无行为）。
 */
(function () {
	'use strict';

	if (!window.fetch || !window.history || !window.DOMParser) return; // 老浏览器直接不启用

	var MAIN_ID = 'oneyer-main';
	var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var OUT_MS = reduced ? 0 : 150;
	var IN_MS  = reduced ? 0 : 250;

	var FILE_RE = /\.(jpe?g|png|gif|webp|avif|svg|ico|zip|rar|7z|tar|gz|pdf|mp4|mp3|txt|xml)(\?|#|$)/i;
	var cache = new Map();          // LRU：url → {main, title, bodyClass, navHtml}
	var CACHE_MAX = 5;
	var controller = null;          // 竞态取消
	var pendingUrl = null;

	/* ── 工具 ─────────────────────────────── */
	function sameOrigin(url) {
		try { return new URL(url, location.href).origin === location.origin; }
		catch (e) { return false; }
	}
	function shouldIntercept(el, ev) {
		if (ev.defaultPrevented || ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) return false;
		if (!el || el.target === '_blank' || el.hasAttribute('download')) return false;
		var href = el.getAttribute('href');
		if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) return false;
		if (el.closest('.no-pjax')) return false;
		if (/\/wp-(admin|login)\.php/i.test(href)) return false;
		if (FILE_RE.test(href)) return false;
		return sameOrigin(href);
	}

	/* ── 加载条 ───────────────────────────── */
	var bar = document.querySelector('.pjax-bar');
	function barStart() {
		if (!bar) return;
		bar.classList.add('is-loading');
		bar.style.width = '80%';
	}
	function barEnd() {
		if (!bar) return;
		bar.style.width = '100%';
		setTimeout(function () { bar.classList.remove('is-loading'); bar.style.width = '0'; }, 200);
	}
	function barFail() {
		if (!bar) return;
		bar.classList.remove('is-loading');
		bar.style.width = '0';
	}

	/* ── 抓取与解析 ───────────────────────── */
	function parseDoc(html) {
		var doc = new DOMParser().parseFromString(html, 'text/html');
		var main = doc.getElementById(MAIN_ID);
		if (!main) return null; // 目标页没有主区（异常模板）→ 触发兜底
		return {
			main: main,
			title: doc.title || '',
			bodyClass: doc.body.getAttribute('class') || '',
			navActiveHref: null // 由下方 resolveNav 从新文档导航计算
		};
	}
	/* 从新文档解析导航 active 态：取 .nav-menu .active 的 href 与当前对比 */
	function syncNavState(newDoc) {
		var newActive = newDoc.querySelector('.nav-menu > li > a.active, .nav-menu > .current-menu-item > a');
		var curActive = document.querySelector('.nav-menu > li > a.active, .nav-menu > .current-menu-item > a');
		if (curActive) curActive.classList.remove('active');
		if (newActive) {
			var href = newActive.getAttribute('href');
			var target = document.querySelector('.nav-menu a[href="' + (href || '').replace(/"/g, '\\"') + '"]');
			if (target) target.classList.add('active');
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
			if (!res.ok) throw new Error('http ' + res.status);
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

		if (push && history.state) history.replaceState({ y: scrollTop }, '', location.href);
		if (push) history.pushState({ y: 0 }, '', url);

		var doSwap = function () {
			// 记录新文档导航 active（在替换前用旧 DOM 树的菜单同步）
			syncNavState(doc.main.ownerDocument || doc.main);
			main.replaceWith(doc.main);
			document.title = doc.title;
			document.body.setAttribute('class', doc.bodyClass);
			// 转场进入
			doc.main.classList.add('pjax-entering');
			requestAnimationFrame(function () {
				requestAnimationFrame(function () {
					doc.main.classList.remove('pjax-entering');
				});
			});
			// 滚动：目标带 # 锚则滚锚，否则回顶
			var hash = url.indexOf('#') > -1 ? url.slice(url.indexOf('#') + 1) : '';
			if (hash) {
				var target = document.getElementById(hash);
				if (target) { target.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth' }); }
			} else {
				window.scrollTo(0, push ? 0 : (history.state && history.state.y) || 0);
			}
			// 重初始化主题组件（main.js 提供）
			if (window.ONEYER_UI && window.ONEYER_UI.init) window.ONEYER_UI.init(doc.main);
			// 通知埋点（无则安静跳过）
			if (window.oneyerPjaxComplete) window.oneyerPjaxComplete(url);
		};

		// 转场退出
		main.classList.add('pjax-leaving');
		main.style.pointerEvents = 'none';
		setTimeout(function () { doSwap(); }, OUT_MS);
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
		/* M4：抽屉内点击 → 先关抽屉再转场（ONEYER_UI.closeDrawer 由 T4 暴露） */
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
		if (!a || !shouldIntercept(a, { button: 0 })) return;
		if (!cache.has(a.href)) fetchPage(a.href).catch(function () {});
	}, { passive: true });

	/* 搜索表单：拦 submit → 构造搜索 URL → pjax */
	document.addEventListener('submit', function (ev) {
		var form = ev.target.closest ? ev.target.closest('form.oneyer-searchform') : null;
		if (!form || !window.fetch) return;
		var input = form.querySelector('input[name="s"]');
		if (!input) return;
		ev.preventDefault();
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
		if (!a || !shouldIntercept(a, { button: 0 })) return;
		var url = a.href;
		if (cache.has(url)) return;
		hoverTimer = setTimeout(function () { fetchPage(url).catch(function () {}); }, 150);
	});
	document.addEventListener('mouseout', function () { clearTimeout(hoverTimer); });
})();
```

> `transition()` 中 `doc.main.ownerDocument`：从缓存直出的节点来自旧文档对象，`replaceWith` 跨文档节点在主流浏览器均支持（adopt 语义）；若实测目标浏览器异常，改为 `document.importNode(doc.main, true)`（实施时二选一，验收点见 §六-3）。

### 四-B · main.js 可重入改造（T4 核心）

- 锚点：`assets/js/main.js:9` IIFE 起始 `(function () {` 与文件末尾 `})();`
- 指令：
  1. IIFE 内所有组件初始化收敛为一个函数 `function mountAll(ctx)`（ctx 默认 document）——把现有对 `document` 的查询全部改为 `(ctx || document)` 参数化（main.js:10 已有 `$/$$` helper，天然支持第二参传 ctx，**改造是机械替换**）；
  2. 一次性事件（全局 click/submit/popstate 监听）保留在 mountAll 外——只绑一次；
  3. DOM 相关组件（进度条/backtop 可保留全局；**reveal 渐入、数字滚动、chips 筛选、scroll-spy、loadmore 按钮、代码复制、技能条**必须进 mountAll）；
  4. 文件末尾改为暴露：

```javascript
window.ONEYER_UI = {
	init: function (ctx) { mountAll(ctx || document); },
	closeDrawer: (typeof closeDrawer === 'function') ? closeDrawer : null /* M4：抽屉联动 */
};
if (document.documentElement.classList.contains('js')) window.ONEYER_UI.init(document);
```

（原 `wp_add_inline_script` 加 `js` 类在脚本前执行，首屏照常初始化。）

### 四-C · CSS 追加（main.css 末尾）

```css
/* ── Pjax 转场 ── */
#oneyer-main{transition:opacity 150ms ease}
#oneyer-main.pjax-leaving{opacity:0}
#oneyer-main.pjax-entering{opacity:0;transition:opacity 250ms ease}
.pjax-bar{position:fixed;top:0;left:0;height:2.5px;width:0;z-index:400;
  background:linear-gradient(90deg,#409eff,#6ec6ff);box-shadow:0 0 10px rgba(64,158,255,.6);
  transition:width .3s ease,opacity .2s ease;opacity:0;pointer-events:none}
.pjax-bar.is-loading{opacity:1;transition:width .4s ease}
@media (prefers-reduced-motion: reduce){
  #oneyer-main{transition:none}
}
/* html.js 之外的降级：无 JS 时不渲染任何 pjax 元素 */
html:not(.js) .pjax-bar{display:none}
```

---

## 五、任务卡（T1→T6 顺序执行）

### T1 · 主区容器统一 + 加载条元素
- **1a** `<main>` 加 `id="oneyer-main"`：`index.php:36`（`<main class="wrap">`）、`category.php:38`、`search.php` / `archive.php` / `single.php` / `page.php` / `page-templates/page-about.php` / `page-archive-timeline.php` / `page-links.php` / `404.php`（各文件 grep `<main` 一次到位）。**若某模板 main 在 grid 外还包着面包屑等**：id 放在能覆盖「每页变化区域」的最外层（含面包屑/横幅），导航和页脚必须留在 id 外。
- **1b** `header.php`：`<body>` 开标签后（progress 条旁）插 `<div class="pjax-bar" aria-hidden="true"></div>`。
- 验收：`grep -c 'id="oneyer-main"' *.php **/*.php` ≥ 10；浏览器任意页 `document.getElementById('oneyer-main')` 非空。

### T2 · pjax.js 落地
- 新建 `assets/js/pjax.js`（§四-A 全量）。
- **T3 一起做**：`functions.php:70-89` 资源段，在 oneyer-main 脚本后注册：

```php
wp_enqueue_script( 'oneyer-pjax', get_theme_file_uri( '/assets/js/pjax.js' ), array( 'oneyer-main' ), (string) filemtime( get_theme_file_path( 'assets/js/pjax.js' ) ), true );
```

（依赖 oneyer-main 保证 ONEYER_UI.init 存在。）

### T4 · main.js 可重入改造（最关键卡）
- 锚点与规则见 §四-B。**改造时禁止顺手重构样式逻辑**——只做「document → ctx 参数化 + 入口收敛」，每改完一个组件立刻在浏览器验证首屏行为不变。
- 验收：首屏全组件正常（抽屉/进度/计数/chips/loadmore/复制/spy）——**改造前后各截一张首屏图对比，像素级一致**。

### T5 · 搜索 AJAX 化
- 已由 pjax.js 的 submit 拦截完成（§四-A），`searchform.php` 零改动。无 JS 时表单原生 GET 提交可用（渐进增强保持）。
- 验收：导航搜索框输入「Vite」回车 → URL 变 `/?s=Vite` 不刷新、结果页淡入、无结果态正常。

### T6 · 评论提交 AJAX 化（可选增强，独立上线）
- 思路：`comment_form` 的 submit 由 JS 拦截 → `fetch(ONEYER.ajax, {action:'oneyer_comment', nonce, …})` → 服务端 `wp_handle_comment_submission()`（或 `wp_new_comment()`）→ 返回渲染好的单条 `.cmt` HTML → 前端插到评论列表顶部 + 计数 +1 + 成功提示。
- 服务端新增：`wp_ajax(_nopriv)_oneyer_comment` 回调（复用 `oneyer_comment()` 输出结构，`functions.php:863`）；nonce 用现有 `ONEYER.nonce`。
- 红线：审核中的评论（`comment_approved=0`）返回特殊提示「已提交，等待审核」而非直接插入列表。
- **此卡可延后单独执行**，与前五卡无依赖。

---

## 六、验收清单（dev.oneyer.cc）

> **验收环境要求**：桌面（1440px Chrome）+ **移动真机**（iOS Safari + Android Chrome 各一台，至少各一台——系统返回手势、touchstart 预取、抽屉联动只能真机验证，DevTools 模拟不了）。DevTools 430px 可作为补充但不可替代真机。

**移动端专项（真机必测，对应 §二-A M1~M7）**
- [ ] **M3 系统返回手势**：iOS 边缘右滑 / Android 返回键 → 无整页重载；缓存命中页 0ms 直出
- [ ] **M4 抽屉联动**：汉堡菜单展开 → 点菜单项 → 抽屉自动关闭 + 内容转场，无残影
- [ ] **M1 tap 预取体感**：4G 下点卡片到转场完成 < 600ms（无预取时对比明显更慢）
- [ ] **M5 滚动恢复**：详情页滚到中部 → 返回手势 → 首页滚动位置精确恢复，无 iOS 弹性抖动
- [ ] **M6 省流模式**：系统/浏览器开启 Data Saver → Network 面板确认无预取请求
- [ ] 移动端连续快速点击两个不同链接 → 最终页面与 URL 一致（竞态）
- [ ] 移动端弱网模拟（DevTools Slow 4G 真机可跳过）→ 加载条出现，8s 内完成或整页跳转兜底，无卡死

**核心路径（用户点名，双端各过）**
- [ ] 首页点「WordPress」胶囊 → 无刷新，内容淡出淡入，URL 变为分类页，滚动到顶
- [ ] 分类页点子分类胶囊 → 同上；点「全部」回到父分类
- [ ] 分类页第 2 页分页 → 无刷新，分页器 active 态正确

**全站链接走查（双端各过）**
- [ ] 导航六个菜单项切换：无刷新、**active 蓝底态跟随切换**、页脚不闪
- [ ] 首页卡片 → 详情页 → 上下篇 → 相关阅读 → 返回首页，全程无白屏
- [ ] 搜索提交无刷新；无结果态正常
- [ ] 归档/关于/友链页进出正常（页面模板主区已统一 id）
- [ ] 404 页能以 pjax 方式进入（点一个坏链接）

**浏览器历史（双端各过）**
- [ ] 后退：内容回切 + **滚动位置恢复**（先滚到页面中部再后退）
- [ ] 前进同上；后退到刚访问过的页 **0ms 直出**（缓存命中）
- [ ] pjax 后刷新（桌面 F5 / 移动下拉刷新）：URL 正确、页面正常渲染

**边界与降级**
- [ ] 断网（DevTools offline / 真机飞行模式）点链接 → 自动整页跳转或错误页，无 JS 报错死循环
- [ ] Ctrl+点击 / 中键新开标签 → 不拦截，正常新开（桌面）
- [ ] 外部链接（友链站点）→ 不拦截
- [ ] `wp-admin` 链接（回复评论后台入口）→ 不拦截
- [ ] 带 `#comments` 锚的链接 → pjax 后滚到评论区
- [ ] prefers-reduced-motion 系统设置开启 → 无动画直接切换
- [ ] 无 JS（禁 JS 刷新）→ 全站普通跳转完全可用（渐进增强未破坏）

**转场后组件复活（T4 专验，双端各过）**
- [ ] pjax 进首页：数字滚动、chips 点击筛选、loadmore 追加、reveal 渐入全部正常
- [ ] pjax 进详情页：目录 scroll-spy 高亮、代码块复制按钮、返回顶部可用
- [ ] pjax 进关于页：技能条动画重新生长

**服务端零影响**
- [ ] 访问日志出现带 `X-Requested-With: oneyer-pjax` 的 GET（非页面渲染差异）
- [ ] `WP_DEBUG=true` 无 notice；Lighthouse 移动端性能分不降（pjax.js < 5KB 未压缩）

---

## 七、红线汇总

1. **渐进增强不可破坏**：禁 JS / 老浏览器 / fetch 失败，三条路全部回退原生跳转——pjax.js 顶部 `return` 与 `location.href` 兜底缺一不可。
2. `#oneyer-main` 必须包住「每页唯一变化区」且**排除导航与页脚**；所有 10 个模板统一（漏一个 = 该页 pjax 落空触发兜底整页跳，破坏一致性）。
3. main.js 改造**只做参数化收敛，不动行为逻辑**；改造前后首屏像素级一致是 T4 验收硬条件。
4. 拦截白名单里 `.no-pjax` 逃生舱类名必须保留（未来任何不想走 pjax 的链接加类即走原生跳转）。
5. history 状态必须带 `y` 滚动记录，`scrollRestoration='manual'`；后退丢滚动位置 = 验收不通过。
6. **移动端专项（M1~M7）随 T2/T4 一体交付，不得砍掉**：tap 预取、8s 超时兜底、抽屉联动、Save-Data 尊重是「双端都受益」的落地保障；验收必须在真机执行（模拟器替代不了返回手势/触摸预取）。
6. 竞态：连点两个链接时旧请求必须 abort，最终 DOM 与最终 URL 一致。
7. 不引入 jQuery/任何依赖；pjax.js 保持单文件 < 5KB 未压缩，随主题版本走 mtime 缓存破坏。
8. loadmore（functions.php:821）已有 AJAX **不重构不合并**——它返回局部卡片，与 pjax 整页摘取是两套场景，合并复杂度不值得。
