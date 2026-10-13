# Oneyer 主题 · 首页分类 AJAX 预览 + 分类页 AJAX 翻页方案

> 需求（已确认）：①首页点分类胶囊 → 无刷新就地展示该分类前 N 条（N=后台设置，全部/分类共用一个），自动隐藏置顶大卡，超 N 条时「进入分类页」；②分类页支持 AJAX 翻页
> 已拍板分叉：首页切换分类 **URL 不变**（纯前端状态，刷新回全部态）；「进入分类页」**跟随当前跳转机制**（pjax 已落地→无刷新进入）；条数 N **复用现有 `oneyer_home_ppp` 设置项**
> 实施对象：`E:\桌面\项目总览\WP主题\oneyer\oneyer-theme`（云端 dev.oneyer.cc）
> 性质：coding agent 可直接执行。锚点全部 file:line 实核，代码整段复制即用

---

## 〇、大白话总览

首页的分类胶囊（`全部 / AIGC图像生成 / …`）现在是普通链接，点了会被已落地的 pjax 整页换掉 `#oneyer-main`——整块都换（Hero、统计卡、混排区全消失再重渲染），这不是你要的。你要的是**更轻的局部切换**：点胶囊，只有文章网格那一块变，首页其余部分原地不动。

所以本方案做三件事：

1. **给胶囊加逃生舱**：chips 链接加 `no-pjax` 类——pjax 白名单已有这个逃生舱（pjax.js `shouldIntercept` 遇 `.no-pjax` 直接放行），让胶囊不再走整页切换，交给新模块接管。
2. **新写一个局部切换模块**：点胶囊 → 静默调新 AJAX 接口拿该分类前 N 条卡片 → 只替换网格区域。切回「全部」时**原 DOM 显隐恢复**（不重建），首页「加载更多」的翻页状态原样保留，零重绑。
3. **需求 2 是免费的**：pjax 已落地（functions.php:81 已注册 pjax.js、header.php:18 有加载条），分类页分页器是普通链接，天然被 pjax 拦截无刷新翻页——只需验收，不需新代码。

**置顶隐藏怎么实现**：分类态由 AJAX 接口返回的查询带 `ignore_sticky_posts=true` 且不含置顶位——但首页 DOM 上置顶大卡是独立块（index.php:79），所以切换时直接**隐藏该块**、切回全部时**显示**，CSS 一行 `display:none`，最简单可靠。

**"加载更多"两种形态**：
- 全部态（现状不动）：AJAX 追加下一页 → 到底变「已经到底啦」
- 分类态（新增）：接口返回 `found`；`found > N` → 按钮变成 `<a class="loadmore">进入分类页</a>`（pjax 自动拦截 = 无刷新进入）；`found ≤ N` → 不显示按钮，提示「已展示全部 N 篇」

---

## 一、现状锚点（全部实核）

| # | 位置 | 锚点 | 现状 |
|---|---|---|---|
| 1 | 分类胶囊条 | `index.php:59-68` | `<a class="chip">` 普通链接（全部→`/`，分类→`get_category_link()`），`.on` 态由 PHP 判断 |
| 2 | 置顶大卡 | `index.php:33,79` 起（`$oy_featured` 块） | 仅第一页/静态首页渲染；主查询 `post__not_in` 排除置顶 |
| 3 | 文章网格 | `index.php:151` `<div class="grid" id="grid">` | 主循环渲染 `post-card` |
| 4 | 加载更多区 | `index.php:161-168` `#loadWrap`（data-max/found/per-page）+ `#loadMore`（默认隐藏）+ `#loadHint` | main.js:287-345 AJAX 追加，闭包持 `current` 页码状态 |
| 5 | 区块标题 | `index.php:144-147` `.section-head`（"最新文章 / Latest Posts"） | 静态文案 |
| 6 | pjax | `functions.php:81` 注册 + `header.php:18` 加载条 + `pjax.js` | **已落地**；`shouldIntercept` 白名单含 `.no-pjax` 逃生舱 |
| 7 | main.js 可重入 | `assets/js/main.js:168` `mountAll(ctx)`、`:357` `ONEYER_UI` 暴露 | **已落地**（T4 已完成） |
| 8 | loadmore 接口 | `functions.php:821-859` `oneyer_loadmore_ajax` | 仿它写新接口；nonce 用 `ONEYER.nonce`（functions.php:82-83） |
| 9 | 每页条数设置 | `inc/admin-settings.php:47` `oneyer_home_ppp`（默认 9）；消费：`functions.php:101`（home 主查询） | 共用一个 N：含义扩展，改 desc 即可 |
| 10 | 分类页每页数 | `functions.php:102` category 固定写死 `9` | 顺带统一为读同一设置（见 T5，可选但强烈建议） |

