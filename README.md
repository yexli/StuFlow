<div align="center">

# ONEYER

**现代技术博客 WordPress 经典主题 · 源自 StuFlow 设计系统**

从 8 页高保真静态原型，到开箱即用的 WordPress 主题——毛玻璃导航、渐变卡片流、悬浮目录、时间线归档，一个都不少。

[![Version](https://img.shields.io/badge/version-1.0.0-409eff.svg)](#-安装)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b.svg)](#-环境要求)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)](#-环境要求)
[![License](https://img.shields.io/badge/License-GPL--2.0-cb4737.svg)](#-许可证)
[![Plugin](https://img.shields.io/badge/%E4%BB%98%E8%B4%B9%E6%8F%92%E4%BB%B6-0%20%E4%BE%9D%E8%B5%96-34c77b.svg)](#%EF%B8%8F-%E4%B8%BB%E9%A2%98%E7%89%B9%E6%80%A7)

![ONEYER 主题截图](oneyer-theme/screenshot.png)

</div>

---

## 📖 简介

**ONEYER** 是一套面向个人技术博客的 WordPress 经典主题。设计以内容阅读为核心：
浅色底 + 品牌蓝 `#409eff`、半透明毛玻璃导航、渐变色卡片流、深色窗口式代码块，以及移动端优先的响应式细节。

- **经典主题架构**：原生 PHP 模板 + 原生 JavaScript，**零构建工具、零框架、零付费插件依赖**
- **内容驱动**：所有展示数据（文章/分类/标签/评论/友链）均来自 WordPress 原生能力，无一处写死的演示数据
- **双端打磨**：320px – 1440px 全断点实测，移动端抽屉菜单、三栏紧凑页脚、无横向溢出

## ✨ 主题特性

| 分类 | 特性 |
|---|---|
| **丝滑导航** | Pjax 渐进增强切页（转场淡入淡出 · LRU 缓存直出 · 滚动位置恢复 · hover/tap 预取 · 搜索无刷新）——失败自动回退原生跳转，禁 JS 全站可用 |
| **首页分类预览** | 点分类胶囊**就地无刷新**预览该分类前 N 篇（URL 不变、置顶卡自动隐藏、超量一键进分类页），加载更多状态无损 |
| **内容体验** | 卡片流首页（置顶大卡 + 分类胶囊）· 悬浮目录（h2/h3 自动锚点 + 滚动高亮）· 窗口式代码块（语言徽标 / `// file:` 文件名提取 / 一键复制）· Markdown 表格斑马纹 · `[!TIP]`/`[!WARN]` 彩色提示框 · 相关阅读 · 上下篇导航 |
| **页面模板** | 博客卡片流首页 · 文章页 · 分类/标签/作者/日期归档 · **归档时间线** · **关于页** · **友情链接** · 搜索 · 404 视窗 |
| **后台能力** | 顶级菜单 **ONEYER** 设置页（6 标签页 · 28 个配置项）· 友链管理器（CPT，免插件）· 免登录评论 · 「加载更多」AJAX · 编辑器所见即所得 |
| **站点标识** | 站点 Logo 三级回退（字符方块 → 图片链接 → 自定义 Logo），导航/抽屉/页脚/关于页/友链页 **5 处同步** |
| **工程质量** | 全量输出转义 · 过滤器幂等 · `WP_DEBUG` 零告警 · 原生 JS 渐进增强（无 jQuery）· 尊重 `prefers-reduced-motion` · 缓存友好（filemtime 版本号） |

## 📦 环境要求

| 项 | 要求 |
|---|---|
| WordPress | ≥ 6.0 |
| PHP | ≥ 7.4 |
| 付费插件 | **不需要** |

## 🚀 安装

1. 下载本仓库 `oneyer-theme/` 目录的 ZIP（或 `git clone` 后打包）；
2. WordPress 后台 →「外观 → 主题 → 安装主题 → 上传主题」→ 启用 **ONEYER**；
3. 「设置 → 固定链接」选择非「朴素」结构并保存一次。

命令行方式：`wp theme install oneyer.zip --activate`

## ⚙️ 快速配置（5 步）

| 步骤 | 位置 | 说明 |
|---|---|---|
| ① 站点信息 | 后台 **ONEYER → 基本** | 站点 Logo（单字符或图片链接）、建站日期、备案号——留空项自动隐藏 |
| ② 导航菜单 | 外观 → 菜单 | 未配置时自动回退为分类导航，无告警 |
| ③ 页面模板 | 页面 → 新建 | 「归档时间线 / 关于页 / 友情链接」三个模板按需建页，页脚自动发现入口 |
| ④ 友链数据 | 后台 **友情链接** | 名称/地址/简介/分组/头像/徽章/状态，失联站点单独纪念区 |
| ⑤ 首页文案 | **ONEYER → 首页** | 大标题、问候语、每页篇数、热榜/标签云/友链数量、各类开关 |

> 完整配置对照表见 [`oneyer-theme/README.md`](oneyer-theme/README.md)，部署与回滚见 [`oneyer-theme/docs/DEPLOYMENT.md`](oneyer-theme/docs/DEPLOYMENT.md)。

## ✍️ 正文创作速览

```markdown
## 章节标题            → 自动进入右侧悬浮目录（01/02 编号）
> [!TIP] 实践建议      → 绿色提示框（支持 WARN / CAUTION）
​```ts                 → 深色窗口代码块 + TypeScript 徽标 + 复制按钮
| 表格 | 语法 |        → 蓝头斑马纹表格，窄屏横向滚动
```

所有排版规格见 [`内容样式映射方案.md`](内容样式映射方案.md)，后台编辑器所见即所得。

## 🗂 项目结构

```text
StuFlow/
├── oneyer-theme/               # WordPress 主题（可直接打包安装）
│   ├── style.css functions.php inc/   # 主题头 / 启动 / 设置页 / 内容过滤器
│   ├── *.php page-templates/ template-parts/   # 模板层级
│   ├── assets/                 # main.css · editor.css · main.js · admin.*
│   └── docs/                   # 原型审计 · 测试报告 · 部署文档 · editor.css 构建脚本
├── *.html                      # StuFlow 设计系统静态原型（8 页）
├── WP模板映射文档.md            # 原型 → WP 模板的任务卡片
├── 内容样式映射方案.md          # 正文 Markdown → 前端样式规格
├── 后台设置页方案.md            # ONEYER 设置页规格
└── 站点Logo方案.md              # 站点 Logo 规格
```

## 🧪 质量

- 16 个 PHP 文件语法检查全通过；`WP_DEBUG=true` 下 10 类页面 **0 Notice/Warning**
- 48 项验收清单（功能 / 数据 / 交互 / 安全 / 响应式）逐项实测，记录见
  [`oneyer-theme/docs/TEST_REPORT.md`](oneyer-theme/docs/TEST_REPORT.md)
- 已知限制：`/%post_id%/` 等纯数字固定链接下，日期归档被 WordPress 核心禁用（主题已兼容，含日期的结构不受影响）

## 📄 许可证

[GPL-2.0-or-later](oneyer-theme/LICENSES.md) —— 与 WordPress 相同。
主题不包含任何第三方 JS/CSS 库、远程字体请求、统计或追踪代码。
