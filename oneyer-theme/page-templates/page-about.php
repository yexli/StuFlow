<?php
/**
 * Template Name: 关于页（Oneyer）
 * Description: 站长卡片 + 真实站点统计 + 数据驱动的建站里程碑 + 可编辑正文区。
 * 原型：about.html。统计与里程碑均取真实数据；技能栈/本站构成/订阅卡未配置时自动隐藏。
 */

get_header();

$oy_admin = get_users( array( 'role' => 'Administrator', 'number' => 1 ) );
$oy_owner = $oy_admin ? $oy_admin[0] : null;

$oy_owner_name = $oy_owner ? $oy_owner->display_name : get_bloginfo( 'name' );
$oy_bio        = oneyer_opt( 'oneyer_about_bio', get_bloginfo( 'description' ) );
$oy_tags_raw   = oneyer_opt( 'oneyer_about_tags' );

$oy_stats = oneyer_stats();

/* 技能栈（自定义器行格式：名称|描述|百分比|渐变色，逗号分隔） */
$oy_skills = array();
foreach ( preg_split( '/\r\n|\r|\n/', (string) oneyer_opt( 'oneyer_about_sk' ) ) as $oy_line ) {
	$oy_line = trim( $oy_line );
	if ( '' === $oy_line ) {
		continue;
	}
	$oy_parts = array_map( 'trim', explode( '|', $oy_line ) );
	if ( count( $oy_parts ) >= 3 && is_numeric( rtrim( $oy_parts[2], '%' ) ) ) {
		$oy_skills[] = array(
			'name' => $oy_parts[0],
			'desc' => isset( $oy_parts[1] ) ? $oy_parts[1] : '',
			'pct'  => min( 100, max( 0, intval( rtrim( $oy_parts[2], '%' ) ) ) ),
			'grad' => isset( $oy_parts[3] ) && preg_match( '/^#[0-9a-fA-F]{3,8},#[0-9a-fA-F]{3,8}$/', $oy_parts[3] ) ? $oy_parts[3] : '#409eff,#2f6fd6',
		);
	}
}

/* 本站构成（行格式：名称|内容|徽章） */
$oy_stack = array();
foreach ( preg_split( '/\r\n|\r|\n/', (string) oneyer_opt( 'oneyer_about_stk' ) ) as $oy_line ) {
	$oy_line = trim( $oy_line );
	if ( '' === $oy_line ) {
		continue;
	}
	$oy_parts = array_map( 'trim', explode( '|', $oy_line ) );
	$oy_stack[] = array(
		'key'  => $oy_parts[0],
		'val'  => isset( $oy_parts[1] ) ? $oy_parts[1] : '',
		'note' => isset( $oy_parts[2] ) ? $oy_parts[2] : '',
	);
}

/* 建站里程碑（真实数据）：建站首文 / 讨论最多 / 最新发布。 */
$oy_first  = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'orderby' => 'date', 'order' => 'ASC', 'posts_per_page' => 1, 'no_found_rows' => true ) );
$oy_top    = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'orderby' => 'comment_count', 'order' => 'DESC', 'posts_per_page' => 1, 'no_found_rows' => true ) );
$oy_latest = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC', 'posts_per_page' => 1, 'no_found_rows' => true ) );

$oy_days = oneyer_days_since();

$oy_wechat   = oneyer_opt( 'oneyer_wechat_url' );
$oy_telegram = oneyer_opt( 'oneyer_telegram' );
$oy_links_pg = oneyer_template_page_url( 'page-templates/page-links.php' );

$oy_sub_count = 1 + ( $oy_wechat ? 1 : 0 ) + ( $oy_telegram ? 1 : 0 );
?>

