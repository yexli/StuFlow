# Oneyer 主题 · 站点 Logo 方案（单字符 → 渐变方块 / 图片链接 → 圆角图片）

> 需求：站点 Logo（导航栏站点名左侧图标）——后台输入**单个字符**（中文/英文/数字）显示该字符的品牌渐变方块；输入**图片链接**显示圆角图片；留空保持现状
> 实施对象：`E:\桌面\项目总览\WP主题\oneyer\oneyer-theme`（测试站 cs.oneyer.cc）
> 性质：coding agent 可直接执行。本方案**只做站点 Logo 一件事**，不涉及评论/友链/站长头像
> 本文档取代早期的《头像系统方案.md》（范围过大，已废弃删除）

---

## 〇、大白话总览

现在站点名左边的图标是个写死的方块：取**站点名第一个字**放在蓝色渐变里（`.logo-mark`）。桌面导航虽然支持 WP 的「自定义 Logo」（要传媒体库），但移动端抽屉和页脚没跟上。

方案做的事很小：

1. 后台加一个输入框 `站点 Logo`：填一个字（如 `雾`）→ 方块里显示这个字；填图片地址 → 显示图片；**不填 → 一切照旧**（有自定义 Logo 用自定义 Logo，否则站点名首字）。
2. 写一个 `oneyer_logo_html()` 函数统一输出，三处渲染点（桌面导航 / 移动抽屉 / 页脚）全部换成调它——以后 Logo 规则只改一个函数。
3. 图片挂了自动回退字符方块，不留破图。

**优先级链（唯一事实源）**：

```
后台「站点 Logo」输入框
 ├─ 是图片链接(http/https) → <img> 圆角图片
 ├─ 含合法单字符(中文/英文/数字) → 取首个合法字符 → 品牌渐变方块
 ├─ 空 → WP 自定义 Logo（媒体库，若设置过）→ <img>
 └─ 都没有 → 站点名首字 → 品牌渐变方块（现状行为，兜底）
```

---

## 一、现状盘点（锚点已核实）

| # | 渲染点 | 现状代码 | 文件:行 |
|---|---|---|---|
| 1 | 桌面导航 Logo | `has_custom_logo() ? the_custom_logo() : <span class="logo-mark">站点名首字</span>` | `header.php:22-26` |
| 2 | 移动端抽屉 Logo | `<span class="logo-mark" style="width:30px…">站点名首字</span>`（无自定义 Logo 分支） | `header.php:42` |
| 3 | 页脚品牌 Logo | `<span class="logo-mark">站点名首字</span>`（无自定义 Logo 分支） | `footer.php:22` |
| 4 | 样式 | `.logo-mark`（36px 方块/品牌渐变/圆角 11px/hover 旋转） | `assets/css/main.css:90-97` |
| 5 | 自定义 Logo 尺寸复位 | `.custom-logo{width:36px…}` | `assets/css/main.css:1288` |
| 6 | 主题已声明 custom-logo 支持 | `add_theme_support('custom-logo', …)` | `functions.php:31` |
| 7 | 设置读取口 | `oneyer_opt( $key, $fallback )` | `functions.php:210` |

尺寸规格：桌面/页脚 36px（字号 17px）；移动抽屉 30px（字号 14px）。字符方块**固定品牌渐变** `#5aabff→#2f6fd6→#1b4f9e`（即 `.logo-mark` 现有背景，站点标识不做散列变色）。

---

## 二、核心代码

### 二-A · 渲染函数（`functions.php` 文件末尾追加，完整代码）

