# 原型审计记录（T0 产物）

## 0. 环境勘察结论

| 项 | 结论 |
|---|---|
| 工作区 | `E:\桌面\项目总览\WP主题\`（非 Git 仓库，不主动初始化） |
| 原型仓库 | `oneyer/`（独立 Git 仓库，main 分支，工作区干净，1 个提交） |
| 实施模式 | **模式 A**：在原型仓库内新建 `oneyer-theme/`，不覆盖任何原型文件 |
| 本地站点 | `../wordpress/` 为完整 WordPress（wp-config.php 存在，已配置） |
| 运行环境 | PHP 8.3.35（tools/php）、MariaDB 3307、PHP 内置服务器 127.0.0.1:8080、WP-CLI（tools/wp.bat） |
| Git 保护 | oneyer 仓库无未提交改动；不 reset/checkout/clean；主题独立目录可整体删除回滚 |

## 1. 原型文件清单与模板映射

| 原型 | 行数 | WP 模板 | 页面专属区块 |
|---|---|---|---|
| index.html | 768 | front-page.php / home.php / index.php | Hero+统计、分类 chips、置顶大卡、卡片网格、双列混排（热榜/标签云/友链）、加载更多 |
| single.html | 906 | single.php + comments.php | 面包屑、文章卡、prose 正文（表格/代码块/提示框/图注）、悬浮目录+数据小卡、上下篇、相关文章、评论（楼中楼）、分享 |
| category.html | 469 | category.php | 分类横幅（图标/统计）、子分类 chips、分页器（含提示行） |
| archive.html | 578 | page-templates/page-archive-timeline.php | 归档头部、年份锚点 tabs、年/月双层时间线 |
| search.html | 489 | search.php | 大搜索框、热词、结果统计行、.sr 结果条、无结果态、相关标签 |
| about.html | 517 | page-templates/page-about.php | 站长卡、统计行、里程碑、技能条、本站构成、订阅三卡、留言指引 |
| links.html | 524 | page-templates/page-links.php | 页头、分组友链卡（常逛/新链/五年+/失联徽章）、申请友链+本站信息卡 |
| 404.html | 417 | 404.php | 404 视窗（终端风）、推荐文章 |

## 2. 公共组件（8 页重复 → 单点维护）

- `.progress` 进度条、`.nav` 毛玻璃导航（scrolled 投影、`.submenu` 双列下拉、`.nav-search`、`.nav-burger`）
- `.drawer` 移动抽屉（mask、d-search、d-link+计数徽章）
- `.card` 文章卡（`.grad g1~g12` 渐变 + `.glyph` 字符兜底、cat-badge、views 悬浮徽章）
- `footer` 深色页脚（`</>` 水印、四列、icp 行）
- `.backtop`、`.reveal` 渐入、`.chips` 分类胶囊、`.pager` 分页器、`.crumb` 面包屑

## 3. 交互清单（→ assets/js/main.js）

进度条、导航投影、回到顶部、抽屉开合（补 Escape/焦点/ARIA）、数字滚动(data-count)、
分类筛选（改 URL/服务端跳转）、滚动渐入 IO、目录 scroll-spy、年份锚点 scroll-spy、
代码块复制、友链信息复制、加载更多（AJAX 化）、技能条入场生长、Ctrl+K 聚焦搜索（原型仅视觉，主题补全）

## 4. 静态假数据清单（→ 真实数据源）

| 假数据 | 位置 | 替换 |
|---|---|---|
| Hero 统计 328/1024+/56/2190 | index | wp_count_posts / wp_count_comments / CPT 友链数 / 建站天数(自定义器) |
| 9 张手写卡片 + JS 假追加 3 张 | index | 主循环 + post-card.php；AJAX 加载更多 |
| chips 分类计数 | index/category | get_categories()->count |
| 置顶大卡内容 | index | sticky_posts 查询，无则最新一篇 |
| 热门阅读 6 条（含假阅读量） | index | WP_Query orderby=comment_count；副行显示真实评论数 |
| 标签云计数 | index | get_tags() |
| 友链 4 卡 | index | 友链 CPT |
| 分页 1/8 页·74 篇 | category | 真实分页 |
| 搜索热词/结果 | search | 热门标签 / 主查询 |
| 时间线全部条目 | archive | 按真实文章年月分组 |
| 站长卡/里程碑/技能条/订阅卡 | about | 动态统计 + 自定义器（未配置则隐藏对应模块） |
| 友链 10+8+3 卡 | links | 友链 CPT（分组/状态/徽章） |
| 页脚 ICP「蜀ICP备…」 | 全部 | 自定义器，空则不输出 |
| 评论 58 条 | single | wp_list_comments |
| 目录/阅读时长/字数 | single | the_content 解析 / mb_strlen 计算 |

## 5. 需删除的原型自检件

- search.html 的 `.state-toggle`（有结果/无结果态预览切换）
- index.js 的 `extraCards` 假追加数组

## 6. 待确认设计细节（按现状处理，均有兜底）

1. Google Fonts（Outfit/JetBrains Mono）→ 尝试本地化 woff2，失败则系统字体栈（CSS 已含回退）
2. 文章阅读量 → 无可靠数据源，卡片悬浮徽章改显示真实评论数
3. 子菜单图标/描述 → 菜单项描述字段（后台可选填）+ 位置序号取色板
4. 首页 chips → 按红线 #4 改为分类归档链接（服务端筛选），保留胶囊视觉

## 7. 设计保真检查方法

每阶段完成后，浏览器 1280px 与 390px 截图对照原型静态页；CSS 选择器一律沿用原型命名。