---

## 二、交互规格（首页状态机）

```
首页（全部态，默认）
 │  chips：[全部·on] [AIGC图像生成 105] [AIGC文本写作 9] …
 │  置顶大卡：显示      网格：主循环 N 张      底部：加载更多(AJAX追加)
 │
 ├─ 点「AIGC图像生成」(data-cat=term_id)
 │    ① 胶囊 .on 切换（全部→目标），其余不变；URL 不变
 │    ② 网格区加 .is-loading（opacity .45，150ms）
 │    ③ fetch POST admin-ajax { action: oneyer_homecat, cat: term_id }
 │    ④ 成功：置顶大卡加 .oy-hidden；原 #grid/#loadWrap/#homePager 加 .oy-hidden；
 │       插入 #grid-filter（卡片逐张 rise 入场 + 底部行）
 │       section-head 标题 → 「AIGC图像生成」，sub → 「Category · 前 9 篇预览」
 │       底部行（found=105 > N=9）：
 │         [进入分类页]（<a>，pjax 拦截→无刷新进分类页）+ hint「已展示 9 / 105 篇」
 │       若 found ≤ N：无按钮，hint「已展示全部 N 篇」
 │    ⑤ 失败/超时(8s)：胶囊 .on 弹回「全部」，toast 提示「加载失败，请重试」
 │    ⑥ 竞态：连点另一胶囊 → AbortController 取消前一请求
 │
 ├─ 分类态点「全部」
 │    移除 #grid-filter；去掉各块 .oy-hidden（置顶/网格/加载更多/分页器原样回来，
 │    加载更多的 current 页码状态在闭包里、DOM 未动 → 状态无损）
 │    标题还原「最新文章 / Latest Posts」；胶囊 .on 回「全部」
 │
 ├─ 分类态刷新页面 → 回全部态（URL 从未变过，天然满足）
 └─ 分类态点「进入分类页」→ pjax 无刷新进入 /category/aigc-image/
      （后退手势回首页 = 全部态，符合"刷新回默认"语义）
```

**双端**：网格 CSS 已响应式（3→2→1 列），卡片数 N 任意不破版；设置项 desc 提示"桌面端建议 3 的倍数（3 列对齐）"。移动端连点防护与桌面一致（请求竞态 abort）。

---

## 三、任务卡

### T1 · 后端接口 `oneyer_homecat`（functions.php 文件末尾追加）