```php
/* -------------------------------------------------------------------------
 * 站点 Logo：单字符 → 渐变方块；图片链接 → 圆角图片；空 → 自定义Logo/站点名首字。
 * 判定优先级与规格见《站点Logo方案.md》，改规则只改本函数。
 * ---------------------------------------------------------------------- */

/** 取字符串首个合法字符（中文/英文/数字；emoji、符号不算），无则空串。 */
function oneyer_logo_first_char( $text ) {
	if ( ! is_string( $text ) || '' === $text ) {
		return '';
	}
	foreach ( mb_str_split( trim( $text ) ) as $ch ) {
		if ( preg_match( '/^[\p{L}\p{N}]$/u', $ch ) ) {
			return $ch;
		}
	}
	return '';
}

/**
 * 站点 Logo 统一渲染器。
 *
 * @param int $size 方块尺寸 px（桌面/页脚 36，移动抽屉 30）。
 * @return string HTML（span 字符方块 或 img）。
 */
function oneyer_logo_html( $size = 36 ) {
	$size = max( 16, absint( $size ) );
	$raw  = trim( (string) oneyer_opt( 'oneyer_logo', '' ) );
	$name = get_bloginfo( 'name' );

	/* 兜底字符：依次从设置输入 → 站点名取首个合法字符 → 固定 "S" */
	$fallback_char = oneyer_logo_first_char( $raw );
	if ( '' === $fallback_char ) {
		$fallback_char = oneyer_logo_first_char( $name );
	}
	if ( '' === $fallback_char ) {
		$fallback_char = 'S';
	}

	/* 分支 1：图片链接（仅 http/https，esc_url 白名单防伪协议注入） */
	if ( preg_match( '#^https?://#i', $raw ) && '' !== esc_url( $raw, array( 'http', 'https' ) ) ) {
		return sprintf(
			'<img class="logo-mark is-img" src="%1$s" alt="%2$s" style="width:%3$dpx;height:%3$dpx;font-size:%4$dpx" loading="eager" decoding="async" referrerpolicy="no-referrer" data-oneyer-fb-char="%5$s">',
			esc_url( $raw, array( 'http', 'https' ) ),
			esc_attr( $name ),
			$size,
			(int) round( $size * 0.47 ),
			esc_attr( $fallback_char )
		);
	}

	/* 分支 2：输入含合法字符（填了字）→ 字符方块（用输入的字符，非站点名首字） */
	if ( '' !== oneyer_logo_first_char( $raw ) ) {
		return sprintf(
			'<span class="logo-mark"%1$s>%2$s</span>',
			36 === $size ? '' : sprintf( ' style="width:%dpx;height:%dpx;font-size:%dpx"', $size, $size, (int) round( $size * 0.47 ) ),
			esc_html( oneyer_logo_first_char( $raw ) )
		);
	}

	/* 分支 3：输入为空 → WP 自定义 Logo（媒体库，若设置过）→ 统一尺寸输出 */
	$logo_id = (int) get_theme_mod( 'custom_logo' );
	if ( $logo_id ) {
		$logo_src = wp_get_attachment_image_url( $logo_id, 'full' );
		if ( $logo_src ) {
			return sprintf(
				'<img class="logo-mark is-img" src="%1$s" alt="%2$s" style="width:%3$dpx;height:%3$dpx" loading="eager" decoding="async" data-oneyer-fb-char="%4$s">',
				esc_url( $logo_src ),
				esc_attr( $name ),
				$size,
				esc_attr( $fallback_char )
			);
		}
	}

	/* 分支 4：兜底 → 站点名首字方块（现状行为） */
	return sprintf(
		'<span class="logo-mark"%1$s>%2$s</span>',
		36 === $size ? '' : sprintf( ' style="width:%dpx;height:%dpx;font-size:%dpx"', $size, $size, (int) round( $size * 0.47 ) ),
		esc_html( $fallback_char )
	);
}
```

> 说明：`36 === $size` 时不写行内 style——直接吃 main.css:90 的默认 `.logo-mark`（36px/17px），非 36px 场景（抽屉 30px）写行内覆盖，避免在 CSS 里加第二套尺寸规则。字号按 0.47 倍尺寸换算（36→17px、30→14px，与现状一致）。

### 二-B · CSS 追加（`assets/css/main.css` 末尾）

```css
/* ── 站点 Logo：图片形态（字符方块样式复用 .logo-mark，勿动） ── */
.logo-mark.is-img{display:block;object-fit:cover;flex:none}
/* 图片形态保留 hover 旋转微动效（与字符方块一致） */
.logo:hover .logo-mark.is-img{transform:rotate(-8deg) scale(1.08)}
```

（`width/height` 已由 helper 行内输出，`border-radius`/阴影/渐变背景不适用于 img——img 自带 `border-radius:11px` 需补一条：）

```css
.logo-mark.is-img{border-radius:11px;box-shadow:0 4px 14px rgba(64,158,255,.45)}
```

（两段合并为一条规则即可，agent 合并时注意别覆盖 main.css:90 原有 `.logo-mark`。）

### 二-C · 图片加载失败兜底（`assets/js/main.js` 末尾追加）

```javascript
/* 站点 Logo 图片加载失败 → 回退品牌渐变字符方块（data-oneyer-fb-char 存兜底字符） */
document.addEventListener('error', function (e) {
  var img = e.target;
  if (!(img instanceof HTMLImageElement) || !img.classList.contains('logo-mark')) return;
  if (img.dataset.oneyerFbDone) return;
  img.dataset.oneyerFbDone = '1';
  var span = document.createElement('span');
  span.className = img.className.replace(/\bis-img\b/g, '').trim();
  span.setAttribute('aria-hidden', 'true');
  span.textContent = img.dataset.oneyerFbChar || 'S';
  /* 行内 style 是 img 的 width/height/font-size，span 直接继承可用，仅补背景： */
  span.style.background = 'linear-gradient(135deg,#5aabff 0%,#2f6fd6 60%,#1b4f9e 100%)';
  span.style.color = '#fff';
  span.style.display = 'grid';
  span.style.placeItems = 'center';
  img.replaceWith(span);
}, true); /* capture：img 的 error 事件不冒泡 */
```

