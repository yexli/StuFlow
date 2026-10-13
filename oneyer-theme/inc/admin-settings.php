<?php
/**
 * Oneyer 后台设置页：顶级菜单 ONEYER + 标签页（后台设置页方案）。
 *
 * 存储：theme_mods（与 Customizer 同源，oneyer_opt() 统一读取，零迁移）。
 * 结构：A 注册表（唯一事实源）→ B 菜单注册 → C 页面渲染 → D 字段渲染器
 *       → E 保存回调（admin-post）→ F 后台资源加载 → G checkbox 默认值 helper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * A. 设置注册表 —— 全部字段的唯一事实源
 *    type: text | textarea | url | number | checkbox
 * ---------------------------------------------------------------------- */
function oneyer_settings_registry() {
	return array(
		'base'     => array(
			'label'  => '基本',
			'icon'   => 'dashicons-admin-site-alt3',
			'groups' => array(
				'建站信息' => array(
					'oneyer_logo'   => array( 'label' => '站点 Logo', 'type' => 'text', 'ph' => '如：雾 或 https://…/logo.png', 'desc' => '单个字符（中文/英文/数字）显示为渐变方块；填图片链接显示为图片；留空回退「外观→自定义Logo」或站点名首字。' ),
					'oneyer_since'  => array( 'label' => '建站日期', 'type' => 'text', 'ph' => '2022-10-16', 'desc' => 'YYYY-MM-DD。用于首页「N 天运维」与页脚版权年份区间。' ),
					'oneyer_icp'    => array( 'label' => 'ICP 备案号', 'type' => 'text', 'ph' => '粤ICP备XXXXXXXX号', 'desc' => '显示在页脚右侧，留空隐藏。' ),
					'oneyer_police' => array( 'label' => '公安备案号', 'type' => 'text', 'ph' => '公安备案 XXXXXXXXXXXX号', 'desc' => '显示在页脚右侧，留空隐藏。' ),
				),
				'页脚'     => array(
					'oneyer_footer_note' => array( 'label' => '页脚附言', 'type' => 'textarea', 'desc' => '页脚品牌区下方的一句话，留空隐藏。可用 <b> <a>。' ),
				),
			),
		),
		'home'     => array(
			'label'  => '首页',
			'icon'   => 'dashicons-admin-home',
			'groups' => array(
				'Hero 区'   => array(
					'oneyer_hero_greeting' => array( 'label' => '顶部问候语', 'type' => 'text', 'desc' => '大标题上方的小字。' ),
					'oneyer_hero_title'    => array( 'label' => '首页大标题', 'type' => 'textarea', 'desc' => '可用 <span class="hl">高亮</span>、<br> 换行、<span class="caret"></span> 打字光标。' ),
					'oneyer_hero_desc'     => array( 'label' => '首页副标题', 'type' => 'textarea', 'desc' => '留空回退「设置→常规」的站点副标题。' ),
					'oneyer_show_stats'    => array( 'label' => '显示统计卡', 'type' => 'checkbox', 'desc' => '大标题右侧的文章/评论/运行天数卡片。' ),
				),
				'文章列表' => array(
					'oneyer_show_chips'  => array( 'label' => '显示分类筛选条', 'type' => 'checkbox', 'desc' => '文章网格上方的分类胶囊。' ),
					'oneyer_home_ppp'    => array( 'label' => '网格每页篇数', 'type' => 'number', 'desc' => '首页主循环 posts_per_page，建议 6/9/12。' ),
					'oneyer_loadmore_pp' => array( 'label' => '加载更多每次追加', 'type' => 'number', 'desc' => 'AJAX 追加的文章条数。' ),
				),
				'下方混排区' => array(
					'oneyer_hot_count'   => array( 'label' => '热门榜条数', 'type' => 'number', 'desc' => '按评论数排序取前 N，0 = 隐藏热门榜。' ),
					'oneyer_tag_count'   => array( 'label' => '标签云个数', 'type' => 'number', 'desc' => '首页标签云显示的标签数，0 = 隐藏标签云。' ),
					'oneyer_links_count' => array( 'label' => '友链卡数量', 'type' => 'number', 'desc' => '首页右侧友链小卡条数。' ),
				),
			),
		),
		'single'   => array(
			'label'  => '文章页',
			'icon'   => 'dashicons-edit-page',
			'groups' => array(
				'正文区' => array(
					'oneyer_show_toc'      => array( 'label' => '显示悬浮目录', 'type' => 'checkbox', 'desc' => '桌面端右侧目录；关闭后纯居中窄栏（移动端本就不显示）。' ),
					'oneyer_show_readtime' => array( 'label' => '显示阅读时长/字数', 'type' => 'checkbox', 'desc' => '元信息行的「约 N 分钟 · N 字」。' ),
				),
				'文末区' => array(
					'oneyer_related_count' => array( 'label' => '相关文章条数', 'type' => 'number', 'desc' => '同分类优先取 N 篇，0 = 关闭该区块。' ),
					'oneyer_show_share'    => array( 'label' => '显示分享按钮', 'type' => 'checkbox', 'desc' => '文末标签行右侧的微博/复制按钮。' ),
				),
			),
		),
		'about'    => array(
			'label'  => '关于页',
			'icon'   => 'dashicons-id-alt',
			'groups' => array(
				'站长信息' => array(
					'oneyer_about_bio'  => array( 'label' => '站长简介', 'type' => 'textarea', 'desc' => '关于页横幅卡片内的一段介绍。' ),
					'oneyer_about_tags' => array( 'label' => '站长标签', 'type' => 'text', 'ph' => 'PHP, TypeScript, Kotlin, 咖啡依赖', 'desc' => '英文逗号分隔，渲染为胶囊。' ),
				),
				'数据展示' => array(
					'oneyer_about_sk'  => array( 'label' => '技能栈', 'type' => 'textarea', 'desc' => "每行：名称|描述|百分比|渐变色。\n例：PHP / WordPress|主业之外的第二把刀|88|#409eff,#2f6fd6" ),
					'oneyer_about_stk' => array( 'label' => '本站构成', 'type' => 'textarea', 'desc' => '每行：名称|内容|徽章。例：服务器|2C2G 香港节点|¥288/年' ),
				),
			),
		),
		'links'    => array(
			'label'  => '订阅与友链',
			'icon'   => 'dashicons-admin-links',
			'groups' => array(
				'订阅方式' => array(
					'oneyer_wechat_url' => array( 'label' => '微信公众号链接', 'type' => 'url', 'desc' => '关于页订阅卡跳转地址。' ),
					'oneyer_telegram'   => array( 'label' => 'Telegram 频道', 'type' => 'url', 'desc' => '关于页订阅卡跳转地址。' ),
					'oneyer_rss_show'   => array( 'label' => '显示 RSS 订阅卡', 'type' => 'checkbox', 'desc' => '关闭则关于页订阅卡相应减少。' ),
				),
				'友链页'   => array(
					'oneyer_links_hint' => array( 'label' => '头像建议文案', 'type' => 'text', 'desc' => '本站信息卡中「头像建议」一行的内容。' ),
				),
			),
		),
		'advanced' => array(
			'label'  => '高级',
			'icon'   => 'dashicons-admin-generic',
			'groups' => array(
				'自定义样式' => array(
					'oneyer_custom_css' => array( 'label' => '自定义 CSS', 'type' => 'textarea', 'desc' => '输出到页头 style 标签，优先级高于主题样式。不需要写 style 标签本身。' ),
				),
				'性能'      => array(
					'oneyer_archives_limit' => array( 'label' => '归档时间线最大拉取', 'type' => 'number', 'desc' => '文章超过该数时时间线截断并提示，防止长查询拖垮页面。' ),
				),
			),
		),
	);
}