```php
/* -------------------------------------------------------------------------
 * 首页分类 AJAX 预览：返回某分类前 N 条卡片 HTML + 元信息。
 * N 与首页主循环共用 oneyer_home_ppp；分类态不含置顶。
 * ---------------------------------------------------------------------- */
function oneyer_homecat_ajax() {
	check_ajax_referer( 'oneyer', 'nonce' );

	$cat = isset( $_POST['cat'] ) ? absint( $_POST['cat'] ) : 0;
	if ( $cat < 1 || ! term_exists( (int) $cat, 'category' ) ) {
		wp_send_json_error( 'bad cat', 400 );
	}

	$term = get_term( $cat, 'category' );
	if ( ! $term || is_wp_error( $term ) ) {
		wp_send_json_error( 'bad term', 400 );
	}

	$n     = max( 1, (int) oneyer_opt( 'oneyer_home_ppp', '9' ) );
	$query = new WP_Query( array(
		'cat'                 => $cat,
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $n,
		'ignore_sticky_posts' => true,   // 分类态无置顶（需求：自动隐藏置顶）
		'no_found_rows'       => false,  // 需要 found 判断是否超 N 条
	) );

	ob_start();
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			get_template_part( 'template-parts/post-card' );
		}
		wp_reset_postdata();
	}
	$html = ob_get_clean();

	wp_send_json_success( array(
		'html'     => $html,
		'found'    => (int) $query->found_posts,
		'per_page' => $n,
		'name'     => $term->name,
		'cat_url'  => get_category_link( $term ),
	) );
}
add_action( 'wp_ajax_oneyer_homecat', 'oneyer_homecat_ajax' );
add_action( 'wp_ajax_nopriv_oneyer_homecat', 'oneyer_homecat_ajax' );
```

> 复用 `template-parts/post-card.php` 渲染 → 卡片视觉与首页/分类页**像素级一致**，无新增模板。

### T2 · index.php 改造（chips 标记 + 容器钩子）

**2a. 胶囊加 data 属性与逃生舱**（锚点 `index.php:61-70`，两处 `printf`/直接输出）：

```php
<!-- 全部胶囊 -->
<a class="chip on no-pjax" data-cat="0" href="<?php echo esc_url( home_url( '/' ) ); ?>"><span class="dot"></span>全部</a>
<!-- 分类胶囊（printf 里加 no-pjax 与 data-cat） -->
printf(
	'<a class="chip%1$s no-pjax" data-cat="%5$d" href="%2$s"><span class="dot"></span>%3$s<span class="cnt">%4$s</span></a>',
	$oy_current_cat === $oy_cc->term_id ? ' on' : '',
	esc_url( get_category_link( $oy_cc ) ),
	esc_html( $oy_cc->name ),
	esc_html( number_format_i18n( $oy_cc->count ) ),
	$oy_cc->term_id
);
```

> `no-pjax` 是**本方案关键**：不加它，pjax 会把胶囊当普通链接整页换掉主区，与"首页就地切换"直接冲突。同时 `.on` 态后续由 JS 维护（PHP 初渲染保持现状不变）。

**2b. 可隐藏标记**（三处加 class 钩子，不改结构）：
- 置顶大卡外层（`index.php:79` 起的 `$oy_featured` 块最外元素）→ 加 `id="oyFeatured"`
- `index.php:151` `<div class="grid" id="grid">` → 加 `id` 保持，外层无需动
- `index.php:161` `#loadWrap` 与 `index.php:169` `#homePager` → 保持 id（JS 直接选中加 `.oy-hidden`）

**2c. section-head 标题可寻址**（`index.php:146`）：

```php
<div><h3 id="gridTitle">最新文章</h3><div class="sub" id="gridSub">Latest Posts</div></div>
```

### T3 · 新模块 `assets/js/homecat.js`（新建，全量）

```javascript
/**
 * 首页分类胶囊 AJAX 预览（局部切换，URL 不变）。
 * 依赖：ONEYER.ajax / ONEYER.nonce；与 pjax 互斥靠 chips 的 .no-pjax。
 */
(function () {
	'use strict';
	var chipsWrap = document.getElementById('chips');
	if (!chipsWrap || !window.ONEYER) return;

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
			foot.innerHTML =
				'<a class="loadmore" href="' + data.cat_url + '">进入分类页' +
				'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m9 6 6 6-6 6"/></svg></a>' +
				'<span class="load-hint">已展示 ' + data.per_page + ' / ' + data.found + ' 篇 · 更多内容进分类页</span>';
			/* <a> 是普通链接：pjax 拦截=无刷新进入（跟随当前机制）；未装 pjax=整页跳转 */
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

		/* 全部：DOM 显隐恢复，零请求零重绑（loadmore 状态无损） */
		if (!cat) { setOn(chip); restoreAll(); return; }

		if (loading && controller) controller.abort();
		loading = true;
		setOn(chip);
		if (filterBox) filterBox.classList.add('is-loading');
		controller = new AbortController();
		var timer = setTimeout(function () { controller.abort(); }, 8000);

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
			/* 失败弹回全部态 */
			restoreAll();
			setOn(chipsWrap.querySelector('.chip[data-cat="0"]'));
		});
	});
})();
```

