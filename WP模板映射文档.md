# Oneyer 主题 · WP 模板映射文档

> 源原型：`oneyer/` 目录 8 个 HTML 单页（StuFlow 设计系统）
> 目标：WordPress 经典主题（PHP 模板），无登录展示、无用户中心
> 本文档 = coding agent 可直接执行的任务卡片，按 T1→T10 顺序执行，每卡自带验收命令

---

## 〇、大白话总览（先读这段）

这套原型要变成 WP 主题，本质上就三件事：

1. **拆公共件**：8 个 HTML 页里，导航栏、页脚、移动端抽屉菜单、滚动进度条是每页都有的 → 抽成 `header.php` / `footer.php`，以后改一处全站生效。
2. **把写死的内容换成 WP 数据**：HTML 里那些「手打的」文章卡片、分类、标签、评论 → 换成 WP 的循环函数（The Loop / WP_Query），WP 后台一发文章，前台自动出现。
3. **每页套一个模板文件**：WP 有一套「模板层级」规则——访问什么 URL 就自动找什么文件名。我们只要按规矩命名文件（`single.php` 管 文章页、`category.php` 管分类页……），内容按原型排版填进去就行。

不需要改的：CSS 变量、卡片布局、动效 JS（筛选/抽屉/进度条/目录高亮）全部原样搬进 assets，只需把 JS 里的假数据钩子换成真实 DOM。

---

## 一、主题目录结构（目标形态）

```
oneyer-theme/
├── style.css                 # 主题头注释 + 全量样式（从原型 <style> 合并）
├── functions.php             # 主题启动：注册菜单位/小工具区/资源加载/免登录评论
├── header.php                # ← 导航+抽屉+进度条（T2）
├── footer.php                # ← 深色页脚+备案位（T3）
├── searchform.php            # ← 导航搜索框（含 Ctrl K）
├── index.php                 # ← 兜底模板（= 首页卡片流，T4）
├── front-page.php            # ← 首页（可并入 index.php，见 T4 说明）
├── single.php                # ← 文章详情页（T5）
├── comments.php              # ← 免登录评论区（T5 子卡）
├── category.php              # ← 分类页（T6）
├── archive.php               # ← 标签/作者/日期归档兜底（T6）
├── search.php                # ← 搜索结果页（T7）
├── 404.php                   # ← 404 页（T7）
├── page.php                  # ← 普通页面兜底
├── page-templates/
│   ├── page-archive-timeline.php   # ← 归档时间线页（T8，页面模板）
│   ├── page-about.php              # ← 关于页（T8）
│   └── page-links.php              # ← 友链页（T8）
├── template-parts/
│   ├── post-card.php         # ← 文章卡片（首页/分类/相关文章复用）
│   ├── chip-filter.php       # ← 分类胶囊筛选条
│   └── pagination.php        # ← 分页器
├── assets/
│   ├── css/main.css          # 设计令牌 + 组件样式（原型 style 全量）
│   └── js/main.js            # 进度条/抽屉/筛选/scroll-spy（原型 script 全量）
```

---

## 二、页面 → 模板总映射表

| 原型 HTML | WP 模板文件 | WP 触发 URL | 说明 |
|---|---|---|---|
| `index.html` | `index.php`（或 `front-page.php`） | `/` | 首页 = 卡片流 + 置顶大卡 + 热门/标签/友链混排 |
| `single.html` | `single.php` + `comments.php` | `/?p=ID` | 正文窄栏 + 悬浮目录 + 免登录评论 |
| `category.html` | `category.php` | `/category/slug/` | 分类横幅 + 子分类筛选 + 卡片网格 + 分页 |
| —（兜底） | `archive.php` | `/tag/…` `/author/…` `/2026/…` | 复用 category 布局，横幅信息换成对应对象 |
| `search.html` | `search.php` | `/?s=关键词` | 大搜索框 + 结果列表（mark 高亮）+ 无结果态 |
| `archive.html` | `page-templates/page-archive-timeline.php` | 自建页面挂模板 | 年份时间线（WP 无内置时间线模板，用页面模板实现） |
| `404.html` | `404.php` | 任意 404 | 齿轮 404 + 热门文章 |
| `about.html` | `page-templates/page-about.php` | 自建页面挂模板 | 关于页（固定内容 + 少量动态统计） |
| `links.html` | `page-templates/page-links.php` | 自建页面挂模板 | 友链页（数据源见 T8-C） |

