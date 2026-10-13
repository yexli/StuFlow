<?php
/**
 * Oneyer StuFlow 主题启动文件。
 *
 * 结构：
 *  1. 主题功能注册（setup / 资源加载 / 菜单兜底）
 *  2. Customizer 站点信息（备案号、建站日期、首页文案、关于页、友链）
 *  3. 模板辅助函数（统计、卡片媒体、目录、分页、高亮、阅读时长）
 *  4. 导航菜单 Walker 与未配置菜单时的兜底
 *  5. 友链数据模型（CPT + 元数据盒）
 *  6. 首页「加载更多」AJAX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ONEYER_VERSION', '1.0.0' );

/* -------------------------------------------------------------------------
 * 1. 主题功能注册
 * ---------------------------------------------------------------------- */

function oneyer_setup() {
	load_theme_textdomain( 'oneyer-stuflow', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'custom-logo', array(
		'height'      => 72,
		'width'       => 72,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'html5', array(
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
		'style',
		'script',
	) );

	// 卡片媒体比例 16:9.5（宽 640 高 380）。
	add_image_size( 'oneyer-card', 640, 380, true );

	register_nav_menus( array(
		'primary'     => '主导航（桌面）',
		'drawer'      => '移动端抽屉菜单（未配置时复用主导航）',
		'footer-cats' => '页脚 · 栏目列',
		'footer-site' => '页脚 · 站点列',
	) );

	add_editor_style( 'assets/css/editor.css' );
}
add_action( 'after_setup_theme', 'oneyer_setup' );

function oneyer_content_width() {
	$GLOBALS['content_width'] = 780;
}
add_action( 'after_setup_theme', 'oneyer_content_width', 0 );

/**
 * 资源加载。样式与脚本的缓存版本取文件修改时间，便于更新后刷新缓存。
 */
function oneyer_assets() {
	$css = get_theme_file_path( 'assets/css/main.css' );
	wp_enqueue_style( 'oneyer-main', get_theme_file_uri( '/assets/css/main.css' ), array(), (string) filemtime( $css ) );

	// 字体本地化文件仅在存在时加载（assets/fonts/fonts.css）。
	if ( file_exists( get_theme_file_path( 'assets/fonts/fonts.css' ) ) ) {
		wp_enqueue_style( 'oneyer-fonts', get_theme_file_uri( '/assets/fonts/fonts.css' ), array(), (string) filemtime( get_theme_file_path( 'assets/fonts/fonts.css' ) ) );
	}

	$js = get_theme_file_path( 'assets/js/main.js' );
	wp_enqueue_script( 'oneyer-main', get_theme_file_uri( '/assets/js/main.js' ), array(), (string) filemtime( $js ), true );

	wp_localize_script( 'oneyer-main', 'ONEYER', array(
		'ajax'  => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'oneyer' ),
	) );

	// 首帧标记 JS 可用（渐进增强：无 JS 时分页可用、加载更多按钮不显示）。
	wp_add_inline_script( 'oneyer-main', 'document.documentElement.classList.add("js");', 'before' );
}
add_action( 'wp_enqueue_scripts', 'oneyer_assets' );

/**
 * 主查询每页数量：首页/归档统一 9 篇（3×3 网格）。
 */
function oneyer_pre_get_posts( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( $query->is_home() || $query->is_category() || $query->is_tag() || $query->is_author() || $query->is_date() ) {
		$query->set( 'posts_per_page', $query->is_home() ? max( 1, (int) oneyer_opt( 'oneyer_home_ppp', '9' ) ) : 9 );
		$query->set( 'ignore_sticky_posts', true );
	}
	if ( $query->is_search() ) {
		$query->set( 'posts_per_page', 10 );
	}
	// 首页第一页：置顶大卡已单独展示，网格中排除同一篇，避免重复。
	if ( $query->is_home() && ! $query->is_paged() ) {
		$featured_id = oneyer_featured_post_id();
		if ( $featured_id ) {
			$query->set( 'post__not_in', array( $featured_id ) );
		}
	}
}
add_action( 'pre_get_posts', 'oneyer_pre_get_posts' );

/**
 * 置顶大卡文章：优先置顶文章，无则最新一篇。不存在任何文章时返回 0。
 */
function oneyer_featured_post_id() {
	$sticky = get_option( 'sticky_posts' );
	if ( ! empty( $sticky ) && is_array( $sticky ) ) {
		$found = get_posts( array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'post__in'       => $sticky,
			'orderby'        => 'date',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) );
		if ( $found ) {
			return (int) $found[0];
		}
	}
	$latest = get_posts( array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );
	return $latest ? (int) $latest[0] : 0;
}

function oneyer_excerpt_length( $length ) {
	return 46;
}
add_filter( 'excerpt_length', 'oneyer_excerpt_length' );

function oneyer_excerpt_more( $more ) {
	return '…';
}
add_filter( 'excerpt_more', 'oneyer_excerpt_more' );

/* -------------------------------------------------------------------------
 * 2. Customizer：Oneyer 站点信息
 * ---------------------------------------------------------------------- */

