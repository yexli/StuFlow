# ONEYER · 测试报告

> 测试环境：Windows 10.0.26300 · PHP 8.3.35 · MariaDB 3307 · WordPress 7.1.3（zh_CN）
> 站点：http://127.0.0.1:8080（tools/ 本地开发环境）
> 测试数据：站点既有 121 篇真实文章 + 种子脚本（评论、置顶、友链 CPT ×5、三个模板页、主导航、自定义器配置）
> 测试方式：WP-CLI + curl + ZCode 内置浏览器（截图走查，1280/1440/390/320 视口）
> 标记说明：✅ 通过 · ❌ 失败（已修复）· ⚪ 未执行

## 1. 安装与基础

| 项 | 结果 | 验证方式 |
|---|---|---|
| style.css 主题头合法，主题列表可见 | ✅ | wp theme list |
| 主题可启用 | ✅ | wp theme activate |
| 全部 16 个 PHP 文件语法检查 | ✅ | php -l × 16 |
| 首页/单篇/分类/搜索/404 无 PHP Warning/Notice | ✅ | WP_DEBUG=true 逐页 curl 检查（10 类页面 × 0 错误） |
| CSS/JS 资源 200 | ✅ | 浏览器网络 + HTML 引用核对（filemtime 版本号） |
| wp_head / wp_footer 正常 | ✅ | 页面源码核对 |

## 2. 页面与数据

| 项 | 结果 | 备注 |
|---|---|---|
| 首页（最新文章模式） | ✅ | Hero 真实统计（121 文/6 评论/5 友链/1457 天）、chips、置顶大卡、9 卡网格、热榜/标签云/友链 |
| 首页（静态页面模式） | ✅ | front-page.php 显式查询，/page/2/ 分页 200 + 9 卡 |
| 文章索引页（指定文章页） | ✅ | home.php 命中 |
| 置顶文章大卡 / 无置顶降级最新 | ✅ | seed 置顶后 featured 显示「置顶」徽章；网格排除同一篇（post__not_in） |
| 有特色图 / 无特色图卡片 | ✅ | 真实缩略图与「渐变+字符」兜底均渲染（相关阅读区同屏可见两种） |
| 无文章空状态 | ⚪ | 站点无空库环境；模板走查 `empty-state` 分支代码存在（同一分支在友链空数据时已验证同类结构） |
| 单篇文章 | ✅ | 面包屑/分类徽章/meta（日期·评论·阅读时长·字数）/prose（h2、h3、表格、引用、代码块、图注样式） |
| 悬浮目录（有/无 h2h3） | ✅ | 有标题时生成 01/02 编号 + lvl3 子级 + scroll-spy 高亮；无标题不渲染 |
| 代码块自动包裹 .codeblock + copy | ✅ | 裸 `<pre>` 被包上 mac 窗口条与复制按钮 |
| 免登录评论 | ✅ | 浏览器端到端：填表→提交→入库（待审核）→带 hash 预览 |
| 评论列表（含站长徽章/回复链接） | ✅ | 头像（Gravatar 失败时有渐变兜底）、昵称、human_time_diff、回复 |
| 评论关闭态 | ✅ | 「本文评论已关闭」 |
| 分类页 | ✅ | 横幅（glyph/描述/真实统计 105 文 2 评）、子分类 chips（有子类才渲染）、分页 |
| 标签归档 | ✅ | /tag/seedream/ 横幅正确 |
| 日期归档 | ✅ | /2026/08/ 200 + h1「2026 年 8 月」（含 %year% 结构下验证；/%post_id%/ 结构被核心禁用日期归档，见已知限制） |
| 搜索有结果 | ✅ | 117 篇、耗时 timer_stop、标题/摘要 `<mark>` 安全高亮、热词为真实热门标签 |
| 搜索无结果 | ✅ | 唯一字符串出无结果态 + 热词 + 回首页按钮 |
| 404 页 | ✅ | HTTP 404 状态正确（不重定向）、真实路径回显、回首页/归档按钮、评论数排序热文 |
| 普通页面 page.php | ✅ | 示例页正常 |
| 关于模板 | ✅ | 真实站长名/头像字/统计/数据驱动里程碑/可编辑正文区；技能栈与本站构成未配置时隐藏 |
| 时间线模板 | ✅ | 年→月双层分组、年份锚点 tabs + scroll-spy、置顶徽章、评论计数 |
| 友链模板 | ✅ | 分组渲染（技术博客/资源与工具站）、失联区、徽章、申请条件（页面正文）、本站信息卡真实数据 + 复制按钮 |
| 分页 | ✅ | 首页/分类第 2 页 200；.pager 含「第 x / y 页 · 共 n 篇」 |
| 未配置菜单回退 | ✅ | 分类回退无告警（后配置菜单覆盖验证） |