**公共区块归属**

| HTML 区块（每页重复） | 归属 |
|---|---|
| `.progress` 进度条 / `.nav` 毛玻璃导航 / `.drawer` 移动抽屉 | `header.php` |
| `.nav-search`（含 kbd Ctrl K） | `searchform.php`，header 内 `get_search_form()` |
| `footer` 深色页脚（含备案 + `</>` 水印） | `footer.php`（备案号挂主题选项） |
| `.backtop` / 渐入 `.reveal` / scroll-spy | `assets/js/main.js` 全站加载 |

---

## 三、数据替换对照（假数据 → WP 函数）

| 原型假数据 | 替换为 |
|---|---|
| 9 张手打文章卡片 | `WP_Query` / 主循环 + `template-parts/post-card.php` |
| 卡片渐变缩略图（`.g1~.g12` + glyph 字符） | `get_the_post_thumbnail_url()` 有图用图；无图回退：按分类 slug 映射渐变色板 + 首字符（保留原型观感，写 helper `oneyer_card_media()`） |
| `chips` 分类胶囊（WordPress 86 / 前端开发 74…） | `get_categories()`，计数用 `->count`；「全部」= 当前页链接 |
| Hero 统计 328/1024/56/2190 | `wp_count_posts()->publish` / `wp_count_comments()->approved` / 友链数（T8-C 数据源）/ `floor((time()-STRTOTIME('开通日'))/86400)`（开通日挂主题选项） |
| 置顶大卡（featured） | `WP_Query(['post__in'=>get_option('sticky_posts'),'posts_per_page'=>1])` |
| 热门阅读榜 | `WP_Query(['orderby'=>'comment_count','posts_per_page'=>6])`（或挂 post views 插件 meta） |
| 标签云 | `wp_tag_cloud(['format'=>'flat','smallest'=>13,'largest'=>13,'unit'=>'px'])`，样式覆盖到胶囊 |
| 友链 4 卡（首页侧） | `wp_list_bookmarks()` 或 WPJam/自建链接 CPT（T8-C 统一） |
| 详情页目录（h2/h3） | 正文 `the_content()` 后正则抓 `<h2|h3 id>` 生成目录（helper `oneyer_toc()`，自动给标题加 id） |
| 上/下篇 | `previous_post_link()` / `next_post_link()`，模板覆盖加 `.pn` 结构 |
| 相关阅读 3 卡 | `WP_Query(['category__in'=>wp_get_post_categories(get_the_ID()),'post__not_in'=>[get_the_ID()],'posts_per_page'=>3])` |
| 评论列表（含楼中楼） | `wp_list_comments(['style'=>'div','max_depth'=>2'])` + 原型 `.cmt` 回调样式 |
| 免登录评论表单 | `comment_form()`（WP 天然免登录，仅需后台开启「允许无需注册评论」） |
| 分类横幅统计 | `get_queried_object()` 取 term 名/描述 + `->count` |
| 搜索关键词高亮 | `the_title()`/`the_excerpt()` 输出时 `preg_replace` 关键词包 `<mark>`（helper `oneyer_highlight()`，`get_search_query()` 转义） |
| 归档时间线 | `WP_Query` 全量 posts 按 `post_date` 年/月分组（数据量 <2000 可一次拉，更大改 SQL GROUP BY） |
| 分页器 | `the_posts_pagination()` + Walker 输出原型 `.pager` 结构（含「第 x / y 页 · 共 n 篇」行 = `oneyer_pager_hint()`） |

---

## 四、functions.php 骨架（T1 卡）

