<?php
/**
 * Template Name: 归档时间线（Oneyer）
 * Description: 按年份/月份分组的全站文章时间线。
 * 原型：archive.html。上限 2000 篇（可被 oneyer_timeline_max_posts 过滤器调整），超出部分建议用分类归档分流。
 */

get_header();

/* 上限：后台「ONEYER → 高级」可配（默认 2000），并保留 oneyer_timeline_max_posts 过滤器。 */
$oy_max = (int) apply_filters( 'oneyer_timeline_max_posts', max( 1, (int) oneyer_opt( 'oneyer_archives_limit', '2000' ) ) );

$oy_q = new WP_Query( array(
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'posts_per_page'      => $oy_max,
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
	'orderby'             => 'date',
	'order'               => 'DESC',
) );

/* 按 年 → 月 分组 */
$oy_groups = array();
while ( $oy_q->have_posts() ) {
	$oy_q->the_post();
	$oy_year  = get_the_date( 'Y' );
	$oy_month = get_the_date( 'n' );
	$oy_groups[ $oy_year ][ $oy_month ][] = array(
		'id'    => get_the_ID(),
		'title' => get_the_title(),
		'url'   => get_permalink(),
		'date'  => get_the_date( 'm-d' ),
		'cat'   => get_the_category(),
		'cmts'  => (int) get_comments_number(),
		'sticky'=> is_sticky(),
	);
}
wp_reset_postdata();

/* 统计真实文章数：年 → 月 → 文章三层求和。 */
$oy_total    = 0;
$oy_years    = array_keys( $oy_groups );
$oy_y_counts = array();
foreach ( $oy_groups as $oy_y => $oy_months ) {
	$oy_year_n = 0;
	foreach ( $oy_months as $oy_items ) {
		$oy_year_n += count( $oy_items );
	}
	$oy_y_counts[ $oy_y ] = $oy_year_n;
	$oy_total += $oy_year_n;
}

$oy_month_names = array( 1 => '一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月' );
$oy_comments    = (int) wp_count_comments()->approved;
$oy_newest      = $oy_first = null;
?>

<main class="wrap" id="oneyer-main">
	<section class="arc-hero">
		<nav class="crumb" aria-label="面包屑">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">首页</a><span class="sep">/</span>
			<span class="cur"><?php the_title(); ?></span>
		</nav>

		<div class="arc-head">
			<div>
				<span class="kicker">Archive · 时间轴</span>
				<h1>回望来路，<span class="hl"><?php echo esc_html( number_format_i18n( $oy_total ) ); ?> 篇</span>踩下的脚印。</h1>
				<p>按年份与月份整理的全站文章时间线。想找某个月写了什么、某个系列什么时候开的头，翻这里最快。</p>
			</div>
			<div class="arc-stats">
				<div class="astat"><b><?php echo esc_html( number_format_i18n( $oy_total ) ); ?></b><span>总文章</span></div>
				<div class="astat"><b><?php echo esc_html( number_format_i18n( $oy_comments ) ); ?></b><span>总评论</span></div>
				<div class="astat"><b><?php echo esc_html( count( $oy_years ) ); ?></b><span>写作年份</span></div>
			</div>
		</div>

		<?php if ( $oy_groups ) : ?>
		<?php if ( $oy_total >= $oy_max ) : ?>
		<div class="note note-warn" style="margin:0 0 26px">
			<span class="ic">!</span>
			<div><b>时间线已截断</b>：全站文章较多，本页仅显示最近 <?php echo esc_html( number_format_i18n( $oy_max ) ); ?> 篇。更早的内容请前往分类归档翻阅（上限可在后台「ONEYER → 高级」调整）。</div>
		</div>
		<?php endif; ?>
		<!-- 年份锚点 -->
		<div class="year-tabs" id="yearTabs">
			<?php foreach ( $oy_years as $oy_y ) : ?>
			<a class="ytab" href="#y<?php echo esc_attr( $oy_y ); ?>"><?php echo esc_html( $oy_y ); ?><span class="n"><?php echo esc_html( number_format_i18n( $oy_y_counts[ $oy_y ] ) ); ?> 篇</span></a>
			<?php endforeach; ?>
		</div>

		<!-- 时间线 -->
		<div class="timeline">
			<?php foreach ( $oy_groups as $oy_y => $oy_months ) : ?>
			<div class="year-block reveal" id="y<?php echo esc_attr( $oy_y ); ?>">
				<div class="year-head"><h2><?php echo esc_html( $oy_y ); ?></h2><span class="cnt">共 <b><?php echo esc_html( number_format_i18n( $oy_y_counts[ $oy_y ] ) ); ?></b> 篇</span></div>

				<?php
				krsort( $oy_months );
				foreach ( $oy_months as $oy_m => $oy_items ) :
					?>
				<div class="month-group">
					<div class="mg-head"><?php echo esc_html( $oy_month_names[ (int) $oy_m ] ); ?><span class="n"><?php echo esc_html( number_format_i18n( count( $oy_items ) ) ); ?> 篇</span></div>
					<?php foreach ( $oy_items as $oy_item ) : ?>
					<a class="arc-item" href="<?php echo esc_url( $oy_item['url'] ); ?>">
						<span class="d"><?php echo esc_html( $oy_item['date'] ); ?></span>
						<?php if ( $oy_item['sticky'] ) : ?><span class="pin-flag">置顶</span><?php endif; ?>
						<span class="t"><?php echo esc_html( $oy_item['title'] ); ?></span>
						<?php if ( $oy_item['cat'] ) : ?><span class="cat"><?php echo esc_html( $oy_item['cat'][0]->name ); ?></span><?php endif; ?>
						<span class="cmt"><?php echo esc_html( number_format_i18n( $oy_item['cmts'] ) ); ?></span>
					</a>
					<?php endforeach; ?>
				</div>
				<?php endforeach; ?>
			</div>
			<?php endforeach; ?>
		</div>

		<?php else : ?>
			<?php
			get_template_part( 'template-parts/empty-state', null, array(
				'title' => '还没有可归档的文章',
				'links' => array( '回首页看看' => home_url( '/' ) ),
			) );
			?>
		<?php endif; ?>
	</section>
</main>

<?php get_footer(); ?>