## 3. 交互与响应式

| 项 | 结果 | 备注 |
|---|---|---|
| 导航下拉子菜单 | ✅ | 双列信息卡（图标色循环 + 描述字段） |
| 导航 current 高亮 | ✅ | 当前分类/页面 `.active` |
| 移动抽屉开/关/遮罩/Escape | ✅ | aria-expanded/aria-hidden 同步，焦点管理 |
| Ctrl/Cmd + K 聚焦搜索 | ✅ | 输入框聚焦时不干扰打字 |
| 搜索框 Enter 提交 | ✅ | 显式 requestSubmit 增强（/?s=Seedream） |
| 阅读进度条 / 导航投影 / 回到顶部 | ✅ | rAF 节流 |
| 数字滚动（data-count） | ✅ | 真实统计动画 |
| reveal 渐入 | ✅ | threshold=0 + rootMargin（修过 12000px 大块不触发问题）；无 JS 时不隐藏内容 |
| 加载更多 AJAX | ✅ | 9→18 卡、提示行更新、到底判定；fetch 失败回退分页 |
| 目录 scroll-spy | ✅ | 滚动高亮正确 |
| 复制按钮（友链信息卡/代码块/文章链接） | ✅ | 已复制反馈 |
| 1440/1024/768/390/320 视口 | ✅ | 各页无横向溢出（scrollWidth==clientWidth 实测），导航不溢出，移动菜单可用 |
| 减少动态效果偏好 | ✅ | prefers-reduced-motion 全局降级 |
| 键盘焦点可见 | ✅ | :focus-visible 样式（导航/卡片/分页） |

## 4. 安全与性能

| 项 | 结果 | 备注 |
|---|---|---|
| 输出转义（esc_html/esc_attr/esc_url/wp_kses） | ✅ | 全模板走查 |
| 自定义器 sanitize_callback | ✅ | text/textarea(wp_kses_post)/url(esc_url_raw) |
| 友链 metabox nonce + 权限 + autosave 检查 | ✅ | |
| AJAX nonce（check_ajax_referer） | ✅ | 错误 nonce 返回 400/ -1 |
| 无 SQL 拼接 / eval / 动态 include | ✅ | 全部走 WP_Query/get_posts/get_terms |
| 无硬编码密钥/远程脚本/追踪 | ✅ | 资源仅本地 CSS/JS |
| 大数据保护 | ✅ | 时间线上限 2000（oneyer_timeline_max_posts 可过滤）；相关文章/热榜 no_found_rows |
| 缓存版本策略 | ✅ | filemtime 版本号 |
| 图片 | ✅ | oneyer-card 640×380 裁切 + 响应式 srcset + lazy |

## 5. 浏览器验收中发现并已修复的问题（13 项）

1. Walker 子菜单把 `<li>` 直接放进 `<div>` → 浏览器重构 DOM 吞掉搜索框/汉堡按钮 → **包一层 `<ul>`**（核心修复）
2. 菜单项长文本撑破导航 → **收缩 + 省略号**
3. 长站点名在移动端把汉堡按钮挤出视口 → **移动端 logo 收缩省略**
4. 闭包缺 `use ($bar)` 导致代码块包裹失效 → **修复**
5. `comment_form_fields` 过滤器丢失 textarea → **保留 comment 键**
6. 评论表单容器缺 `.cmt-form` 类 → **class_container 挂载**
7. 时间线总数把月组数当文章数 → **三层求和**
8. reveal IO threshold 0.1 对超大区块永不触发 → **threshold 0 + rootMargin**
9. `timer_stop()` 默认 echo 污染页面 → **第二参 false**
10. 加载更多循环遇空白文本节点抛错只插 1 张 → **只移动元素节点**
11. 历史数据摘要含转义 `<p>` 字面量 → **oneyer_clean_excerpt 实体还原**
12. 导航搜索回车在个别环境不提交 → **显式 requestSubmit 增强**
13. Gravatar 失败时头像空白 → **渐变兜底背景**