注册（锚点 `functions.php:81` 后追加一行）：

```php
wp_enqueue_script( 'oneyer-homecat', get_theme_file_uri( '/assets/js/homecat.js' ), array( 'oneyer-main' ), (string) filemtime( get_theme_file_path( 'assets/js/homecat.js' ) ), true );
```

> **pjax 转场后的重挂**：从其他页 pjax 回首页时，homecat 的监听绑在旧 DOM 的 `#chips` 上会失效。解法：homecat.js 挂载逻辑同样收进 `mountAll(ctx)`？——**不并入**，更简单：pjax 转场后整页 DOM 已替换，`#chips` 是新节点。把上述 IIFE 改造为 `window.ONEYER_HOMECAT = { mount: function(ctx){…} }` 导出，首屏自调一次；并在 main.js 的 `mountAll` 末尾追加 `if (window.ONEYER_HOMECAT) window.ONEYER_HOMECAT.mount(ctx);`（锚点 `main.js:168` mountAll 函数体内最后一行）。mount 内做防重入（`if (ctx.getElementById('chips') && bound) return`——以 chips 是否已绑 data 标记判断）。**实施时按此调整 IIFE 结构，逻辑不变。**

### T4 · CSS 追加（main.css 末尾）

```css
/* ── 首页分类 AJAX 预览 ── */
.oy-hidden{display:none!important}
#grid-filter{animation:rise .5s both}
#grid-filter.is-loading{opacity:.45;transition:opacity .15s}
#grid-filter .load-wrap{display:flex;flex-direction:column;align-items:center;gap:12px;margin:46px 0 10px}
#grid-filter .loadmore{ /* 复用按钮样式的 <a> 形态 */
  display:inline-flex;align-items:center;gap:9px;padding:13px 44px;border-radius:99px;
  border:1.5px solid var(--blue);color:var(--blue);font-size:15px;font-weight:600;
  background:rgba(255,255,255,.7);transition:all .2s}
#grid-filter .loadmore:hover{background:var(--blue);color:#fff;box-shadow:0 8px 22px rgba(64,158,255,.35);transform:translateY(-2px)}
```

### T5 · 设置项语义扩展（inc/admin-settings.php:47，一行 desc 替换）

```php
'oneyer_home_ppp' => array( 'label' => '首页每页/分类预览条数', 'type' => 'number', 'desc' => '首页网格每页条数 = 点分类胶囊就地预览的条数（共用）。桌面端 3 列，建议 3 的倍数：6/9/12。' ),
```

**顺带统一（强烈建议，一行）**：`functions.php:102` 分类页写死的 `9` 改为同一设置：

```php
$query->set( 'posts_per_page', max( 1, (int) oneyer_opt( 'oneyer_home_ppp', '9' ) ) );
```

（否则后台设 12，首页预览 12 条、进分类页却每页 9 条，体验断裂。）

### T6 · 需求 2：分类页 AJAX 翻页 = 验收既有 pjax（零新代码）

pjax 已落地（functions.php:81、header.php:18），分页器输出的是普通链接（`oneyer_pagination()`，functions.php:492）→ 被 pjax 拦截，`#oneyer-main` 整区无刷新切换、URL 变 `/category/xxx/page/2/`、后退恢复。**只需走 §四 验收**。唯一注意：分页器链接**不得**有人加过 `no-pjax`（当前没有，别在实施中误加）。

