<?php
/**
 * Template Name: 友情链接（Oneyer）
 * Description: 友链卡片墙（数据源：后台「友情链接」CPT）+ 申请条件（页面正文）+ 本站信息卡。
 * 原型：links.html。未添加任何友链时显示空状态；页面正文为空时申请条件区显示默认文案。
 */

get_header();

$oy_all    = oneyer_friend_links( array( 'posts_per_page' => -1 ) );
$oy_normal = array();
$oy_lost   = array();
foreach ( $oy_all as $oy_link ) {
	$oy_status = get_post_meta( $oy_link->ID, 'oy_status', true );
	if ( 'lost' === $oy_status ) {
		$oy_lost[] = $oy_link;
	} else {
		$oy_normal[] = $oy_link;
	}
}

/* 正常友链按「分组」字段归组（未填分组的进入「其他站点」）。 */
$oy_groups = array();
foreach ( $oy_normal as $oy_link ) {
	$oy_group = get_post_meta( $oy_link->ID, 'oy_group', true );
	if ( '' === $oy_group ) {
		$oy_group = '其他站点';
	}
	$oy_groups[ $oy_group ][] = $oy_link;
}

$oy_hint  = oneyer_opt( 'oneyer_links_hint', '蓝底白字「' . mb_substr( get_bloginfo( 'name' ), 0, 1, 'UTF-8' ) . '」圆角方块' );
$oy_has_content = trim( get_the_content() );
?>