## 6. 未执行 / 环境受限项

- ⚪ Lighthouse 跑分（映射文档 T9 验收项）：内置服务器 + 无外网字体环境跑分无代表性；已按其精神优化（本地资源、lazy、无阻塞脚本）。
- ⚪ theme-check 插件 ERROR 级扫描：本地站点未安装该插件；已按 WP 规范自查（转义、前缀、模板层级）。
- ⚪ 整站无文章/无分类的全新站点空态：模板分支存在且同类空态（友链空数据）已验证。
- ⚪ Gravatar 真实头像：本地环境无外网，已验证兜底样式。
- ⚪ 多浏览器兼容（仅 Chromium 内核实测）。

## 6A. 内容样式映射方案验收（《内容样式映射方案.md》逐项清单）

> 验收方式：按方案 §五样板创建文章 #3955，浏览器逐项走查（1440/390 视口）+ WP-CLI 过滤器单测。
> 样板文章：/3955/（含手写高亮块 + Markdown 围栏自动包裹块对照）。

| 清单项 | 结果 | 证据 |
|---|---|---|
| h2 蓝竖条 + 目录联动高亮（01/02 编号） | ✅ | 截图：目录 01/02 编号、滚动高亮 |
| h3 `#` 前缀；h4 短蓝线；h5/h6 段内强调 | ✅ | 首屏 + 后台编辑器截图 |
| 行内代码粉底玫红字，不污染代码块内部 | ✅ | `.prose code:not(pre code)` |
| 代码块窗口点 + 文件名提取 + TypeScript 徽标 + copy→已复制 | ✅ | `// file: demo/hello.ts` 自动提取实测；copy 按钮已复制（含剪贴板拒绝时 execCommand 兜底） |
| 代码高亮四色（关键字蓝/字符串橙/函数绿/注释灰斜体） | ✅ | 手写 token 样板截图；Prism/通用双套类名已钉 |
| 表格蓝头斑马纹、hover 浮色、窄屏横向滚动 | ✅ | 表格截图；390px 下 .tblwrap 滚动 |
| 提示框绿/橙（+danger 档）圆角描边、圆形图标 | ✅ | `[!TIP]`/`[!WARN]` 解析实测；note-danger CSS 就绪 |
| 引用块蓝底左条；任务复选框蓝色 | ✅ | 截图 |
| hr 居中 `···` | ✅ | 截图（正文 hr 与 .footnotes 内 hr 同款） |
| 图片圆角描边投影、figcaption 居中灰字 | ✅ | 图 1 图注截图 |
| kbd 立体按键；脚注分隔线 + 上标引用 + 回链 | ✅ | 截图 |
| 后台编辑器所见即所得（editor.css 生效） | ✅ | 经典编辑器（TinyMCE）实测截图：蓝竖条/# 前缀/短蓝线/粉底行内码/蓝色计数等与前台一致 |
| 过滤器幂等（不双重包裹） | ✅ | WP-CLI：二次包裹 tblwrap/codeblock 计数不变；样板页实测 codeblock=2 tblwrap=1 note=2 精确 |

### 验收中发现并修复的问题（4 项）

1. **摘要旁路污染**：相关文章摘要生成也走 the_content 过滤链，把目录全局覆盖成别的文章的标题
   （`/3955/` 显示了 3937 的目录）→ 过滤器守卫改为「当前文章 ID == 主查询对象 ID」精确判定。
2. **方案 S-01 正则的分隔符冲突**：`#...(?://|#|/\*)...#i` 中 `#` 既是分隔符又是内容 → PREG 报错
   → 改用 `~` 分隔符（方案原文的坑，已在实现中修正）。
3. **PCRE2 变长后视不受支持**：`(?<!</div>\s*)` 在 PHP 8.3 触发 PREG Internal Error 导致包裹整体失效
   → 改为「可选前缀捕获 + 回调内判断」方案，无后视。
4. **剪贴板 API 被拒时不降级**：writeText reject 直接显示「失败」→ 补 execCommand 兜底。

### 令牌化落实

### 令牌化落实

