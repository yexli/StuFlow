#!/usr/bin/env python3
"""
editor.css 构建脚本（内容样式映射方案 · 同步层）

从 assets/css/main.css 的 <<<CONTENT-START>>> ~ <<<CONTENT-END>>> 标记区
提取 .prose 内容样式，生成两套作用域副本写入 assets/css/editor.css：
  1. .editor-styles-wrapper 前缀 —— Gutenberg 区块编辑器画布
  2. body#tinymce.wp-editor 前缀 —— 经典编辑器（TinyMCE iframe）

用法：python docs/build-editor-css.py
红线：editor.css 为生成物，请勿手工编辑；改样式只改 main.css 后重跑本脚本。
"""
import pathlib
import re
import sys

root = pathlib.Path(__file__).resolve().parents[1]
main = root / "assets" / "css" / "main.css"
out = root / "assets" / "css" / "editor.css"

css = main.read_text(encoding="utf-8")
# 起点跳过 START 标记所在注释块的剩余部分；终点为独立单行结束标记。
m = re.search(r"<<<CONTENT-START>>>.*?\*/\n(.*?)\n/\* <<<CONTENT-END>>> \*/", css, re.S)
if not m:
    sys.exit("未找到 <<<CONTENT-START>>> / <<<CONTENT-END>>> 标记区")

body = m.group(1).strip()


def scope(text: str, prefix: str) -> str:
    text = text.replace(".prose{", prefix + "{")
    text = text.replace(".prose ", prefix + " ")
    return text


banner = (
    "/*\n"
    " * ⚠ 自动生成文件 —— 由 docs/build-editor-css.py 从 main.css 内容同步区生成。\n"
    " * 请勿手工编辑：修改排版请改 assets/css/main.css（<<<CONTENT-START>>> 标记区），\n"
    " * 然后在本目录执行：python build-editor-css.py\n"
    " */\n"
)

result = (
    banner
    + "\n/* ===== 作用域 A：Gutenberg 区块编辑器画布 ===== */\n"
    + scope(body, ".editor-styles-wrapper")
    + "\n\n/* ===== 作用域 B：经典编辑器（TinyMCE iframe，body 即正文容器） ===== */\n"
    + scope(body, "body#tinymce.wp-editor")
    + "\n"
)

out.write_text(result, encoding="utf-8")
print(f"已生成 {out}（{len(result)} 字符）")