<main class="wrap">
	<div class="main-col">
		<section class="about-hero">
			<nav class="crumb" aria-label="面包屑">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">首页</a><span class="sep">/</span>
				<span class="cur"><?php the_title(); ?></span>
			</nav>

			<!-- 站长卡片（头像与站点 Logo 同源：oneyer_logo_html 统一渲染） -->
			<div class="master">
				<?php echo oneyer_logo_html( 110, ' avatar' ); ?>
				<div class="m-tx">
					<div class="m-kicker">Webmaster · 站长</div>
					<h1><?php echo esc_html( $oy_owner_name ); ?></h1>
					<?php if ( $oy_bio ) : ?><p class="m-bio"><?php echo esc_html( $oy_bio ); ?></p><?php endif; ?>
					<?php if ( $oy_tags_raw ) : ?>
					<div class="m-tags">
						<?php foreach ( array_filter( array_map( 'trim', explode( ',', $oy_tags_raw ) ) ) as $oy_tag_item ) : ?>
						<span><?php echo esc_html( $oy_tag_item ); ?></span>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- 数据统计（真实数据） -->
			<div class="stats-row">
				<?php foreach ( $oy_stats as $oy_stat ) : list( $oy_label, $oy_value, $oy_plus ) = $oy_stat; ?>
				<div class="stat"><b><span data-count="<?php echo esc_attr( $oy_value ); ?>">0</span><?php echo $oy_plus ? '<span style="font-size:14px;color:var(--blue)">+</span>' : ''; ?></b><span><?php echo esc_html( $oy_label ); ?></span></div>
				<?php endforeach; ?>
			</div>

			<!-- 页面正文可编辑区 -->
			<?php if ( trim( get_the_content() ) ) : ?>
			<div class="page-prose prose"><?php the_content(); ?></div>
			<?php endif; ?>

			<?php if ( $oy_first || $oy_top || $oy_latest ) : ?>
			<!-- 建站历程（数据驱动） -->
			<div class="sec-head reveal"><span class="bar"></span><div><h2>建站历程</h2><div class="sub">Milestones</div></div></div>
			<div class="milestones reveal">
				<?php if ( $oy_first ) : $oy_p = $oy_first[0]; ?>
				<div class="ms">
					<div class="ms-date"><?php echo esc_html( get_the_date( 'Y-m', $oy_p ) ); ?></div>
					<h4><a href="<?php echo esc_url( get_permalink( $oy_p ) ); ?>">「<?php echo esc_html( get_the_title( $oy_p ) ); ?>」</a></h4>
					<p>本站第一篇文章上线，一切从这里开始。</p>
				</div>
				<?php endif; ?>
				<?php if ( $oy_top && ( ! $oy_first || $oy_top[0]->ID !== $oy_first[0]->ID ) && (int) get_comments_number( $oy_top[0]->ID ) > 0 ) : $oy_p = $oy_top[0]; ?>
				<div class="ms">
					<div class="ms-date"><?php echo esc_html( get_the_date( 'Y-m', $oy_p ) ); ?></div>
					<h4><a href="<?php echo esc_url( get_permalink( $oy_p ) ); ?>">「<?php echo esc_html( get_the_title( $oy_p ) ); ?>」</a></h4>
					<p>评论区最热闹的一篇：<?php echo esc_html( number_format_i18n( get_comments_number( $oy_p ) ) ); ?> 条讨论。</p>
				</div>
				<?php endif; ?>
				<?php if ( $oy_latest && ( ! $oy_first || $oy_latest[0]->ID !== $oy_first[0]->ID ) ) : $oy_p = $oy_latest[0]; ?>
				<div class="ms">
					<div class="ms-date"><?php echo esc_html( get_the_date( 'Y-m', $oy_p ) ); ?></div>
					<h4><a href="<?php echo esc_url( get_permalink( $oy_p ) ); ?>">「<?php echo esc_html( get_the_title( $oy_p ) ); ?>」</a></h4>
					<p>最新发布，笔耕不辍。</p>
				</div>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<?php if ( $oy_skills ) : ?>
			<!-- 技能栈（自定义器配置） -->
			<div class="sec-head reveal" id="skills"><span class="bar"></span><div><h2>正在使用的技术</h2><div class="sub">Skill Stack</div></div></div>
			<div class="skills reveal">
				<?php foreach ( $oy_skills as $oy_sk ) : ?>
				<div class="skill">
					<div class="s-top">
						<span class="s-ic" style="background:linear-gradient(135deg,<?php echo esc_attr( $oy_sk['grad'] ); ?>)"><?php echo esc_html( mb_substr( $oy_sk['name'], 0, 3, 'UTF-8' ) ); ?></span>
						<div><div class="s-name"><?php echo esc_html( $oy_sk['name'] ); ?></div><div class="s-desc"><?php echo esc_html( $oy_sk['desc'] ); ?></div></div>
					</div>
					<div class="s-bar"><i data-w="<?php echo esc_attr( $oy_sk['pct'] ); ?>%" style="--w:<?php echo esc_attr( $oy_sk['pct'] ); ?>%"></i></div>
					<div class="s-lv"><span><?php echo $oy_sk['pct'] >= 85 ? '精通' : ( $oy_sk['pct'] >= 70 ? '熟练' : '熟悉' ); ?></span><span><?php echo esc_html( $oy_sk['pct'] ); ?>%</span></div>
				</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

			<?php if ( $oy_stack ) : ?>
			<!-- 本站构成（自定义器配置） -->
			<div class="sec-head reveal"><span class="bar"></span><div><h2>本站是怎么搭起来的</h2><div class="sub">How This Site Works</div></div></div>
			<div class="stack-list reveal">
				<?php foreach ( $oy_stack as $oy_st ) : ?>
				<div class="stack-item">
					<span class="k"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg><?php echo esc_html( $oy_st['key'] ); ?></span>
					<span class="v"><?php echo esc_html( $oy_st['val'] ); ?></span>
					<?php if ( $oy_st['note'] ) : ?><span class="note"><?php echo esc_html( $oy_st['note'] ); ?></span><?php endif; ?>
				</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

			<!-- 找到我 / 订阅 -->
			<div class="sec-head reveal"><span class="bar"></span><div><h2>找到我 / 订阅本站</h2><div class="sub">Contact &amp; Subscribe</div></div></div>
			<div class="contact-grid reveal"<?php echo $oy_sub_count < 3 ? ' style="grid-template-columns:repeat(' . intval( max( 1, $oy_sub_count ) ) . ',1fr)"' : ''; ?>>
				<div class="ct">
					<div class="ct-ic" style="background:linear-gradient(135deg,#409eff,#2f6fd6)">
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 11a8 8 0 0 1 16 0"/><rect x="2" y="11" width="4" height="9" rx="2"/><rect x="18" y="11" width="4" height="9" rx="2"/></svg>
					</div>
					<h5>RSS 订阅</h5>
					<p>新文章第一时间推送，不依赖任何平台算法</p>
					<a class="ct-btn" href="<?php echo esc_url( get_bloginfo( 'rss2_url' ) ); ?>">订阅 Feed</a>
				</div>
				<?php if ( $oy_wechat ) : ?>
				<div class="ct">
					<div class="ct-ic" style="background:linear-gradient(135deg,#34c77b,#1f8f5a)">
						<svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9.5 4C5.5 4 2 6.9 2 10.7c0 2.2 1.1 4 2.8 5.3l-.7 2.1 2.4-1.2c.9.2 1.7.4 3 .4h.6a5.5 5.5 0 0 1-.1-1.1c0-3.4 3.2-6 7-6h.4C16.9 6.7 13.6 4 9.5 4Zm-2.6 4a1 1 0 1 1 0 2 1 1 0 0 1 0-2Zm5.2 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2Z"/><path d="M22 16.2c0-3-2.9-5.2-6.2-5.2s-6.2 2.3-6.2 5.2 2.9 5.2 6.2 5.2c.9 0 1.8-.2 2.6-.4l2 1-.6-1.8c1.4-1 2.2-2.4 2.2-4Zm-8.2-1.6a.9.9 0 1 1 0 1.8.9.9 0 0 1 0-1.8Zm4 0a.9.9 0 1 1 0 1.8.9.9 0 0 1 0-1.8Z"/></svg>
					</div>
					<h5>微信公众号</h5>
					<p>每周精选 + 站外随笔，周末推送不打扰</p>
					<a class="ct-btn" href="<?php echo esc_url( $oy_wechat ); ?>" target="_blank" rel="noopener noreferrer">扫码关注</a>
				</div>
				<?php endif; ?>
				<?php if ( $oy_telegram ) : ?>
				<div class="ct">
					<div class="ct-ic" style="background:linear-gradient(135deg,#e6a23c,#cf8a1d)">
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4 20-7Z"/><path d="M22 2 11 13"/></svg>
					</div>
					<h5>Telegram</h5>
					<p>频道同步更新，附构建产物与勘误通知</p>
					<a class="ct-btn" href="<?php echo esc_url( $oy_telegram ); ?>" target="_blank" rel="noopener noreferrer">加入频道</a>
				</div>
				<?php endif; ?>
			</div>

			<!-- 留言指引 -->
			<div class="sec-head reveal"><span class="bar"></span><div><h2>想说点什么？</h2><div class="sub">Say Hi</div></div></div>
			<div class="gb-note reveal">
				<span class="ic">!</span>
				<div>
					本站不设独立留言板——每一篇文章的评论区就是留言板。技术问题、交换友链、指出文中的错误，都欢迎直接在相关文章下留言<?php echo $oy_links_pg ? '，或者前往 <a href="' . esc_url( $oy_links_pg ) . '">友情链接页</a> 查看友链申请方式' : ''; ?>。
				</div>
			</div>
		</section>
	</div>
</main>

<?php get_footer(); ?>