function oneyer_customize_register( $wp_customize ) {
	$wp_customize->add_section( 'oneyer_site', array(
		'title'       => 'Oneyer 站点信息',
		'priority'    => 30,
		'description' => '首页文案、页脚备案与关于页配置。留空的项前台自动隐藏。',
	) );

	$fields = array(
		'oneyer_since'      => array( '建站日期（YYYY-MM-DD，用于运行天数）', 'text' ),
		'oneyer_icp'        => array( 'ICP 备案号（如 粤ICP备XXXXXXXX号）', 'text' ),
		'oneyer_police'     => array( '公安备案号', 'text' ),
		'oneyer_logo'       => array( '站点 Logo（单个字符或图片链接；留空用自定义Logo/站点名首字）', 'text' ),
		'oneyer_hero_title' => array( '首页大标题（可用 <span class="hl">、<br>）', 'textarea' ),
		'oneyer_hero_desc'  => array( '首页副标题（留空用站点副标题）', 'textarea' ),
		'oneyer_about_bio'  => array( '关于页 · 站长简介', 'textarea' ),
		'oneyer_about_tags' => array( '关于页 · 站长标签（英文逗号分隔）', 'text' ),
		'oneyer_about_sk'   => array( "关于页 · 技能栈（每行：名称|描述|百分比|渐变色）\n例：PHP / WordPress|主业之外的第二把刀|88|#409eff,#2f6fd6", 'textarea' ),
		'oneyer_about_stk'  => array( "关于页 · 本站构成（每行：名称|内容|徽章）", 'textarea' ),
		'oneyer_wechat_url' => array( '微信公众号链接（订阅卡）', 'url' ),
		'oneyer_telegram'   => array( 'Telegram 频道链接（订阅卡）', 'url' ),
		'oneyer_links_hint' => array( '友链页 · 头像建议文案', 'text' ),
	);

	$defaults = array(
		'oneyer_hero_title' => '记录折腾，<br><span class="hl">一步一个脚印</span>踩亮前方。<span class="caret"></span>',
	);

	foreach ( $fields as $key => $conf ) {
		list( $label, $type ) = $conf;

		$sanitize = 'sanitize_text_field';
		if ( 'textarea' === $type ) {
			$sanitize = 'wp_kses_post';
		} elseif ( 'url' === $type ) {
			$sanitize = 'esc_url_raw';
		}

		$wp_customize->add_setting( $key, array(
			'default'           => isset( $defaults[ $key ] ) ? $defaults[ $key ] : '',
			'sanitize_callback' => $sanitize,
		) );
		$wp_customize->add_control( $key, array(
			'section' => 'oneyer_site',
			'label'   => $label,
			'type'    => $type,
		) );
	}
}
add_action( 'customize_register', 'oneyer_customize_register' );

/**
 * 读取主题自定义器设置。
 */
function oneyer_opt( $key, $fallback = '' ) {
	$value = get_theme_mod( $key, '' );
	return ( '' !== $value && null !== $value ) ? $value : $fallback;
}

/* -------------------------------------------------------------------------
 * 3. 模板辅助函数
 * ---------------------------------------------------------------------- */

/**
 * 卡片/横幅渐变色板：g1~g12，与原型 .grad 完全一致。
 * 返回 12 组渐变，供 oneyer_grad_class() 按 slug 稳定取色。
 */
function oneyer_gradients() {
	return array(
		'g1'  => '#2563eb,#38bdf8',
		'g2'  => '#0f766e,#34d399',
		'g3'  => '#7c3aed,#c084fc',
		'g4'  => '#b45309,#f59e0b',
		'g5'  => '#be123c,#fb7185',
		'g6'  => '#334155,#94a3b8',
		'g7'  => '#166534,#4ade80',
		'g8'  => '#1e40af,#818cf8',
		'g9'  => '#86198f,#e879f9',
		'g10' => '#0c4a6e,#38bdf8',
		'g11' => '#713f12,#facc15',
		'g12' => '#4c1d95,#a78bfa',
	);
}

/**
 * 按分类 slug 稳定映射一个渐变类名（g1~g12）。
 */
function oneyer_grad_class( $slug ) {
	$keys = array_keys( oneyer_gradients() );
	$idx  = function_exists( 'crc32' ) ? abs( crc32( (string) $slug ) ) % count( $keys ) : 0;
	return $keys[ $idx ];
}

/**
 * 分类字符徽标：常见 slug 有专属字符，其余取名称首字符。
 */
function oneyer_glyph( $name, $slug = '' ) {
	$map = array(
		'wordpress' => 'WP',
		'code'      => '</>',
		'javascript' => 'JS',
		'php'       => 'PHP',
		'vue'       => 'VUE',
		'kotlin'    => 'KT',
		'cpp'       => 'C++',
		'c'         => 'C++',
		'python'    => 'PY',
		'rust'      => 'RS',
		'golang'    => 'GO',
		'tool'      => '⚙',
		'tools'     => '⚙',
		'note'      => '^.^',
		'life'      => '^.^',
		'ops'       => '$~',
		'devops'    => '$~',
		'docker'    => '🐳',
		'reverse'   => '0101',
		'assembly'  => '0101',
		'security'  => '🛡',
		'css'       => 'CSS',
		'html'      => '</>',
		'ai'        => 'AI',
		'database'  => '🗄',
		'linux'     => '🐧',
		'nginx'     => 'NX',
	);
	$key = strtolower( (string) $slug );
	if ( '' !== $key && isset( $map[ $key ] ) ) {
		return $map[ $key ];
	}
	return mb_substr( (string) $name, 0, 2, 'UTF-8' );
}