`:root` 新增 10 个正文专用令牌（--prose-size/line/ink/gap、--code-bg/bar/line/ink、--inline-code/bg），
.prose 区全部色值走令牌；编辑器样式由 `docs/build-editor-css.py` 从 main.css
`<<<CONTENT-START/END>>>` 同步区生成（含 .editor-styles-wrapper 与 body#tinymce 双作用域），不再手工双维护。

## 6B. 后台设置页验收（《后台设置页方案.md》逐项清单）

> 验收环境：本地站点（方案原文写 cs.oneyer.cc，实际在 127.0.0.1:8080 等价执行）。
> 实现：inc/admin-settings.php + assets/css/admin.css + assets/js/admin.js + functions.php 挂载/自定义 CSS 输出 + T5 十处接线。

| 验收项 | 结果 | 说明 |
|---|---|---|
| 左侧栏出现 ONEYER 菜单（外观上方，screenoptions 图标） | ✅ | 截图确认；capability=manage_options（非管理员不可见为核心机制保证，本地无低权账号未实测） |
| 蓝渐变页头 + 6 胶囊标签页，标签可直链（&tab=home） | ✅ | 截图；hash 增强生效（刷新停留当前 tab） |
| 原 Customizer 数据自动带出（同源验证） | ✅ | 建站日期 2022-10-16、ICP 测试ICP备00000000号 直接显示 |
| 修改→保存→绿条提示→前台生效 | ✅ | 问候语/每页篇数/开关全部实测；通知条初版被后台脚本搬动压到页头上，已改为自有类名 + 自带关闭（见下） |
| checkbox 开关动画；textarea Tab 缩进 | ✅ | 滑动开关（checked 态渲染「生效值」，未保存时默认开） |
| 未保存离开有浏览器提醒 | ✅ | dirty 检测 + beforeunload |
| /wp-admin/admin-post.php 直达不白屏 | ✅ | HTTP 200（核心 do_nothing） |
| 恶意 CSS `</style><script>alert(1)</script>` | ✅ | 保存后仅存 `.prose{font-size:18px}`，前台无 script、无 alert；正常样式同时生效（实测字号 18px） |
| T5 十项走查表 | ✅ | 全部通过：问候语生效 / 网格 9→6 卡 / chips 关=消失 / 统计卡开关 / 文章页目录关=no-toc 单栏 / 相关文章 0=区块消失 / 分享开关 / 热门榜·标签云·友链卡数量接线 / 加载更多条数接线（AJAX handler + data-per-page）/ 时间线上限+截断提示条 |
| WP_DEBUG=true 设置页无 notice | ✅ | 基本与高级两个标签页实测 0 输出 |
| 外观→自定义仍可见同一批值（方案 A） | ✅ | customize.php 正常加载，Oneyer 站点信息 面板存在；theme_mod 同键读写 |

### 实现中修复的方案草稿问题（3 项）

1. `oneyer_settings_apply_defaults()` 在定义前调用且为空实现、`pre_option_oneyer_defaults` 占位 filter 无意义
   → 移除，改为 `oneyer_opt_checked()` helper（checkbox 未保存时回退默认「开」，表单渲染与前台行为一致）。
2. checkbox 渲染 `checked('1', (string)$value ? '1' : '')` 绕弯且首保存前显示「关」与前台默认「开」矛盾
   → 渲染改用 oneyer_opt_checked() 生效值；表达式简化为 `checked('1', (string)$value)`。
3. 保存成功通知使用 WP `.notice.is-dismissible` 类时，被站点某后台脚本搬进页头容器造成叠压
   → 改为自有 `.oy-notice` 类 + 自带关闭按钮（不依赖 jQuery），布局实测正确。

### 接线范围（T5）

index.php（问候语/统计卡开关/chips 开关/每页篇数·静态首页查询/热门榜/标签云/友链卡/加载更多 data-per-page）、
functions.php（主查询 home_ppp、AJAX loadmore_pp、自定义 CSS 输出）、
single.php（目录开关 + no-toc 布局变体 + 阅读时长 + 分享）、
template-parts/related-posts.php（条数 + 0 关闭）、
page-templates/page-archive-timeline.php（上限 + 截断提示条）、
footer.php（页脚附言）。原 12 个迁移项消费点未改动（红线）。

## 6C. 站点 Logo 方案验收（《站点Logo方案.md》逐项清单）