---

## 三、接线卡（3 处渲染 + 2 处设置入口）

### 卡 1 · 设置项注册

**1a. Customizer**（必做）
- 锚点：`functions.php:165-178`（`oneyer_customize_register` 的 `$fields` 数组）
- 指令：`'oneyer_police'` 行后追加一行：

```php
'oneyer_logo'        => array( '站点 Logo（单个字符或图片链接；留空用自定义Logo/站点名首字）', 'text' ),
```

（消毒走该函数既有的 `sanitize_text_field` 分支，无需新增回调。）

**1b. 后台设置页**（若已实施《后台设置页方案》）
- 锚点：`inc/admin-settings.php` 注册表 `base → 建站信息` 组
- 指令：组内追加：

```php
'oneyer_logo' => array( 'label' => '站点 Logo', 'type' => 'text', 'ph' => '如：雾 或 https://…/logo.png', 'desc' => '单个字符（中文/英文/数字）显示为渐变方块；填图片链接显示为图片；留空回退「外观→自定义Logo」或站点名首字。' ),
```

### 卡 2 · 桌面导航渲染（header.php:22-26）

- 指令：`<a class="logo">…</a>` 整段替换为：

```php
<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
	<?php echo oneyer_logo_html( 36 ); ?><span><?php bloginfo( 'name' ); ?></span>
</a>
```

（原 `has_custom_logo()` 分支删除——该判断已收进 helper 分支 3。）

### 卡 3 · 移动抽屉渲染（header.php:42）

- 指令：整行替换为：

```php
<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo oneyer_logo_html( 30 ); ?><span><?php bloginfo( 'name' ); ?></span></a>
```

### 卡 4 · 页脚渲染（footer.php:22）

- 指令：整行替换为：

```php
<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo oneyer_logo_html( 36 ); ?><span><?php bloginfo( 'name' ); ?></span></a>
```

### 卡 5 · 收尾自检

```bash
cd "E:/桌面/项目总览/WP主题/oneyer/oneyer-theme"
php -l functions.php && php -l header.php && php -l footer.php
```

---

## 四、验收清单（cs.oneyer.cc 实测）

后台填入 → 三处（桌面导航 / 移动抽屉 / 页脚）同步检查：

| 后台「站点 Logo」输入 | 预期 |
|---|---|
| `雾` | 三处均显示品牌渐变方块内「雾」，hover 旋转动效正常 |
| `A` / `7` | 英文/数字正常显示 |
| `https://…/logo.png`（外链图） | 三处显示圆角图片：桌面/页脚 36px、抽屉 30px，object-fit 不变形 |
| 站点名首字（如站名「StuFlow」的 `S`） | 输入 `S` 与留空视觉一致 |
| 多字符 `hello` | 显示首个合法字符 `h` |
| emoji `🎉` 或符号 `!!` | 回退：有自定义 Logo 显示 Logo 图，否则站点名首字 |
| `javascript:alert(1)` / `data:text/html,…` | 同上回退，页面源码无注入 |
| 留空（从未设置自定义 Logo） | 站点名首字方块——**与改造前像素级一致** |
| 留空（已设置自定义 Logo） | 显示自定义 Logo（此前抽屉/页脚不显示，现在三处统一） |

破坏性测试：
- [ ] 填一个失效图片 URL → 前台自动回退字符方块，无破图图标（刷新后依旧回退）
- [ ] 移动端 430px：抽屉内 Logo 30px 不变形
- [ ] `WP_DEBUG=true` 无 notice；页面无新增渲染阻塞（Logo 为首屏元素用 `loading="eager"`，其余无影响）

---

## 五、红线汇总

1. 判定逻辑只存在于 `oneyer_logo_html()`——三个模板只调函数，不得残留任何 `mb_substr(…,0,1)` 手写 Logo。
2. 图片 URL 仅 http/https（`esc_url` 白名单），输出侧 `esc_attr/esc_url` 全包，杜绝伪协议注入。
3. 合法字符白名单 = `\p{L}|\p{N}`（中文/英文/数字）；emoji、符号不算，走回退。
4. 字符方块**固定品牌渐变**（`#5aabff→#1b4f9e`），不做随机/散列变色——站点标识要稳定。
5. 留空 = 现状行为兜底（自定义 Logo → 站点名首字），**升级主题后不配置任何东西的老站点视觉零变化**。
6. 图片失败必须有 JS 兜底（capture 监听 error），回退字符方块。
7. 本方案只动站点 Logo；评论/友链/站长头像不在范围内（此前那份大方案已废弃删除，勿混用）。