/**
 * 输出文章卡片媒体区：有特色图用图，无图回退「渐变 + 字符」。
 * HTML 在模板中直接使用，图片属性均已转义。
 */
function oneyer_card_media( $post_id = null, $show_views = true ) {
	$post_id  = $post_id ? $post_id : get_the_ID();
	$cats     = get_the_category( $post_id );
	$cat      = $cats ? $cats[0] : null;
	$cat_name = $cat ? $cat->name : '';
	$slug     = $cat ? $cat->slug : '';
	$views    = '';
	if ( $show_views ) {
		$n = (int) get_comments_number( $post_id );
		/* 无可靠阅读量数据源时以真实评论数替代原型的假阅读量。 */
		$views = '<span class="views">💬 ' . esc_html( number_format_i18n( $n ) ) . '</span>';
	}

	echo '<div class="card-media">';
	if ( has_post_thumbnail( $post_id ) ) {
		echo '<div class="card-thumb">';
		echo get_the_post_thumbnail( $post_id, 'oneyer-card', array( 'loading' => 'lazy', 'alt' => the_title_attribute( array( 'echo' => false ) ) ) );
		echo '</div>';
	} else {
		$grad = oneyer_grad_class( $slug ? $slug : (string) $post_id );
		$pair = oneyer_gradients()[ $grad ];
		echo '<div class="grad ' . esc_attr( $grad ) . '" style="background:linear-gradient(135deg,' . esc_attr( $pair ) . ')"></div>';
		echo '<div class="glyph">' . esc_html( oneyer_glyph( get_the_title( $post_id ), $slug ) ) . '</div>';
	}
	if ( $cat_name ) {
		echo '<span class="cat-badge">' . esc_html( $cat_name ) . '</span>';
	}
	echo wp_kses_post( $views );
	echo '</div>';
}

/**
 * 搜索结果缩略区块（.sr-thumb）：与卡片媒体同一套兜底逻辑。
 */
function oneyer_search_thumb( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$cats    = get_the_category( $post_id );
	$slug    = $cats ? $cats[0]->slug : (string) $post_id;
	$grad    = oneyer_grad_class( $slug );
	$pair    = oneyer_gradients()[ $grad ];

	echo '<a class="sr-thumb" href="' . esc_url( get_permalink( $post_id ) ) . '">';
	if ( has_post_thumbnail( $post_id ) ) {
		echo get_the_post_thumbnail( $post_id, 'oneyer-card', array( 'loading' => 'lazy' ) );
	} else {
		echo '<div class="grad ' . esc_attr( $grad ) . '" style="background:linear-gradient(135deg,' . esc_attr( $pair ) . ')"></div>';
		echo '<span>' . esc_html( oneyer_glyph( get_the_title( $post_id ), $slug ) ) . '</span>';
	}
	echo '</a>';
}

/**
 * 全站真实统计（首页 Hero / 关于页共用）。
 * 返回 array( label, value(数字), plus(bool) )，总阅读无数据源以友链数替代并注明。
 */
function oneyer_stats() {
	$posts    = (int) wp_count_posts( 'post' )->publish;
	$comments = (int) wp_count_comments()->approved;
	$links    = wp_count_posts( 'oy_friend_link' );
	$links_n  = $links ? (int) $links->publish : 0;
	$days     = oneyer_days_since();

	$stats = array(
		array( '文章', $posts, false ),
		array( '评论', $comments, true ),
		array( '友链', $links_n, false ),
	);
	if ( $days > 0 ) {
		$stats[] = array( '天运维', $days, false );
	} else {
		$stats[] = array( '标签', (int) wp_count_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false ) ), false );
	}
	return $stats;
}

/**
 * 建站天数：仅在自定义器配置了合法建站日期时返回，否则 0。
 */
function oneyer_days_since() {
	$since = oneyer_opt( 'oneyer_since' );
	if ( ! $since ) {
		return 0;
	}
	$ts = strtotime( $since );
	if ( ! $ts || $ts > time() ) {
		return 0;
	}
	return (int) floor( ( time() - $ts ) / DAY_IN_SECONDS );
}

/**
 * 友链查询（含首页侧栏 4 条的便捷封装）。
 */
function oneyer_friend_links( $args = array() ) {
	$args = wp_parse_args( $args, array(
		'posts_per_page' => -1,
		'status'         => '',
		'group'          => '',
	) );
	$meta_query = array();
	if ( $args['status'] ) {
		$meta_query[] = array( 'key' => 'oy_status', 'value' => $args['status'] );
	}
	if ( $args['group'] ) {
		$meta_query[] = array( 'key' => 'oy_group', 'value' => $args['group'] );
	}
	$query = new WP_Query( array(
		'post_type'      => 'oy_friend_link',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $args['posts_per_page'],
		'orderby'        => 'menu_order date',
		'order'          => 'ASC',
		'no_found_rows'  => true,
		'meta_query'     => $meta_query,
	) );
	return $query->posts;
}

/**
 * 友链头像色：优先取自定义色，否则按序取一组预设渐变。
 */
