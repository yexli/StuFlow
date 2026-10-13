<?php
/**
 * 404 页面。由 WordPress 正常返回 404 状态，不重定向。
 * 原型：404.html。热门文章以评论数排序（无可信阅读量数据源时的可解释替代）。
 */

get_header();

$oy_path  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
$oy_links = array( 'page-templates/page-archive-timeline.php' => '翻翻归档' );

$oy_hot = new WP_Query( array(
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'posts_per_page'      => 3,
	'orderby'             => 'comment_count',
	'order'               => 'DESC',
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
) );
/* 整站都没有评论时，评论排序不可解释，退回最新文章。 */
$oy_any_comments = (int) wp_count_comments()->approved;
if ( ! $oy_any_comments ) {
	$oy_hot = new WP_Query( array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 3,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	) );
}
?>

<main class="wrap">
	<section class="nf-hero">
		<div class="nf-left">
			<div class="nf-kicker">
				<span class="ok">✔</span> GET <span class="path"><?php echo esc_html( $oy_path ); ?></span><br>
				<span class="err">✖ 404 Not Found</span> — 页面在重构中走丢了
			</div>
			<h1>这个页面像极了我的发际线——<br><span class="hl">存在过，但找不到了</span>。</h1>
			<p>它可能被改名、被删除，或者只是链接里多打了一个字母。别急着走，下面的搜索框和热门文章大概率能带你去想去的地方。</p>
			<div class="nf-actions">
				<a class="btn-primary" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10"/></svg>
					回到首页
				</a>
				<?php
				$oy_archive_url = oneyer_template_page_url( 'page-templates/page-archive-timeline.php' );
				if ( $oy_archive_url ) :
					?>
				<a class="btn-ghost" href="<?php echo esc_url( $oy_archive_url ); ?>">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="3"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
					翻翻归档
				</a>
				<?php endif; ?>
			</div>
			<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="nf-search">
				<span class="screen-reader-text"><?php esc_html_e( '搜索：', 'oneyer-stuflow' ); ?></span>
				<input type="search" name="s" placeholder="或者，直接搜你想找的内容…">
				<button type="submit" aria-label="搜索">
					<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
				</button>
			</form>
		</div>

		<div class="nf-visual" aria-hidden="true">
			<div class="nv-bar">
				<span class="b" style="background:#ff5f57"></span><span class="b" style="background:#febc2e"></span><span class="b" style="background:#28c840"></span>
				<span class="title">error — 404</span>
			</div>
			<div class="nv-body">
				<div class="nv-404">4<span class="zero"><svg width="118" height="118" viewBox="0 0 100 100" fill="none">
					<circle cx="50" cy="50" r="38" stroke="rgba(255,255,255,.95)" stroke-width="9" stroke-dasharray="10 12" stroke-linecap="round"/>
					<circle cx="50" cy="50" r="14" fill="rgba(255,255,255,.95)"/>
				</svg></span>4</div>
				<div class="nv-msg">page not found<span class="cursor"></span></div>
			</div>
		</div>
	</section>

	<?php if ( $oy_hot->have_posts() ) : ?>
	<div class="section-head">
		<span class="bar" aria-hidden="true"></span>
		<div><h3>大家都在看</h3><div class="sub">Hot Posts</div></div>
	</div>
	<div class="grid">
		<?php
		while ( $oy_hot->have_posts() ) :
			$oy_hot->the_post();
			get_template_part( 'template-parts/post-card' );
		endwhile;
		wp_reset_postdata();
		?>
	</div>
	<?php endif; ?>
</main>

<?php get_footer(); ?>
