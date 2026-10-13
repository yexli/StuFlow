<?php
/**
 * ONEYER · the_content 结构层过滤器（内容样式映射方案 S-01 ~ S-04）。
 *
 * 优先级链：19 标题锚点 → 20 代码块包裹 → 21 表格包裹 → 22 提示框解析
 *（wpautop / do_shortcode 在 10/11 已完成，天然先于本链）。
 *
 * 幂等红线：代码块/表格包裹不得双重嵌套（靠定宽后视断言保证）。
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 本过滤器链是否应当运行。
 * 精确判定「正在渲染主查询对象本身」：单页里的相关文章摘要、置顶卡等旁路渲染
 * 也会走 the_content 过滤链，若不拦截会把目录全局覆盖成别的文章的标题，
 * 并把代码块/表格/提示框结构带进摘要文本。
 */
function oneyer_content_filters_enabled() {
	if ( ! is_singular() || is_feed() || wp_doing_ajax() ) {
		return false;
	}
	$oy_id = get_the_ID();
	return $oy_id && (int) $oy_id === (int) get_queried_object_id();
}

/* ============================ S-04 标题锚点 + 目录数据 ============================ */

/**
 * 给 h2/h3 注入稳定 id（s-序号；已有 id 则沿用），并收集到全局供 single.php 渲染悬浮目录。
 * 条目结构：array( 'id' => string, 'text' => string, 'lvl' => 2|3 )。
 */
function oneyer_heading_ids( $content ) {
	if ( ! oneyer_content_filters_enabled() || false === stripos( $content, '<h' ) ) {
		return $content;
	}
	$GLOBALS['oneyer_toc'] = array();
	$idx                   = 0;

	$content = preg_replace_callback( '/<(h[23])([^>]*)>(.*?)<\/\1>/is', function ( $m ) use ( &$idx ) {
		$idx++;
		$attrs = $m[2];
		$text  = wp_strip_all_tags( $m[3] );
		if ( '' === trim( $text ) ) {
			return $m[0];
		}
		$id = 's-' . $idx;
		if ( preg_match( '/id=["\']([^"\']+)["\']/i', $attrs, $idm ) ) {
			$id = $idm[1];
		} else {
			$attrs = ' id="' . $id . '"' . $attrs;
		}
		$GLOBALS['oneyer_toc'][] = array(
			'id'   => $id,
			'text' => $text,
			'lvl'  => ( 'h3' === strtolower( $m[1] ) ) ? 3 : 2,
		);
		return '<' . $m[1] . $attrs . '>' . $m[3] . '</' . $m[1] . '>';
	}, $content );

	return $content;
}
add_filter( 'the_content', 'oneyer_heading_ids', 19 );

/* ============================ S-01 代码块包裹 ============================ */

/**
 * 把裸 <pre>（可能带 <code class="language-x">）包进原型 .codeblock 契约结构：
 * 窗口点 + 文件名（首行 // file: 提取）+ 语言徽标 + copy 按钮。
 * 幂等：已被包裹的 <pre>（紧邻 </div> 之后）不再处理。
 * 语言来源：pre 的 class 或内部 code 的 class（language-xx / lang-xx）。
 * 已启用 Prism 等高亮插件时，token class 原样保留，main.css 双套类名均已钉住。
 */