```php
<?php
// 锚点：functions.php 新建
function oneyer_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form','comment-form','comment-list','gallery','caption']);
    add_theme_support('automatic-feed-links');
    register_nav_menus(['primary' => '主导航', 'drawer' => '移动端抽屉菜单']);
}
add_action('after_setup_theme', 'oneyer_setup');

// 备案号/开通日/友链开关（外观→自定义）
function oneyer_customize($wp_customize) {
    $wp_customize->add_section('oneyer_site', ['title' => 'Oneyer 站点信息']);
    foreach ([['icp','ICP 备案号'],['police','公安备案号'],['since','建站日期 YYYY-MM-DD']] as [$k,$label]) {
        $wp_customize->add_setting("oneyer_{$k}", ['sanitize_callback' => 'sanitize_text_field']);
        $wp_customize->add_control("oneyer_{$k}", ['section'=>'oneyer_site','label'=>$label,'type'=>'text']);
    }
}
add_action('customize_register', 'oneyer_customize');

// 资源加载（Google Fonts 改本地化，规避国内访问问题）
function oneyer_assets() {
    wp_enqueue_style('oneyer-main', get_template_directory_uri() . '/assets/css/main.css', [], '1.0');
    wp_enqueue_style('oneyer-fonts', get_template_directory_uri() . '/assets/fonts/fonts.css', [], '1.0'); // 子集化的 Outfit/JetBrains Mono
    wp_enqueue_script('oneyer-main', get_template_directory_uri() . '/assets/js/main.js', [], '1.0', true);
    wp_localize_script('oneyer-main', 'ONEYER', ['ajax' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('oneyer')]);
}
add_action('wp_enqueue_scripts', 'oneyer_assets');

// 导航菜单 <li> 换原型结构：.nav-menu>li>a（含 .active、二级 .submenu）
class Oneyer_Menu_Walker extends Walker_Nav_Menu {
    // start_lvl 输出 <div class="submenu">，start_el 输出 <a class="…">
    // 锚点：把原型 category.html 的 .submenu HTML 结构逐标签对应实现
}
```

**验收**：主题能在 WP 6.x 启用无报错；`菜单` 后台出现两个菜单位。

---

## 五、任务卡片（按序执行）

### T2 · header.php + searchform.php
- **锚点**：`category.html`（结构最全，含 submenu）第 `nav-wrap` 到 `</aside>` 整段
- **指令**：整段迁入 `header.php`，`wp_body_open()` 前插 `.progress`；菜单数组 → `wp_nav_menu(['theme_location'=>'primary','walker'=>new Oneyer_Menu_Walker(),'container'=>false,'items_wrap'=>'<ul class="nav-menu">%3$s</ul>'])`；`.nav-search` 换 `get_search_form()`；`.active` 判定 = `oneyer_is_current_menu()`（用 `get_queried_object_id()` 对比 menu item object_id）；抽屉菜单单独挂 `drawer` 位置，计数 `->count` 输出到 `.n`
- **验收**：8 个页面顶部导航一致；移动 430px 汉堡抽屉开合正常；`Ctrl K` 聚焦搜索框（main.js 已有钩子）

### T3 · footer.php
- **锚点**：任一页 `footer` 整段
- **指令**：栏目/站点列 → `wp_nav_menu` 两个新菜单位（footer-cats / footer-site）或直接 `wp_list_categories`；备案号 → `get_theme_mod('oneyer_icp')` / `oneyer_police`（为空则整行不输出）；版权年份 = `oneyer_since` ~ 当前年动态生成
- **验收**：自定义器改备案号 → 页脚实时变