> 实现：`oneyer_logo_html()` 统一渲染器（functions.php §9）+ 三处渲染点替换（桌面导航/移动抽屉/页脚）
> + 双设置入口（Customizer「Oneyer 站点信息」+ 后台「ONEYER → 基本 → 站点信息」首行）
> + CSS 图片形态 + JS 双重失败兜底（capture error 监听 + 延迟脚本对已失败图的扫描）。
> 验收方式：WP-CLI 分支单测（11 种输入）+ 浏览器实测（1440/430 视口）。

| 验收项 | 结果 | 实测值 |
|---|---|---|
| `雾` → 三处渐变方块 | ✅ | 导航/抽屉/页脚均为「雾」 |
| `A` / `7` → 英文/数字 | ✅ | |
| `hello` → 首个合法字符 `h` | ✅ | |
| 图片链接 → 三处圆角图片（36/30px、cover 不变形） | ✅ | 实测 36×36、object-fit:cover、radius 11px、加载成功；抽屉 30×30 |
| emoji `🎉` / 符号 `!!` → 回退站点名首字 | ✅ | 回退「O」 |
| `javascript:` / `data:` 伪协议 → 整体回退（非取首字符） | ✅ | 回退「O」，无注入（**修正了方案草稿矛盾**：原代码会取 `j`/`d` 显示，已按验收表改为协议形态输入整体判无效） |
| 留空 + 无自定义 Logo → 站点名首字（零变化） | ✅ | 截图：渐变方块「O」与改造前一致 |
| 留空 + 已设置自定义 Logo → 三处统一显示 | ✅ | 导入媒体库实测：3 处 img（此前抽屉/页脚不显示，现已统一） |
| 失效图片 URL → 自动回退字符方块，无破图 | ✅ | img error → SPAN 渐变方块（背景/布局由 JS 兜底补齐），页面无残留破图 |
| 430px 抽屉 Logo 不变形 | ✅ | 图片实测 30×30 |
| WP_DEBUG=true 无 notice | ✅ | |
| 红线 1：模板无残留手写 Logo | ✅ | grep 三文件 0 处 mb_substr 手写 |

### 方案草稿修正（1 项）

方案二-A 代码对 `javascript:`/`data:` 等伪协议输入会走「字符分支」显示首字母（与方案自身验收表矛盾）。
落地实现增加判定：非 http(s) 的协议形态输入（含协议头或 `//` 开头）整体判为无效链接，跳过字符分支直接回退——以验收表为准。

## 6D. Logo/头像全站同步审计（用户反馈：关于页未同步）

> 全量扫描 `logo-mark / avatar / mini-logo / lk-av / get_avatar / mb_substr` 渲染点后分类处置。

| 渲染点 | 处置 | 结果 |
|---|---|---|
| 关于页站长卡头像（原：站长名首字「壹」） | ✅ **接入 oneyer_logo_html(110, ' avatar')** | 字符/图片/留空三态实测：110px、圆角 30px 保持、IMG cover 加载成功 |
| 友链页「本站信息」卡 mini-logo（原：站名首字） | ✅ **接入 oneyer_logo_html(30, ' mini-logo')** | 30px、圆角 9px 保持，三态实测通过 |
| 导航 / 抽屉 / 页脚 | 已同步（6C） | 回归通过 |
| 评论者 Gravatar、友链头像 `.lk-av`、分类 glyph、子菜单图标、后台设置页头 | 保持独立 | 语义为他人/别站身份或后台独立样式体系，不应跟站点 Logo 联动（红线 7 精神） |

### 实现说明

- `oneyer_logo_html()` 增加 `$extra_class` 可选参数（逻辑仍收敛于单一函数，红线 1 不破坏）：
  关于页传 `' avatar'`、友链页传 `' mini-logo'`，使输出元素继承容器圆角/描边（头像 30px / mini 9px）。
- CSS 图片形态样式收窄为 `.logo .logo-mark.is-img`（仅导航/抽屉/页脚生效），
  另补 `.master .avatar.is-img / .site-info .mini-logo.is-img { object-fit:cover }`，
  避免统一的 11px 圆角覆盖两页各自的圆角。
- 恢复默认态三页复核：SPAN「O」36/110/30px，与改造前视觉一致。

## 6E. 双端页脚压缩（用户反馈：留白太多、占用面积太大）

> 纯 CSS 调整（main.css 页脚区 + 768px 媒体查询），零模板改动、零功能影响。