function oneyer_codeblock_wrap( $content ) {
	if ( ! oneyer_content_filters_enabled() || false === stripos( $content, '<pre' ) ) {
		return $content;
	}

	return preg_replace_callback(
		'#(?:</div>\s*)?<pre([^>]*)>(.*?)</pre>#s',
		function ( $m ) {
			// 紧跟 </div>（cb-bar 收尾）的 pre = 手写/已包裹结构，原样保留（幂等）。
			if ( 0 === strpos( $m[0], '</div>' ) ) {
				return $m[0];
			}
			$pre_cls = isset( $m[1] ) ? $m[1] : '';
			$body    = $m[2];

			$lang = '';
			if ( preg_match( '/(?:language|lang)-([\w+#.-]+)/i', $pre_cls, $lm ) ) {
				$lang = strtolower( $lm[1] );
			} elseif ( preg_match( '/<code[^>]*class=["\'][^"\']*(?:language|lang)-([\w+#.-]+)/i', $body, $lm ) ) {
				$lang = strtolower( $lm[1] );
			}

			$file = '';
			if ( preg_match( '~<code[^>]*>\s*(?://|[#%]|/\*)\s*file:\s*([^\s*<]+)~i', $body, $fm ) ) {
				$file = esc_html( $fm[1] );
			}

			$lang_names = array(
				'php' => 'PHP', 'js' => 'JavaScript', 'javascript' => 'JavaScript', 'ts' => 'TypeScript',
				'typescript' => 'TypeScript', 'css' => 'CSS', 'html' => 'HTML', 'json' => 'JSON',
				'bash' => 'Shell', 'sh' => 'Shell', 'shell' => 'Shell', 'sql' => 'SQL',
				'kotlin' => 'Kotlin', 'kt' => 'Kotlin', 'cpp' => 'C++', 'c' => 'C++', 'py' => 'Python',
				'python' => 'Python', 'go' => 'Go', 'rust' => 'Rust', 'vue' => 'Vue', 'yaml' => 'YAML',
				'yml' => 'YAML', 'nginx' => 'Nginx', 'ini' => 'INI', 'md' => 'Markdown',
			);
			$badge      = isset( $lang_names[ $lang ] ) ? $lang_names[ $lang ] : strtoupper( $lang );
			$badge_html = ( '' !== $badge ) ? '<span class="lang-badge">' . esc_html( $badge ) . '</span>' : '';
			$file_html  = ( '' !== $file ) ? '<span class="file">' . $file . '</span>' : '';

			return '<div class="codeblock' . ( '' !== $lang ? ' lang-' . esc_attr( $lang ) : '' ) . '">'
				. '<div class="cb-bar">'
				. '<span class="dot" style="background:#ff5f57"></span><span class="dot" style="background:#febc2e"></span><span class="dot" style="background:#28c840"></span>'
				. $file_html . $badge_html
				. '<button class="copy" type="button">copy</button>'
				. '</div><pre>' . $body . '</pre></div>';
		},
		$content
	);
}
add_filter( 'the_content', 'oneyer_codeblock_wrap', 20 );

/* ============================ S-02 表格包裹 ============================ */

/**
 * 把裸 <table> 包上 .tblwrap（横向滚动 + 圆角描边）。
 * 幂等：仅处理不紧跟在 <div class="tblwrap"> 之后的 <table>；
 * Gutenberg 的 figure.wp-block-table 场景由同一正则覆盖（包裹于 figure 内部）。
 */
function oneyer_table_wrap( $content ) {
	if ( ! oneyer_content_filters_enabled() || false === stripos( $content, '<table' ) ) {
		return $content;
	}

	return preg_replace_callback(
		'#(?:<div class="tblwrap">\s*)?<table\b(.*?)</table>#is',
		function ( $m ) {
			// 已有 .tblwrap 紧邻的 table 原样保留（幂等）。
			if ( 0 === strpos( $m[0], '<div class="tblwrap">' ) ) {
				return $m[0];
			}
			return '<div class="tblwrap"><table' . $m[1] . '</table></div>';
		},
		$content
	);
}
add_filter( 'the_content', 'oneyer_table_wrap', 21 );

/* ============================ S-03 提示框（Callout）解析 ============================ */