/* -------------------------------------------------------------------------
 * B. 菜单注册
 * ---------------------------------------------------------------------- */
add_action( 'admin_menu', 'oneyer_admin_menu' );
function oneyer_admin_menu() {
	add_menu_page(
		'ONEYER 主题设置',          // page_title
		'ONEYER',                   // menu_title
		'manage_options',           // capability
		'oneyer-settings',          // menu_slug
		'oneyer_admin_page_render', // callback
		'dashicons-screenoptions',  // icon
		59                          // position：外观(60) 上方
	);
}

/* -------------------------------------------------------------------------
 * C. 页面渲染
 * ---------------------------------------------------------------------- */
function oneyer_admin_page_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( '权限不足', 'oneyer-stuflow' ) );
	}

	$registry = oneyer_settings_registry();
	$tab      = isset( $_GET['tab'] ) && isset( $registry[ $_GET['tab'] ] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'base'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 只读分流，无状态变更
	$updated  = isset( $_GET['updated'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$base_url = admin_url( 'admin.php?page=oneyer-settings' );
	?>
	<div class="wrap oneyer-admin">
		<div class="oy-header">
			<div class="oy-header__brand">
				<span class="oy-header__logo"><?php echo esc_html( mb_substr( get_bloginfo( 'name' ), 0, 1, 'UTF-8' ) ); ?></span>
				<div>
					<h1>ONEYER 主题设置</h1>
					<p>记录折腾，一步一个脚印踩亮前方。</p>
				</div>
			</div>
			<span class="oy-header__ver">v<?php echo esc_html( ONEYER_VERSION ); ?></span>
		</div>

		<?php if ( $updated ) : ?>
		<div class="oy-notice" role="status">
			<span class="oy-notice__ok">✔</span><p><b>已保存。</b>前台已生效。</p>
			<button type="button" class="oy-notice__x" aria-label="关闭提示">&times;</button>
		</div>
		<?php endif; ?>

		<nav class="oy-tabs">
			<?php foreach ( $registry as $slug => $conf ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'tab', $slug, $base_url ) ); ?>"
					class="oy-tab<?php echo $slug === $tab ? ' is-active' : ''; ?>">
					<span class="dashicons <?php echo esc_attr( $conf['icon'] ); ?>"></span><?php echo esc_html( $conf['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</nav>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="oy-form">
			<input type="hidden" name="action" value="oneyer_save_settings">
			<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">
			<?php wp_nonce_field( 'oneyer_settings', 'oneyer_nonce' ); ?>

			<?php foreach ( $registry[ $tab ]['groups'] as $group_label => $fields ) : ?>
				<section class="oy-card">
					<h2 class="oy-card__title"><?php echo esc_html( $group_label ); ?></h2>
					<?php foreach ( $fields as $key => $conf ) {
						oneyer_admin_field( $key, $conf );
					} ?>
				</section>
			<?php endforeach; ?>

			<div class="oy-savebar">
				<span class="oy-savebar__hint">修改后记得保存</span>
				<?php submit_button( '保存设置', 'primary oy-savebar__btn', 'submit', false ); ?>
			</div>
		</form>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * D. 字段渲染器
 * ---------------------------------------------------------------------- */
function oneyer_admin_field( $key, $conf ) {
	// checkbox 渲染「生效状态」：未保存过时与前台 oneyer_opt_checked 的默认开一致。
	$value = ( 'checkbox' === $conf['type'] ) ? oneyer_opt_checked( $key ) : oneyer_opt( $key, '' );
	$ph    = isset( $conf['ph'] ) ? $conf['ph'] : '';
	?>
	<div class="oy-field oy-field--<?php echo esc_attr( $conf['type'] ); ?>">
		<div class="oy-field__side">
			<label class="oy-field__label" for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $conf['label'] ); ?></label>
			<?php if ( ! empty( $conf['desc'] ) ) : ?>
				<p class="oy-field__desc"><?php echo wp_kses( $conf['desc'], array( 'b' => array(), 'br' => array(), 'code' => array(), 'span' => array( 'class' => array() ) ) ); ?></p>
			<?php endif; ?>
		</div>
		<div class="oy-field__ctrl">
			<?php if ( 'textarea' === $conf['type'] ) : ?>
				<textarea id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>"
					rows="<?php echo 'oneyer_custom_css' === $key ? 10 : 3; ?>"
					placeholder="<?php echo esc_attr( $ph ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
			<?php elseif ( 'checkbox' === $conf['type'] ) : ?>
				<label class="oy-switch">
					<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="0">
					<input type="checkbox" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>"
						value="1" <?php checked( '1', (string) $value ); ?>>
					<span class="oy-switch__track"></span>
				</label>
			<?php elseif ( 'number' === $conf['type'] ) : ?>
				<input type="number" min="0" max="9999" step="1" id="<?php echo esc_attr( $key ); ?>"
					name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>"
					placeholder="<?php echo esc_attr( $ph ); ?>">
			<?php elseif ( 'url' === $conf['type'] ) : ?>
				<input type="url" class="regular-text" id="<?php echo esc_attr( $key ); ?>"
					name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>"
					placeholder="https://">
			<?php else : ?>
				<input type="text" class="regular-text" id="<?php echo esc_attr( $key ); ?>"
					name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>"
					placeholder="<?php echo esc_attr( $ph ); ?>">
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * E. 保存回调（admin-post.php）
 * ---------------------------------------------------------------------- */
add_action( 'admin_post_oneyer_save_settings', 'oneyer_admin_save' );
function oneyer_admin_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( '权限不足', 'oneyer-stuflow' ) );
	}
	check_admin_referer( 'oneyer_settings', 'oneyer_nonce' );

	$registry = oneyer_settings_registry();
	$tab      = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : '';
	if ( ! $tab || ! isset( $registry[ $tab ] ) ) {
		wp_die( esc_html__( '非法标签页', 'oneyer-stuflow' ) );
	}

	foreach ( $registry[ $tab ]['groups'] as $fields ) {
		foreach ( $fields as $key => $conf ) {
			$raw = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';

			switch ( $conf['type'] ) {
				case 'textarea':
					$value = wp_kses_post( $raw );
					break;
				case 'url':
					$value = esc_url_raw( $raw );
					break;
				case 'number':
					$value = (string) min( 9999, absint( $raw ) );
					break;
				case 'checkbox':
					$value = empty( $raw ) ? '0' : '1';
					break;
				default:
					$value = sanitize_text_field( $raw );
			}

			// 自定义 CSS 二次消毒：剥掉一切标签，杜绝 </style> 逃逸。
			if ( 'oneyer_custom_css' === $key ) {
				$value = wp_strip_all_tags( $raw );
			}

			set_theme_mod( $key, $value );
		}
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'    => 'oneyer-settings',
				'tab'     => $tab,
				'updated' => 1,
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}

/* -------------------------------------------------------------------------
 * F. 后台资源（仅本页加载，不污染其他后台页）
 * ---------------------------------------------------------------------- */
add_action( 'admin_enqueue_scripts', 'oneyer_admin_assets' );
function oneyer_admin_assets( $hook ) {
	if ( 'toplevel_page_oneyer-settings' !== $hook ) {
		return;
	}
	wp_enqueue_style( 'oneyer-admin', get_template_directory_uri() . '/assets/css/admin.css', array(), ONEYER_VERSION );
	wp_enqueue_script( 'oneyer-admin', get_template_directory_uri() . '/assets/js/admin.js', array(), ONEYER_VERSION, true );
}

/* -------------------------------------------------------------------------
 * G. checkbox 默认值 helper
 *    未保存过（get_theme_mod 返回 null/''）时回退「开」——与各消费点的默认行为一致。
 *    消费点统一用 oneyer_opt_checked(键)；不改 oneyer_opt() 原函数（红线 1）。
 * ---------------------------------------------------------------------- */
function oneyer_opt_checked( $key ) {
	$raw = get_theme_mod( $key, null );
	return ( null === $raw || '' === $raw ) ? '1' : (string) $raw;
}