### T4 · 首页 index.php + post-card.php + chip-filter.php + pagination.php
- **锚点**：`index.html` 的 `hero / chips / featured / grid / mix / load-wrap` 六段
- **指令**：
  1. `post-card.php`：`.card` 结构，字段→ `get_the_permalink()/get_the_title()/oneyer_card_media()/get_the_date('m-d')`，tags → `get_the_tag_list()` 拆 span；`data-cat` 属性输出**第一个分类 slug**（供 JS 筛选）
  2. `chips` → `chip-filter.php`：`get_categories(['exclude'=>get_cat_ID('随笔记')? 0:0])` 按需排除页面类目；`data-cat` 同步 slug
  3. `featured`：sticky 查询，无 sticky 则取最新 1 篇降级
  4. `mix` 区热门榜/标签云/友链按「数据替换对照」实现
  5. **「加载更多」**：原 JS 假追加 → 改 AJAX：`admin_post_nopriv_oneyer_loadmore`，返回 3 张卡片 HTML（`post-card.php` 复用）；加载完变「已经到底啦」判定 = `max_num_pages`
  6. `.hidden-by-filter` 纯前端筛选逻辑保留（JS 已写好）
- **单测要点**：置顶文 1 篇时首页大卡不空；分类胶囊点击后 grid 计数与 chip cnt 一致
- **验收**：`/?s=` 与 `/` 均渲染正常；发布新文章立刻出现在网格

### T5 · single.php + comments.php + 目录生成
- **锚点**：`single.html` 的 `article-layout / post / pn-nav / related / comments` 五段 + `toc-wrap`
- **指令**：
  1. 正文 `.prose` = `the_content()`；主题 editor 样式：把 `.prose h2/h3/blockquote/表格/代码块` CSS 同时注册 `add_editor_style()`，保证后台编辑器所见即所得
  2. `oneyer_toc($content)`：`the_content` 过滤器——给无 id 的 h2/h3 注入 `id="s-序号"` 并返回目录数组存全局；`.toc` 渲染 `wp_kses_post` 转义后的标题；≤1180px CSS 已自动隐藏目录
  3. 阅读时长 = `mb_strlen(strip_tags)/400` 取整；字数 `number_format`
  4. `comments.php`：`comment_form()` 定制字段顺序（昵称/邮箱/评论文本框）；`wp_list_comments(['callback'=>'oneyer_comment','style'=>'div','max_depth'=>2])`，callback 输出原型 `.cmt` 结构（头像 `get_avatar`、站长徽章 = `user_id==1`、`human_time_diff`）；「回复」= `comment_reply_link`
  5. 代码块复制按钮：正文含 `<pre>` 时 main.js 已有 `.copy` 逻辑，编辑器侧给 pre 统一包 `.codeblock` 结构（`oneyer_codeblock()` 过滤器）或使用 Prism 插件输出同构 HTML
- **验收**：未登录访客可发评论（后台 设置→讨论 勾选「无需注册」）；目录随滚动高亮正确

### T6 · category.php + archive.php
- **锚点**：`category.html` 的 `cat-banner / chips / grid / pager`
- **指令**：横幅四要素（图标字符/名称/描述/统计）→ `get_queried_object()`；图标 = term slug→字符映射表（写 `oneyer_cat_glyph($slug)` helper，与卡片色板共用一张表）；子分类筛选条 = `get_term_children()` 有子类则渲染、无则隐藏；`archive.php` 复制本模板，横幅按 `is_tag()/is_author()/is_date()` 换文案
- **验收**：`/category/code/` `/tag/vite/` `/2026/` 三种 URL 横幅正确；分页第 2 页正常

### T7 · search.php + 404.php
- **锚点**：`search.html`（注意删掉原型顶部的「有结果态/无结果态」预览切换按钮，那是给原型自检用的）与 `404.html` 的 `nf-hero / grid`
- **指令**：结果列表用主循环输出 `.sr` 结构，标题/摘要过 `oneyer_highlight(get_search_query())`；`have_posts()` 为假 → 渲染 `.no-result`（热词 → 热门 tag top5）；耗时 = `timer_stop()`；`404.php` 的热门 3 卡 = comment_count 排序 WP_Query；齿轮 404 SVG 与浮动动画 CSS 原样迁移
- **验收**：搜「Vite」高亮、搜乱码出无结果态；`/不存在页面/` 出 404