/**
 * 把 "> [!TIP] 标题 / > [!WARN] / > [!CAUTION]…" 引用语法（经典编辑器经 wpautop 后的
 * blockquote HTML）翻译为原型 .note 结构。手写 HTML 直接用契约结构即可，不经过本过滤器。
 *
 * 类型映射：TIP/NOTE → note-tip(i)；WARN/WARNING → note-warn(!)；CAUTION/DANGER → note-danger(!)。
 * 标题取法：标记行内 [!TYPE] 之后的文字；若首个 <p> 内以 <br> 换行，换行后的行内内容并入正文。
 * 幂等：输出不含 blockquote，天然不会二次命中。
 */
function oneyer_alerts_parse( $content ) {
	if ( ! oneyer_content_filters_enabled() || false === strpos( $content, '[!' ) ) {
		return $content;
	}

	$types = array(
		'TIP'      => array( 'note-tip', 'i' ),
		'NOTE'     => array( 'note-tip', 'i' ),
		'WARN'     => array( 'note-warn', '!' ),
		'WARNING'  => array( 'note-warn', '!' ),
		'CAUTION'  => array( 'note-danger', '!' ),
		'DANGER'   => array( 'note-danger', '!' ),
	);

	return preg_replace_callback(
		'#<blockquote[^>]*>\s*<p>\s*\[!\s*(TIP|NOTE|WARN|WARNING|CAUTION|DANGER)\s*\]\s*(.*?)</blockquote>#is',
		function ( $m ) use ( $types ) {
			$type   = strtoupper( $m[1] );
			$note   = $types[ $type ];
			$first  = $m[2]; // 首个 <p> 的剩余部分 + 其后所有内容（至 </blockquote> 前）。

			// 拆出首个 <p>：换行前的行视为标题行，换行后的行内内容并入正文。
			$title = '';
			$lead  = $first;
			$tail  = '';
			if ( preg_match( '#^(.*?)(?:<br\s*/?>\s*)(.*)$#is', $first, $split ) ) {
				$lead = $split[1];
				$tail = $split[2];
			}
			$lead_text = trim( wp_strip_all_tags( preg_replace( '#</p>.*$#is', '', $lead ) ) );

			// 标题 = 标记行内 [!TYPE] 之后的纯文本；正文 = <br> 后内容 + 其余段落。
			$title = $lead_text;
			$body  = $tail;

			// 首段闭合：若 title 取自首个 <p> 且该 <p> 未在此前输出闭合，补齐其内容。
			if ( preg_match( '#^(.*?</p>)(.*)$#is', $first, $seg ) ) {
				$first_inner = $seg[1];
				$rest        = $seg[2];
				if ( '' === $body ) {
					// 无 <br>：首个 <p> 整段即标题（或空），正文取其后的段落。
					$body = $rest;
				} else {
					// 有 <br>：首个 <p> 的 <br> 后内容 + 剩余段落。
					$body .= $rest;
				}
			}
			// 兜底：未能拆出 </p>（异常结构）时整段当正文。
			if ( '' === $body && '' === $title ) {
				$body = $first;
			}

			$title_html = ( '' !== $title ) ? '<b>' . esc_html( $title ) . '</b>：' : '';

			// 契约要求内容为 div 内联流：把段落标签压平为 <br> 分隔。
			$body_flat = trim( (string) $body );
			$body_flat = preg_replace( '#^\s*<p[^>]*>#i', '', $body_flat );
			$body_flat = preg_replace( '#</p>\s*$#i', '', $body_flat );
			$body_flat = preg_replace( '#</p>\s*<p[^>]*>#i', '<br>', $body_flat );
			$body_html = trim( wp_kses_post( $body_flat ) );
			if ( '' === $body_html ) {
				$body_html = '&nbsp;';
			}

			return '<div class="note ' . esc_attr( $note[0] ) . '">'
				. '<span class="ic">' . esc_html( $note[1] ) . '</span>'
				. '<div>' . $title_html . $body_html . '</div>'
				. '</div>';
		},
		$content
	);
}
add_filter( 'the_content', 'oneyer_alerts_parse', 22 );