function oneyer_link_color( $post_id, $index = 0 ) {
	$color = get_post_meta( $post_id, 'oy_color', true );
	if ( $color && preg_match( '/^#[0-9a-fA-F]{3,8}$/', $color ) ) {
		return 'linear-gradient(135deg,' . $color . ',' . $color . ')';
	}
	$palette = array(
		'#409eff,#2f6fd6',
		'#34c77b,#1f8f5a',
		'#e6a23c,#cf8a1d',
		'#8b5cf6,#7c3aed',
		'#f43f5e,#be123c',
		'#0ea5e9,#0369a1',
		'#f59e0b,#b45309',
		'#64748b,#334155',
	);
	return 'linear-gradient(135deg,' . $palette[ $index % count( $palette ) ] . ')';
}

/* ------------------------- 正文结构层过滤器 ------------------------- */

/**
 * 标题锚点/目录收集（S-04）、代码块包裹（S-01）、表格包裹（S-02）、提示框解析（S-03）：
 * 统一位于 inc/content-filters.php（内容样式映射方案）。
 */
require get_template_directory() . '/inc/content-filters.php';
require get_template_directory() . '/inc/admin-settings.php';

/**
 * 当前文章是否可用目录（至少 1 个 h2/h3）。
 */
function oneyer_has_toc() {
	return ! empty( $GLOBALS['oneyer_toc'] );
}

/**
 * 阅读时长（分钟）与字数：正文纯文本按 400 字/分钟估算。
 */
function oneyer_reading( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$words   = mb_strlen( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ), 'UTF-8' );
	return array(
		'min'   => max( 1, (int) ceil( $words / 400 ) ),
		'words' => $words,
	);
}

/**
 * 搜索关键词安全高亮：先转义文本，再以纯文本方式包裹 <mark>。
 * 关键词不进入任何正则特殊上下文（preg_quote 处理）。
 */
function oneyer_highlight( $text, $q = '' ) {
	$text = esc_html( $text );
	$q    = trim( $q );
	if ( '' === $q ) {
		return $text;
	}
	$q_esc = preg_quote( esc_html( $q ), '/' );
	if ( '' === $q_esc ) {
		return $text;
	}
	return preg_replace( '/' . $q_esc . '/iu', '<mark>$0</mark>', $text );
}

/**
 * 摘要清洗：部分历史文章的正文把 <p> 等标签存成了转义文本（&lt;p&gt;），
 * 摘要里会出现字面标签。先还原实体再去标签，最后由调用方转义输出。
 */
function oneyer_clean_excerpt( $excerpt ) {
	$text = wp_strip_all_tags( html_entity_decode( (string) $excerpt, ENT_QUOTES, 'UTF-8' ) );
	return trim( $text );
}

/* ------------------------- 分页 ------------------------- */

/**
 * 原型 .pager 分页器。支持主查询与自定义查询。
 */
function oneyer_pagination( $query = null, $hint_total = null ) {
	global $wp_query;
	$q     = $query ? $query : $wp_query;
	$total = (int) $q->max_num_pages;
	if ( $total < 2 ) {
		return;
	}
	$paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );

	$links = paginate_links( array(
		'total'     => $total,
		'current'   => $paged,
		'prev_next' => true,
		'prev_text' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="m15 6-6 6 6 6"/></svg>',
		'next_text' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="m9 6 6 6-6 6"/></svg>',
		'type'      => 'array',
	) );
	if ( ! $links ) {
		return;
	}

	// 查询对象总条数（搜索页显示「共 n 篇结果」，其余显示文章数）。
	if ( null === $hint_total ) {
		$hint_total = (int) $q->found_posts;
	}

	echo '<nav class="pager" aria-label="分页">';
	foreach ( $links as $link ) {
		if ( false !== strpos( $link, '<span' ) && false !== strpos( $link, 'current' ) ) {
			echo wp_kses_post( str_replace( '<span', '<span class="cur" aria-current="page"', $link ) );
		} elseif ( 0 === strpos( $link, '<span' ) ) {
			echo '<span class="dots">…</span>';
		} elseif ( 0 === strpos( $link, '<a' ) && false !== strpos( $link, 'prev' ) ) {
			echo str_replace( '<a', '<a class="nav-btn" aria-label="上一页"', $link );
		} elseif ( 0 === strpos( $link, '<a' ) && false !== strpos( $link, 'next' ) ) {
			echo str_replace( '<a', '<a class="nav-btn" aria-label="下一页"', $link );
		} else {
			echo wp_kses_post( $link );
		}
	}
	/* translators: 1: 当前页 2: 总页数 3: 总条数 */
	echo '<span class="hint">第 ' . esc_html( $paged ) . ' / ' . esc_html( $total ) . ' 页 · 共 ' . esc_html( number_format_i18n( $hint_total ) ) . ' 篇</span>';
	echo '</nav>';
}

/* ------------------------- 页面按模板查找 ------------------------- */

/**
 * 找到第一个使用指定页面模板的已发布页面，返回其 URL 或 null。
 */
function oneyer_template_page_url( $template ) {
	$pages = get_pages( array(
		'meta_key'   => '_wp_page_template',
		'meta_value' => $template,
		'number'     => 1,
		'hierarchical' => 0,
	) );
	if ( $pages ) {
		return get_permalink( $pages[0]->ID );
	}
	return null;
}

/**
 * 搜索表单封装：$compact = true 时输出抽屉内紧凑样式（.d-search）。
 * get_search_form 不向模板传参，这里用全局标记传递并在模板内复位。
 */