### T8 · 三个页面模板（归档时间线 / 关于 / 友链）
- **T8-A 归档时间线** `page-archive-timeline.php`：
  - 锚点：`archive.html` 全部
  - 指令：`Template Name: 归档时间线` 头注释；`WP_Query(['posts_per_page'=>-1,'nopaging'=>true])` 后按 `get_the_date('Y')` → `get_the_date('n')` 双层分组渲染 `.year-block/.month-group/.arc-item`；年份锚点 tabs 与 scroll-spy JS 复用（main.js 的 `#yearTabs` 选择器不变）；`置顶` 徽章 = `is_sticky()`
  - 性能红线：>1500 篇改 `get_posts` 字段瘦身（只取 ID/date/title/permalink）
- **T8-B 关于页** `page-about.php`：
  - 锚点：`about.html`
  - 指令：站长卡/时间线/技能栈保留**页面正文可编辑区**（`the_content` 放技能栈上方，其余为模板固定结构）；统计数字动态化（见对照表）；订阅三卡 URL 挂自定义器
- **T8-C 友链页** `page-links.php` + 友链数据源：
  - 锚点：`links.html`
  - 指令：数据源采用「链接管理器复活」：`add_action('init')` 里 `remove_filter('pre_option_link_manager_enabled','__return_false')` 或直接用自建 CPT `friend_link`（字段：名称/URL/简介/头像字符/主题色/分组/状态[正常|失联]）——**推荐 CPT**，失联徽章与分组渲染最省事；本站信息卡 4 行留静态 + 复制按钮 JS 原样；申请条件文本为页面正文，允许后台改
- **验收**：三页在 WP 后台「新建页面→模板」可选；友链 CPT 加一条 → 前台卡片即时出现

### T9 · 资产与字体本地化
- **指令**：`main.css` = 8 页 HTML `<style>` 去重合并（页级独有样式按 body class 隔离：`body.category / body.search / body.error404 / page-template-page-links` 等 WP 默认 body_class）；Google Fonts 下载 woff2 子集（中文正文用系统栈即可，仅 Outfit/JetBrains Mono 需拉丁子集 <50KB）放 `assets/fonts/` + fonts.css；`main.js` = 各页 script 去重合并，保留：进度条/抽屉/chips 筛选/scroll-spy/渐入/复制/数字滚动/加载更多（T4 AJAX 化）
- **验收**：Lighthouse 移动端 ≥95；无外链 Google 域名请求

### T10 · 兼容与安全兜底
- **指令**：`esc_url/esc_attr/esc_html` 全量包裹输出；评论输出过 `wp_kses_post`；`money`类动态数字加 `absint`；菜单为空时 `wp_page_menu()` 兜底；`add_theme_support('responsive-embeds')`；READM E 写部署说明（页面模板建页步骤 + 后台设置项清单）
- **验收**：`theme-check` 插件无 ERROR 级；WP_DEBUG=true 无 notice

---

## 六、执行顺序与依赖

```
T1 functions.php
 └→ T2 header → T3 footer        （先公共后页面）
     └→ T4 首页 → T5 详情 → T6 分类/归档 → T7 搜索/404 → T8 三页面模板
         └→ T9 资产合并 → T10 兜底验收
```

每张卡完成 = 该页面浏览器对照原 HTML 走查一遍（视觉一致 + 交互可用）再进下一卡；全部完成后跑 T10 终验。

---

## 七、固定原则（红线）

1. 原型 CSS 选择器名（`.card/.chip/.prose/.toc` 等）**一律不改名**——JS 与模板互锁，改名 = 连锁炸
2. 无登录/无用户中心：不引用 `wp_login_url()`、不输出作者存档链接于导航（作者页保留但不入口）
3. 卡片无特色图时的渐变+字符兜底必须保留（这是这套皮肤的识别度来源）
4. 「加载更多」「分类筛选」优先走 URL/服务端（可被搜索引擎抓取），JS 增强只做体验层