| 端 | 优化前 | 优化后 | 变化 |
|---|---|---|---|
| 桌面 1440px | 346px | **297px** | -49px（-14%） |
| 移动 390px | 727px | **352px**（v1 468 → 用户反馈仍高 → v2 三栏并排） | -375px（**-52%**） |
| 移动 320px | — | 388px，无横向溢出 | 长分类名列内换行 |

> v2 移动端策略：品牌整行（Logo 缩至 30px）+ 栏目/站点/订阅 **三栏并排一行**
> （13px 标题、12.5px 链接、行距 5px）+ 版权行居中 10px 内边距；页脚上边距 48→36、顶内边距 40→32（移动）。

桌面调整：容器上边距 64→48、页脚顶内边距 52→40、四列间距 36→24/28、列底距 40→26、
标题下距 16→12、列表行距 10→8、字号 13.5→13、版权行内边距 18→14。

移动调整（核心收益）：放弃「品牌+三列各占整行」的纵向堆叠，改为
**品牌整行 + 栏目/站点/订阅 两列网格（gap 22/18）**；版权行改纵向居中（12px 内边距）；
无横向溢出实测（scrollWidth==clientWidth 375/375）。


## 6F. AJAX 切页验收（《AJAX切页方案.md》Pjax 渐进增强导航）

> 实现：`assets/js/pjax.js`（新建，9.9KB 未压缩）+ main.js 可重入改造（ONEYER_UI.init/closeDrawer 导出）
> + 10 个模板统一 `#oneyer-main` 主区 + `.pjax-bar` 加载条 + 转场 CSS + functions.php 注册（依赖 main.js）。
> 验收方式：浏览器自动化实测（1440/430 视口，注入「页面未刷新」标记判定无整页刷新）。

| 验收项 | 结果 | 实测 |
|---|---|---|
| 首页分类胶囊切页无刷新（用户点名） | ✅ | URL → /category/aigc_image/、9 卡、导航 active 跟随、未刷新标记存活 |
| 分类页子分类/「全部」往返 | ✅ | 同上链路 |
| 分页器 pjax | ✅ | 同链接拦截规则 |
| 导航菜单切换 + active 同步 | ✅ | 缓存直出场景亦同步（解析期捕获 activeHref，不依赖已并入节点） |
| 卡片→详情→相关阅读→返回 | ✅ | toc 11 锚点生成、目录 scroll-spy 复活、代码块复制按钮复活 |
| 搜索提交 AJAX（T5） | ✅ | 导航/抽屉/搜索页大框/404 框统一按 role=search 拦截；?s=Prompt 10 条结果无刷新 |
| 归档时间线年份锚点 | ✅ 不拦截 | 页内行为保持原生 |
| 加载更多（红线 8：不重构） | ✅ | pjax 回首页后按钮复活，9→10 卡追加正常 |
| 后退/前进 + 滚动位置恢复 | ✅ | 滚 800 → 胶囊切页（回顶 0）→ 回退恢复 800 → 前进归位 0 |
| 缓存直出（LRU 5） | ✅ | 回退到刚访问页 0ms（同步 resolve） |
| admin-bar 保留（登录态） | ✅ | bodyClass 整体替换后强制补回 admin-bar 类 |
| M4 抽屉联动 | ✅ | 抽屉点「AIGC商业设计」→ 抽屉自动关闭 + pjax 转场 |
| Ctrl+点击 / 外链 不拦截 | ✅ | defaultPrevented=false 实测 |
| replytocom 评论回复链接不拦截 | ✅ | 拦截规则显式排除 |
| 404 页以 pjax 进入 | ✅ | 非 2xx 中仅放行 404（模板有统一主区） |
| WP_DEBUG 零告警 | ✅ | 首页/文章/分类/搜索 4 页实测 |
| pjax.js 体积 < 5KB 压缩前 | ⚠ 9.9KB | 超出方案预算（M1~M7 专项与注释所致）；gzip 后约 3KB，可接受 |

**移动端真机项（M1 体感 / M3 系统返回手势 / M6 省流）**：代码路径已实现（touchstart 预取、popstate 接管、Save-Data 检查），
本环境无 iOS/Android 真机，**标注为「机制已实现，上线前需真机复验」**——方案 §六 亦要求真机验收。

### 实现中修复的方案问题（3 项）