function oneyer_search_form( $compact = false ) {
	$GLOBALS['oneyer_search_compact'] = (bool) $compact;
	get_search_form();
	unset( $GLOBALS['oneyer_search_compact'] );
}

/* -------------------------------------------------------------------------
 * 4. 导航菜单
 * ---------------------------------------------------------------------- */

/**
 * 主导航 Walker：输出原型结构。
 *   <li><a>文字</a></li>
 *   <li><a>父级 <svg.car/></a><div class="submenu"><a><span.ic/><span.tx><b/><span/></span></a>…</div></li>
 * 二级项的图标字符取菜单标题前 2 字符，底色按序循环色板；
 * 描述文字使用菜单项「描述」字段（后台显示选项中勾选「描述」即可填写）。
 */
class Oneyer_Menu_Walker extends Walker_Nav_Menu {

	private $sub_index = 0;

	public function start_lvl( &$output, $depth = 0, $args = null ) {
		$this->sub_index = 0;
		$output .= '<div class="submenu"><ul>';
	}

	public function end_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '</ul></div>';
	}

	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$classes   = empty( $item->classes ) ? array() : (array) $item->classes;
		$has_child = in_array( 'menu-item-has-children', $classes, true );
		$is_cur    = in_array( 'current-menu-item', $classes, true ) || in_array( 'current_page_item', $classes, true );

		$title = apply_filters( 'the_title', $item->title, $item->ID );
		$url   = esc_url( $item->url );

		$output .= '<li>';

		// 二级项输出为双列信息卡：图标色随序号循环，描述取菜单项描述字段。
		if ( 1 === $depth ) {
			$palette = array( '#409eff', '#34c77b', '#e6a23c', '#8b5cf6', '#f43f5e', '#0ea5e9' );
			$color   = $palette[ $this->sub_index % count( $palette ) ];
			$this->sub_index++;

			$output .= '<a href="' . $url . '">';
			$output .= '<span class="ic" style="background:' . esc_attr( $color ) . '">' . esc_html( mb_substr( wp_strip_all_tags( $title ), 0, 3, 'UTF-8' ) ) . '</span>';
			$output .= '<span class="tx"><b>' . esc_html( $title ) . '</b>';
			if ( ! empty( $item->description ) ) {
				$output .= '<span>' . esc_html( $item->description ) . '</span>';
			}
			$output .= '</span></a>';
			return;
		}

		$a_class = $is_cur ? ' class="active"' : '';
		$output .= '<a href="' . $url . '"' . $a_class . '>';
		$output .= esc_html( $title );
		if ( $has_child ) {
			$output .= ' <svg class="car" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>';
		}
		$output .= '</a>';
	}

	public function end_el( &$output, $item, $depth = 0, $args = null ) {
		$output .= '</li>';
	}
}

/**
 * 抽屉菜单 Walker：<a class="d-link">文字 <span.n>计数</span></a>。
 */
class Oneyer_Drawer_Walker extends Walker_Nav_Menu {

	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$is_cur  = in_array( 'current-menu-item', $classes, true ) || in_array( 'current_page_item', $classes, true );
		$output .= '<a class="d-link' . ( $is_cur ? ' on' : '' ) . '" href="' . esc_url( $item->url ) . '">' . esc_html( apply_filters( 'the_title', $item->title, $item->ID ) );
	}

	public function end_el( &$output, $item, $depth = 0, $args = null ) {
		$output .= '</a>';
	}
}

/**
 * 主导航兜底：未配置菜单时输出顶层分类（真实数据，无告警）。
 */
function oneyer_primary_menu() {
	if ( has_nav_menu( 'primary' ) ) {
		wp_nav_menu( array(
			'theme_location' => 'primary',
			'container'      => false,
			'menu_class'     => 'nav-menu',
			'items_wrap'     => '<ul class="%2$s">%3$s</ul>',
			'walker'         => new Oneyer_Menu_Walker(),
			'depth'          => 2,
		) );
		return;
	}

	$cats = get_categories( array( 'number' => 6, 'hide_empty' => true ) );
	if ( ! $cats ) {
		return;
	}
	echo '<ul class="nav-menu">';
	$q = get_queried_object();
	foreach ( $cats as $cat ) {
		$active = ( $q instanceof WP_Term && 'category' === $q->taxonomy && $q->term_id === $cat->term_id ) ? ' class="active"' : '';
		echo '<li><a href="' . esc_url( get_category_link( $cat ) ) . '"' . $active . '>' . esc_html( $cat->name ) . '</a></li>';
	}
	$page = oneyer_template_page_url( 'page-templates/page-about.php' );
	if ( $page ) {
		echo '<li><a href="' . esc_url( $page ) . '">关于本站</a></li>';
	}
	echo '</ul>';
}

/**
 * 抽屉菜单兜底：drawer 位置 → 主导航 → 顶层分类（含计数徽章）。
 */