---

## 四、验收清单（dev.oneyer.cc，桌面 1440 + 移动 430/真机各过一遍）

**首页分类 AJAX 预览（需求 1 核心）**
- [ ] 点「AIGC图像生成」→ 无刷新：Hero/统计卡/胶囊条/混排区/页脚全部不动，只有网格区换成该分类 9 张卡（逐张入场动画）
- [ ] 地址栏保持首页 URL 不变；刷新页面 → 回「全部」默认态
- [ ] 置顶大卡在分类态隐藏；点回「全部」恢复显示
- [ ] 点回「全部」后，「加载更多」从之前追加到的页码**继续追加**（状态无损，如已点过一次追加 → 再点从第 3 页继续）
- [ ] 分类态底部：「进入分类页」按钮（105 > 9 场景）→ 点击无刷新进入分类页（pjax）；后退手势回首页=全部态
- [ ] 分类态底部：文章数 ≤ N 的分类（如 AIGC语音创作 1 篇）→ 无按钮，提示「已展示全部 1 篇」
- [ ] 区块标题随分类切换（「AIGC图像生成 / Category · 前 9 篇预览」↔「最新文章 / Latest Posts」）
- [ ] 后台把条数设为 6 → 全部态网格 6 张（2 行）+ 分类预览也是 6 张；设 12 同理（4 行）
- [ ] 空分类胶囊不出现（`hide_empty=true` 已有）；接口异常/断网 → 胶囊弹回全部 + 无 JS 报错
- [ ] 连点两个不同胶囊 → 最终展示最后点击的分类，无交错渲染

**分类页 AJAX 翻页（需求 2）**
- [ ] 分类页点第 2 页 → 无刷新，网格与分页器 active 更新，URL 变 `/page/2/`
- [ ] 后退手势回第 1 页；F5 刷新停在第 2 页（URL 真实）
- [ ] 搜索页/归档页分页同表现（同一分页器函数）

**双端**
- [ ] 移动端 430px：分类预览单列不破版；胶囊条横向滚动正常；连点竞态正常
- [ ] 桌面端 3 列 × N(=9) 恰好整行填充，无半行缺卡
- [ ] pjax 从文章页返回首页 → 胶囊切换功能仍可用（T3 重挂生效）
- [ ] 禁 JS：胶囊为普通链接（点击整页进分类页，原生可用）
- [ ] `WP_DEBUG=true` 无 notice；Network 面板确认 homecat 请求仅 1 次/次点击

---

## 五、红线汇总

1. **chips 必须加 `no-pjax`**——不加则与 pjax 整页切换直接冲突（胶囊点了整页换主区，混排区消失）。这是本方案第一红线。
2. 切换采用 **DOM 显隐**（`.oy-hidden`），**禁止**移除/重建原网格与 `#loadWrap`——「加载更多」闭包页码状态必须无损，重建 = 状态丢失。
3. URL 不变是拍板决策：**不得**给分类切换加 pushState（刷新回默认态是验收项，不是缺陷）。
4. 接口 `cat` 参数 `absint` + `term_exists` 双校验；nonce 复用 `ONEYER.nonce`；输出经既有 `post-card` 模板（自动 esc）。
5. 「进入分类页」渲染为**普通 `<a>`**（不带 no-pjax）——让 pjax 按当前机制自然拦截，不自行判断 pjax 是否存在。
6. N 只有一个事实源 `oneyer_home_ppp`：分类页 `functions.php:102` 的写死 9 必须一并改掉（否则预览/分类页条数断裂）。
7. pjax 回首页后 homecat 必须重挂（T3 说明的 mount 化改造），且防重复绑定。
8. 不动 main.js 既有 loadmore 模块（main.js:287-345）一行代码；不动 pjax.js。