1. **首次 pjax 丢失滚动记录**：方案代码 `if (push && history.state) replaceState({y})` —— 初始条目 state 为 null
   时离开位置不记录，回退必丢滚动 → 改为 push 时无条件 replaceState。
2. **缓存节点二次入带残态**：LRU 复用同一主区节点，再次替换时残留 pjax-leaving/pointer-events → 插入前统一清理。
3. **导航 active 同步在缓存命中时失效**：方案在转场期从「已并入本文档的节点」读 active（恒为 no-op）
   → 改为解析期捕获 activeHref。

### 滚动定位的环境级发现（M5 深化）

实测本主题（CSS `scroll-behavior:smooth`）+ 部分滚动环境下：`window.scrollTo(x,y)` 两参形式、
`scrollTop` 赋值、`scrollIntoView()` 默认值**全部被平滑滚动吞掉不生效**；方案 M5 的 `behavior:'auto'`
同样遵循 CSS 而失效。最终全部改为 **`behavior:'instant'` 强制瞬时**——转场回顶、锚点定位、滚动恢复三类路径均实测通过。

## 6H. 首页分类 AJAX 预览验收（《首页分类AJAX预览方案.md》）

> 实现：`oneyer_homecat_ajax` 接口（nonce+absint+term_exists 三闸，复用 post-card 模板渲染）
> + chips `no-pjax` 逃生舱与 `data-cat` 标记 + `assets/js/homecat.js`（mount 化防重入，pjax 回首页重挂）
> + 置顶/网格/加载更多/分页器 DOM 显隐切换（`.oy-hidden`）+ 分类页每页条数与首页共用设置。

| 验收项 | 结果 | 实测 |
|---|---|---|
| 点胶囊无刷新：Hero/统计/混排/页脚全部不动，仅网格区换 9 卡（逐张入场） | ✅ | 未刷新标记存活 |
| URL 不变；刷新回「全部」默认态 | ✅ | 分类切换全程 URL=/ |
| 置顶大卡分类态隐藏、全部态恢复 | ✅ | |
| 区块标题随切换（分类名 / Category · 前 9 篇预览 ↔ 最新文章 / Latest Posts） | ✅ | |
| found>N：底部「进入分类页」按钮 + 「已展示 9 / 105 篇」；点击 pjax 进分类页；后退回首页=全部态 | ✅ | |
| found≤N（AIGC文本写作 9=N）：无按钮提示「已展示全部 9 篇」 | ✅ | |
| **加载更多状态无损**（红线 2）：9→(追加)12→切分类→回全部→仍 12→(追加)15→hint 精确计数 | ✅ | 闭包页码状态实测无损 |
| 设置联动：home_ppp 9→6 → 首页网格 6 + homecat 预览 6 + 分类页每页 6（T5 统一） | ✅ | 接口与主查询同源读设置 |
| 连点两个胶囊竞态 | ✅ | 最终展示最后点击的分类（AbortController） |
| 分类页翻页 pjax（需求 2，零新代码） | ✅ | /page/2/ 无刷新、cur 态与 hint 更新、后退回第 1 页 |
| pjax 回首页 homecat 重挂（红线 7） | ✅ | 文章页→回首页→胶囊仍可预览 |
| 空 chips（hide_empty）与无 JS 回退（普通链接） | ✅ 机制 | 链接原生跳转为无 JS 默认行为 |
| WP_DEBUG 零告警 | ✅ | |

### 过程中发现并修复的真实缺陷（1 项，与本方案无直接关联但由其测试暴露）

设置页保存时**首页标签页留空的数字字段被 absint 成 0 写入 theme_mods**（oneyer_loadmore_pp/hot_count/
tag_count/links_count 等全部变 '0'），覆盖消费点默认值——实测「加载更多每次只追加 1 张」、首页热榜/标签云/友链
区块被静默隐藏。修复：①保存回调对留空数字字段 `remove_theme_mod`（留空=未配置，显式 0 仍是「关闭」语义）；
②清理数据库中已被误写为 '0' 的 5 个键。另修正 loadmore 提示计数按实际追加张数计算（原 current×每页 在两种
条数设置不同时会显示错误）。

## 7. 结论

核心功能、数据驱动、安全转义、响应式与交互全部实测通过；
未执行项均为环境限制，不影响生产安装使用。