function oneyer_drawer_menu() {
	if ( has_nav_menu( 'drawer' ) ) {
		wp_nav_menu( array(
			'theme_location' => 'drawer',
			'container'      => false,
			'menu_class'     => '',
			'items_wrap'     => '%3$s',
			'walker'         => new Oneyer_Drawer_Walker(),
			'depth'          => 1,
		) );
		return;
	}
	if ( has_nav_menu( 'primary' ) ) {
		wp_nav_menu( array(
			'theme_location' => 'primary',
			'container'      => false,
			'menu_class'     => '',
			'items_wrap'     => '%3$s',
			'walker'         => new Oneyer_Drawer_Walker(),
			'depth'          => 1,
		) );
		return;
	}

	$cats = get_categories( array( 'number' => 6, 'hide_empty' => true ) );
	foreach ( $cats as $cat ) {
		echo '<a class="d-link" href="' . esc_url( get_category_link( $cat ) ) . '">' . esc_html( $cat->name ) . ' <span class="n">' . esc_html( number_format_i18n( $cat->count ) ) . '</span></a>';
	}
	$page = oneyer_template_page_url( 'page-templates/page-about.php' );
	if ( $page ) {
		echo '<a class="d-link" href="' . esc_url( $page ) . '">关于本站</a>';
	}
}

/* -------------------------------------------------------------------------
 * 5. 友链数据模型（CPT：链接管理器替代，不依赖任何插件）
 *    主题停用后数据仍保留在数据库中，可随时迁移为独立插件。
 * ---------------------------------------------------------------------- */

function oneyer_register_friend_link() {
	register_post_type( 'oy_friend_link', array(
		'labels' => array(
			'name'          => '友情链接',
			'singular_name' => '友情链接',
			'add_new_item'  => '添加友链',
			'edit_item'     => '编辑友链',
		),
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_position'       => 58,
		'menu_icon'           => 'dashicons-admin-links',
		'supports'            => array( 'title', 'thumbnail', 'page-attributes' ),
		'has_archive'         => false,
		'rewrite'             => false,
		'exclude_from_search' => true,
	) );
}
add_action( 'init', 'oneyer_register_friend_link' );