<main class="wrap">
	<section class="links-hero">
		<nav class="crumb" aria-label="面包屑">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">首页</a><span class="sep">/</span>
			<span class="cur"><?php the_title(); ?></span>
		</nav>

		<div class="lk-head">
			<div>
				<span class="kicker">Blogroll · 友邻</span>
				<h1>一个人走得快，<span class="hl">一群人走得远</span>。</h1>
				<p>互联网角落里的同行者们。感谢每一次互链，让独立博客这张网还连着。</p>
			</div>
			<div class="lk-stats">
				<div class="lstat"><b><?php echo esc_html( number_format_i18n( count( $oy_normal ) ) ); ?></b><span>友链站点</span></div>
				<div class="lstat"><b><?php echo esc_html( number_format_i18n( count( $oy_lost ) ) ); ?></b><span>失联纪念</span></div>
				<div class="lstat"><b><?php echo esc_html( oneyer_opt( 'oneyer_since', '' ) ? intval( substr( oneyer_opt( 'oneyer_since' ), 0, 4 ) ) : gmdate( 'Y' ) ); ?></b><span>开链元年</span></div>
			</div>
		</div>

		<?php if ( ! $oy_all ) : ?>
			<?php
			get_template_part( 'template-parts/empty-state', null, array(
				'title' => '还没有交换友链',
				'desc'  => '在后台「友情链接 → 添加友链」中录入第一条数据，这里就会亮起来。申请条件见下方。',
				'links' => array( '回首页看看' => home_url( '/' ) ),
			) );
			?>
		<?php else : ?>

			<?php $oy_gi = 0; foreach ( $oy_groups as $oy_gname => $oy_items ) : $oy_gi++; ?>
			<div class="sec-head reveal">
				<span class="bar"></span>
				<div><h2><?php echo esc_html( $oy_gname ); ?></h2><div class="sub"><?php echo esc_html( 1 === $oy_gi ? 'Tech Blogs' : 'Sites' ); ?></div></div>
				<span class="cnt"><b><?php echo esc_html( number_format_i18n( count( $oy_items ) ) ); ?></b> 个站点</span>
			</div>
			<div class="lk-grid reveal">
				<?php
				$oy_li = 0;
				foreach ( $oy_items as $oy_link ) :
					$oy_li++;
					$oy_url    = get_post_meta( $oy_link->ID, 'oy_url', true );
					$oy_desc   = get_post_meta( $oy_link->ID, 'oy_desc', true );
					$oy_badge  = get_post_meta( $oy_link->ID, 'oy_badge', true );
					$oy_av     = get_post_meta( $oy_link->ID, 'oy_avatar', true );
					$oy_avatar = $oy_av ? $oy_av : mb_substr( get_the_title( $oy_link ), 0, 1, 'UTF-8' );
					if ( has_post_thumbnail( $oy_link ) ) {
						$oy_av_html = get_the_post_thumbnail( $oy_link, 'thumbnail', array( 'style' => 'width:52px;height:52px;border-radius:15px;object-fit:cover' ) );
					} else {
						$oy_av_html = '<span class="lk-av" style="background:' . esc_attr( oneyer_link_color( $oy_link->ID, $oy_li - 1 ) ) . '" aria-hidden="true">' . esc_html( $oy_avatar ) . '</span>';
					}
					?>
				<a class="lk" href="<?php echo esc_url( $oy_url ? $oy_url : get_permalink( $oy_link ) ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo $oy_av_html; // phpcs:ignore WordPress.Security.EscapeOutput -- 图片/字符均已转义 ?>
					<div class="lk-tx">
						<div class="lk-name"><?php echo esc_html( get_the_title( $oy_link ) ); ?><?php echo $oy_badge ? '<span class="badge ' . esc_attr( in_array( $oy_badge, array( '常逛', '新链', '五年+' ), true ) ? ( '常逛' === $oy_badge ? 'b-rec' : ( '新链' === $oy_badge ? 'b-new' : 'b-old' ) ) : 'b-old' ) . '">' . esc_html( $oy_badge ) . '</span>' : ''; ?></div>
						<?php if ( $oy_desc ) : ?><div class="lk-desc"><?php echo esc_html( $oy_desc ); ?></div><?php endif; ?>
						<div class="lk-url"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18 15 15 0 0 1 0-18Z"/></svg><span><?php echo esc_html( wp_parse_url( $oy_url ? $oy_url : home_url(), PHP_URL_HOST ) ); ?></span></div>
					</div>
				</a>
				<?php endforeach; ?>
			</div>
			<?php endforeach; ?>

			<?php if ( $oy_lost ) : ?>
			<div class="sec-head reveal">
				<span class="bar"></span>
				<div><h2>失联站点</h2><div class="sub">In Memoriam</div></div>
				<span class="cnt"><b><?php echo esc_html( number_format_i18n( count( $oy_lost ) ) ); ?></b> 个站点 · 保留怀念</span>
			</div>
			<div class="lk-grid reveal">
				<?php foreach ( $oy_lost as $oy_link ) : ?>
				<div class="lk lost">
					<span class="lk-av" style="background:linear-gradient(135deg,#94a3b8,#475569)" aria-hidden="true"><?php echo esc_html( get_post_meta( $oy_link->ID, 'oy_avatar', true ) ?: mb_substr( get_the_title( $oy_link ), 0, 1, 'UTF-8' ) ); ?></span>
					<div class="lk-tx">
						<div class="lk-name"><?php echo esc_html( get_the_title( $oy_link ) ); ?><span class="badge b-lost">已失联</span></div>
						<?php if ( get_post_meta( $oy_link->ID, 'oy_desc', true ) ) : ?><div class="lk-desc"><?php echo esc_html( get_post_meta( $oy_link->ID, 'oy_desc', true ) ); ?></div><?php endif; ?>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

		<?php endif; ?>

		<!-- 申请友链 -->
		<div class="sec-head reveal" id="apply"><span class="bar"></span><div><h2>申请友链</h2><div class="sub">Link Exchange</div></div></div>
		<div class="apply-box reveal">
			<div class="apply-card">
				<h4><span class="bar"></span>申请条件</h4>
				<?php if ( $oy_has_content ) : ?>
					<div class="prose" style="font-size:14.5px;line-height:1.95"><?php the_content(); ?></div>
				<?php else : ?>
				<ul class="req-list">
					<li><span class="ck">✓</span>独立博客 / 个人站点，原创内容为主，稳定运行 3 个月以上</li>
					<li><span class="ck">✓</span>内容健康合法，不含采集站、博彩、擦边与敏感内容</li>
					<li><span class="ck">✓</span>已在贵站添加本站链接（位置不限，首页或友链页均可）</li>
					<li><span class="ck no">✕</span>暂不接受：纯导航站、无原创内容的资源搬运站、无法正常访问的站点</li>
				</ul>
				<p style="font-size:13px;color:var(--ink-3);line-height:1.9;margin-top:18px">满足条件的话，在任意一篇文章评论区留下贵站「名称 + 链接 + 简介 + 头像建议色」，站长看到后 48 小时内处理。会定期巡链，连续 30 天无法访问的站点将移入失联区。</p>
				<?php endif; ?>
			</div>
			<div class="site-info">
				<h4><?php echo oneyer_logo_html( 30, ' mini-logo' ); ?>本站信息（可直接复制）</h4>
				<div class="info-row"><span class="k">站点名称</span><span class="v" data-copy="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"><?php echo esc_html( get_bloginfo( 'name' ) ); ?> <button class="copy" type="button" data-copy="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">复制</button></span></div>
				<div class="info-row"><span class="k">站点地址</span><span class="v" data-copy="<?php echo esc_attr( home_url() ); ?>"><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?> <button class="copy" type="button" data-copy="<?php echo esc_attr( home_url() ); ?>">复制</button></span></div>
				<div class="info-row"><span class="k">站点简介</span><span class="v" data-copy="<?php echo esc_attr( get_bloginfo( 'description' ) ); ?>"><?php echo esc_html( wp_html_excerpt( get_bloginfo( 'description' ), 18, '…' ) ); ?> <button class="copy" type="button" data-copy="<?php echo esc_attr( get_bloginfo( 'description' ) ); ?>">复制</button></span></div>
				<div class="info-row"><span class="k">头像建议</span><span class="v" style="font-family:var(--font-cn)"><?php echo esc_html( $oy_hint ); ?></span></div>
			</div>
		</div>
	</section>
</main>

<?php get_footer(); ?>
