/**
 * ONEYER · 首页分类胶囊 AJAX 预览（局部切换，URL 不变）
 *
 * - 依赖：window.ONEYER（ajax/nonce）；与 pjax 互斥靠 chips 的 .no-pjax 逃生舱。
 * - 全部态 ↔ 分类态切换用 DOM 显隐（.oy-hidden），原网格/#loadWrap 不重建，
 *   「加载更多」闭包里的页码状态无损（红线 2）。
 * - 可重入：pjax 转场回首页后由 main.js mountAll 调 ONEYER_HOMECAT.mount(ctx) 重挂，
 *   以 chips 节点上的 data 标记防重复绑定。
 */
(function () {
	'use strict';

	window.ONEYER_HOMECAT = {
		mount: function (ctx) {
			ctx = ctx || document;
			var chipsWrap = document.getElementById('chips');
			if (!chipsWrap || !window.ONEYER) return;
			if (chipsWrap.getAttribute('data-oy-homecat-bound') === '1') return; // 防重入
			chipsWrap.setAttribute('data-oy-homecat-bound', '1');

			var grid = document.getElementById('grid');
			var loadWrap = document.getElementById('loadWrap');
			var pager = document.getElementById('homePager');
			var featured = document.getElementById('oyFeatured');
			var title = document.getElementById('gridTitle');
			var sub = document.getElementById('gridSub');
			var loading = false, controller = null, filterBox = null;

			var HIDE = 'oy-hidden';

			function hideAll() {
				if (featured) featured.classList.add(HIDE);
				if (grid) grid.classList.add(HIDE);
				if (loadWrap) loadWrap.classList.add(HIDE);
				if (pager) pager.classList.add(HIDE);
			}
			function showAll() {
				if (featured) featured.classList.remove(HIDE);
				if (grid) grid.classList.remove(HIDE);
				if (loadWrap) loadWrap.classList.remove(HIDE);
				if (pager) pager.classList.remove(HIDE);
			}

			function removeFilter() {
				if (filterBox && filterBox.parentNode) filterBox.parentNode.removeChild(filterBox);
				filterBox = null;
			}

			function restoreAll() {
				removeFilter();
				showAll();
				if (title) title.textContent = '最新文章';
				if (sub) sub.textContent = 'Latest Posts';
			}

			function renderFilter(data) {
				removeFilter();
				filterBox = document.createElement('div');
				filterBox.id = 'grid-filter';
				var inner = document.createElement('div');
				inner.className = 'grid';
				inner.innerHTML = data.html;
				[].forEach.call(inner.querySelectorAll('article.card'), function (c, i) {
					c.style.animation = 'rise .55s both';
					c.style.animationDelay = (i % 3) * 0.05 + 's'; // 同首页入场节奏
				});
				filterBox.appendChild(inner);

				var foot = document.createElement('div');
				foot.className = 'load-wrap';
				if (data.found > data.per_page) {
					/* 普通 <a>：pjax 拦截 = 无刷新进分类页；未装 pjax = 整页跳转（红线 5） */
					foot.innerHTML =
						'<a class="loadmore" href="' + data.cat_url + '">进入分类页' +
						'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m9 6 6 6-6 6"/></svg></a>' +
						'<span class="load-hint">已展示 ' + data.per_page + ' / ' + data.found + ' 篇 · 更多内容进分类页</span>';
				} else if (data.found > 0) {
					foot.innerHTML = '<span class="load-hint">已展示全部 ' + data.found + ' 篇</span>';
				} else {
					foot.innerHTML = '<span class="load-hint">该分类还没有文章</span>';
				}
				filterBox.appendChild(foot);

				var anchor = grid || loadWrap;
				if (anchor && anchor.parentNode) anchor.parentNode.insertBefore(filterBox, anchor.nextSibling);
				if (title) title.textContent = data.name;
				if (sub) sub.textContent = 'Category · 前 ' + data.per_page + ' 篇预览';
			}

			function setOn(chip) {
				[].forEach.call(chipsWrap.querySelectorAll('.chip'), function (c) { c.classList.remove('on'); });
				if (chip) chip.classList.add('on');
			}

			chipsWrap.addEventListener('click', function (ev) {
				var chip = ev.target.closest('.chip');
				if (!chip || chip.classList.contains('on')) return;
				ev.preventDefault();
				var cat = parseInt(chip.getAttribute('data-cat'), 10) || 0;

				/* 全部：DOM 显隐恢复，零请求零重绑（loadmore 页码状态无损） */
				if (!cat) { setOn(chip); restoreAll(); return; }

				if (loading && controller) controller.abort(); // 连点竞态：取消前一请求
				loading = true;
				setOn(chip);
				if (filterBox) filterBox.classList.add('is-loading');
				controller = new AbortController();
				var timer = setTimeout(function () { controller.abort(); }, 8000); // 慢网兜底

				var body = new URLSearchParams();
				body.append('action', 'oneyer_homecat');
				body.append('nonce', window.ONEYER.nonce);
				body.append('cat', String(cat));

				fetch(window.ONEYER.ajax, {
					method: 'POST', credentials: 'same-origin',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
					body: body.toString(), signal: controller.signal
				}).then(function (r) { return r.json(); }).then(function (json) {
					clearTimeout(timer); loading = false;
					if (!json || !json.success) throw new Error('bad');
					hideAll();
					renderFilter(json.data);
				}).catch(function (err) {
					clearTimeout(timer); loading = false;
					if (err && err.name === 'AbortError') return;
					/* 失败弹回全部态（含胶囊 .on 弹回） */
					restoreAll();
					setOn(chipsWrap.querySelector('.chip[data-cat="0"]'));
				});
			});
		}
	};

	/* 首屏自挂（main.js 的 mountAll 先于本文件执行，pjax 后续转场由其回调本 mount） */
	if (document.getElementById('chips')) window.ONEYER_HOMECAT.mount(document);
})();