function oneyer_friend_meta_box() {
	add_meta_box( 'oneyer_link_meta', '友链信息', 'oneyer_friend_meta_html', 'oy_friend_link', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'oneyer_friend_meta_box' );

function oneyer_friend_meta_html( $post ) {
	wp_nonce_field( 'oneyer_link_save', 'oneyer_link_nonce' );
	$url    = get_post_meta( $post->ID, 'oy_url', true );
	$desc   = get_post_meta( $post->ID, 'oy_desc', true );
	$group  = get_post_meta( $post->ID, 'oy_group', true );
	$status = get_post_meta( $post->ID, 'oy_status', true );
	$badge  = get_post_meta( $post->ID, 'oy_badge', true );
	$color  = get_post_meta( $post->ID, 'oy_color', true );
	$avatar = get_post_meta( $post->ID, 'oy_avatar', true );

	if ( '' === $status ) {
		$status = 'normal';
	}
	?>
	<style>.oneyer-meta{display:grid;grid-template-columns:160px 1fr;gap:10px 14px;align-items:center;max-width:720px}.oneyer-meta label{font-weight:600}</style>
	<div class="oneyer-meta">
		<label for="oy_url">站点地址</label>
		<input type="url" id="oy_url" name="oy_url" value="<?php echo esc_attr( $url ); ?>" class="regular-text" placeholder="https://example.com">
		<label for="oy_desc">站点简介</label>
		<textarea id="oy_desc" name="oy_desc" rows="2" class="large-text"><?php echo esc_textarea( $desc ); ?></textarea>
		<label for="oy_group">分组</label>
		<input type="text" id="oy_group" name="oy_group" value="<?php echo esc_attr( $group ); ?>" class="regular-text" placeholder="如：技术博客 / 资源与工具站">
		<label for="oy_avatar">头像字符</label>
		<input type="text" id="oy_avatar" name="oy_avatar" value="<?php echo esc_attr( $avatar ); ?>" maxlength="2" class="small-text" placeholder="留空取站点名首字">
		<label for="oy_color">头像底色</label>
		<input type="text" id="oy_color" name="oy_color" value="<?php echo esc_attr( $color ); ?>" class="regular-text" placeholder="#409eff（留空自动配色）">
		<label for="oy_badge">徽章文字</label>
		<input type="text" id="oy_badge" name="oy_badge" value="<?php echo esc_attr( $badge ); ?>" class="regular-text" placeholder="常逛 / 新链 / 五年+（留空无徽章）">
		<label for="oy_status">状态</label>
		<select id="oy_status" name="oy_status">
			<option value="normal" <?php selected( $status, 'normal' ); ?>>正常</option>
			<option value="lost" <?php selected( $status, 'lost' ); ?>>已失联（进入失联区）</option>
		</select>
	</div>
	<?php
}

function oneyer_friend_meta_save( $post_id ) {
	if ( ! isset( $_POST['oneyer_link_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['oneyer_link_nonce'] ), 'oneyer_link_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$fields = array(
		'oy_url'    => 'sanitize_text_field',
		'oy_desc'   => 'sanitize_textarea_field',
		'oy_group'  => 'sanitize_text_field',
		'oy_avatar' => 'sanitize_text_field',
		'oy_badge'  => 'sanitize_text_field',
		'oy_status' => 'sanitize_key',
	);
	foreach ( $fields as $key => $sanitize ) {
		if ( isset( $_POST[ $key ] ) ) {
			update_post_meta( $post_id, $key, call_user_func( $sanitize, wp_unslash( $_POST[ $key ] ) ) );
		}
	}
	$color = isset( $_POST['oy_color'] ) ? sanitize_text_field( wp_unslash( $_POST['oy_color'] ) ) : '';
	if ( $color && preg_match( '/^#[0-9a-fA-F]{3,8}$/', $color ) ) {
		update_post_meta( $post_id, 'oy_color', $color );
	} else {
		delete_post_meta( $post_id, 'oy_color' );
	}
}
add_action( 'save_post_oy_friend_link', 'oneyer_friend_meta_save' );

/* -------------------------------------------------------------------------
 * 6. 首页「加载更多」AJAX
 *    请求下一页主查询并返回渲染好的卡片 HTML；无 JS 时由常规分页兜底。
 * ---------------------------------------------------------------------- */

function oneyer_loadmore_ajax() {
	check_ajax_referer( 'oneyer', 'nonce' );

	$paged = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 0;
	if ( $paged < 2 ) {
		wp_send_json_error( 'bad paged', 400 );
	}

	$query = new WP_Query( array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'paged'               => $paged,
		'posts_per_page'      => max( 1, (int) oneyer_opt( 'oneyer_loadmore_pp', '3' ) ),
		'ignore_sticky_posts' => true,
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
		'html'  => $html,
		'done'  => $paged >= (int) $query->max_num_pages,
		'found' => (int) $query->found_posts,
	) );
}
add_action( 'wp_ajax_oneyer_loadmore', 'oneyer_loadmore_ajax' );
add_action( 'wp_ajax_nopriv_oneyer_loadmore', 'oneyer_loadmore_ajax' );

/* -------------------------------------------------------------------------
 * 7. 评论
 * ---------------------------------------------------------------------- */

/**
 * wp_list_comments 回调：输出原型 .cmt 结构。
 * 头像用 Gravatar；站长徽章判定 = 评论者具有管理权限。
 */
function oneyer_comment( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment;
	$is_webmaster       = $comment->user_id && user_can( $comment->user_id, 'manage_options' );
	$tag                = ( 'div' === $args['style'] ) ? 'div' : 'li';
	?>
	<<?php echo esc_html( $tag ); ?> id="comment-<?php comment_ID(); ?>" <?php comment_class( 'cmt' . ( $depth > 1 ? ' cmt-reply' : '' ), $comment ); ?>>
		<span class="avatar"><?php echo get_avatar( $comment, $depth > 1 ? 68 : 84, '', '', array( 'extra_attr' => 'loading="lazy"' ) ); ?></span>
		<div class="c-main">
			<div class="c-head">
				<?php
				$author_url = get_comment_author_url( $comment );
				if ( $author_url ) {
					echo '<a class="c-name" href="' . esc_url( $author_url ) . '" rel="external nofollow ugc">' . esc_html( get_comment_author( $comment ) ) . '</a>';
				} else {
					echo '<span class="c-name">' . esc_html( get_comment_author( $comment ) ) . '</span>';
				}
				if ( $is_webmaster ) {
					echo '<span class="c-badge">站长</span>';
				}
				?>
				<span class="c-time">
					<?php
					/* translators: %s: 与当前时间的间隔 */
					printf( esc_html__( '%s前', 'oneyer-stuflow' ), esc_html( human_time_diff( get_comment_time( 'U' ), time() ) ) );
					?>
				</span>
				<?php
				comment_reply_link( array_merge( $args, array(
					'depth'     => $depth,
					'max_depth' => isset( $args['max_depth'] ) ? $args['max_depth'] : 2,
					'before'    => '<span class="c-act">',
					'after'     => '</span>',
					'reply_text' => '回复',
				) ) );
				?>
			</div>
			<div class="c-body">
				<?php if ( '0' === $comment->comment_approved ) : ?>
					<p><em><?php esc_html_e( '您的评论正在等待审核。', 'oneyer-stuflow' ); ?></em></p>
				<?php endif; ?>
				<?php comment_text( $comment ); ?>
			</div>
		</div>
	<?php
	// 子级回复由 wp_list_comments 按 depth 自动递归输出。
}

/**
 * 评论表单字段顺序：昵称 / 邮箱 / 网站在前，评论文本框在后（与原型一致）。
 * 注意必须保留核心并入的 'comment' 键（textarea），否则文本框不渲染。
 */
function oneyer_comment_form_fields( $fields ) {
	$comment_field = isset( $fields['comment'] ) ? $fields['comment'] : '';
	unset( $fields['comment'] );

	$new = array();
	foreach ( array( 'author', 'email', 'url' ) as $key ) {
		if ( isset( $fields[ $key ] ) ) {
			$new[ $key ] = $fields[ $key ];
			unset( $fields[ $key ] );
		}
	}
	// 插件注册的其余字段保持原顺序。
	foreach ( $fields as $key => $value ) {
		$new[ $key ] = $value;
	}
	$new['comment'] = $comment_field;
	return $new;
}
add_filter( 'comment_form_fields', 'oneyer_comment_form_fields' );

function oneyer_comment_form_defaults( $defaults ) {
	$defaults['title_reply_before'] = '<h4 class="cmt-reply-title">';
	$defaults['title_reply_after']  = '</h4>';
	$defaults['class_submit']       = 'submit';
	// 挂上原型 .cmt-form 类，复用评论区表单样式。
	if ( isset( $defaults['class_container'] ) ) {
		$defaults['class_container'] = 'comment-respond cmt-form';
	}
	$defaults['submit_button'] = '<button name="%1$s" type="submit" id="%2$s" class="%3$s">%4$s<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="m9 6 6 6-6 6"/></svg></button>';
	return $defaults;
}
add_filter( 'comment_form_defaults', 'oneyer_comment_form_defaults' );

/* -------------------------------------------------------------------------
 * 8. 后台「高级 → 自定义 CSS」输出到页头（双重消毒：保存时已剥标签，输出再剥一次）
 * ---------------------------------------------------------------------- */
add_action( 'wp_head', 'oneyer_custom_css_output', 99 );
function oneyer_custom_css_output() {
	$css = oneyer_opt( 'oneyer_custom_css', '' );
	if ( '' !== $css ) {
		echo "\n<style id=\"oneyer-custom-css\">\n" . wp_strip_all_tags( $css ) . "\n</style>\n";
	}
}

/* -------------------------------------------------------------------------
 * 9. 站点 Logo：单字符 → 渐变方块；图片链接 → 圆角图片；空 → 自定义Logo/站点名首字。
 *    判定优先级与规格见《站点Logo方案.md》，改规则只改本函数。
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
 * 站点 Logo 统一渲染器（桌面导航 / 移动抽屉 / 页脚 / 关于页头像 / 友链页 mini-logo 共用）。
 *
 * @param int    $size        方块尺寸 px（桌面/页脚 36，移动抽屉 30，关于页头像 110）。
 * @param string $extra_class 追加类名（如 ' avatar'、' mini-logo'，用于继承容器圆角/描边）。
 * @return string HTML（span 字符方块 或 img）。
 */
function oneyer_logo_html( $size = 36, $extra_class = '' ) {
	$size = max( 16, absint( $size ) );
	$cls  = ( '' !== $extra_class ) ? ' ' . trim( $extra_class ) : '';
	$raw  = trim( (string) oneyer_opt( 'oneyer_logo', '' ) );
	$name = get_bloginfo( 'name' );

	/* 输入分类：http(s) 图片链接 / 含协议或协议相对形态的无效链接 / 其余（按字符处理） */
	$is_http_url    = preg_match( '#^https?://#i', $raw ) && '' !== esc_url( $raw, array( 'http', 'https' ) );
	$is_bad_url     = ! $is_http_url && '' !== $raw && (bool) preg_match( '#^(?:[a-z][a-z0-9+.\-]*\s*:|//)#i', $raw );

	/* 兜底字符：无效链接一律忽略输入，回退站点名首字符 → 固定 "S" */
	$first_char    = oneyer_logo_first_char( $is_bad_url ? '' : $raw );
	$fallback_char = ( '' !== $first_char ) ? $first_char : oneyer_logo_first_char( $name );
	if ( '' === $fallback_char ) {
		$fallback_char = 'S';
	}

	/* 分支 1：图片链接（仅 http/https，esc_url 白名单防伪协议注入） */
	if ( $is_http_url ) {
		return sprintf(
			'<img class="logo-mark is-img%6$s" src="%1$s" alt="%2$s" style="width:%3$dpx;height:%3$dpx;font-size:%4$dpx" loading="eager" decoding="async" referrerpolicy="no-referrer" data-oneyer-fb-char="%5$s">',
			esc_url( $raw, array( 'http', 'https' ) ),
			esc_attr( $name ),
			$size,
			(int) round( $size * 0.47 ),
			esc_attr( $fallback_char ),
			esc_attr( $cls )
		);
	}

	/* 分支 2：输入含合法字符（填了字，且不是协议形态）→ 字符方块（用输入的字符，非站点名首字） */
	if ( '' !== $first_char ) {
		return sprintf(
			'<span class="logo-mark%3$s"%1$s>%2$s</span>',
			36 === $size ? '' : sprintf( ' style="width:%dpx;height:%dpx;font-size:%dpx"', $size, $size, (int) round( $size * 0.47 ) ),
			esc_html( $first_char ),
			esc_attr( $cls )
		);
	}

	/* 分支 3：输入为空 → WP 自定义 Logo（媒体库，若设置过）→ 统一尺寸输出 */
	$logo_id = (int) get_theme_mod( 'custom_logo' );
	if ( $logo_id ) {
		$logo_src = wp_get_attachment_image_url( $logo_id, 'full' );
		if ( $logo_src ) {
			return sprintf(
				'<img class="logo-mark is-img%5$s" src="%1$s" alt="%2$s" style="width:%3$dpx;height:%3$dpx" loading="eager" decoding="async" data-oneyer-fb-char="%4$s">',
				esc_url( $logo_src ),
				esc_attr( $name ),
				$size,
				esc_attr( $fallback_char ),
				esc_attr( $cls )
			);
		}
	}

	/* 分支 4：兜底 → 站点名首字方块（现状行为） */
	return sprintf(
		'<span class="logo-mark%3$s"%1$s>%2$s</span>',
		36 === $size ? '' : sprintf( ' style="width:%dpx;height:%dpx;font-size:%dpx"', $size, $size, (int) round( $size * 0.47 ) ),
		esc_html( $fallback_char ),
		esc_attr( $cls )
	);
}
