<?php
/**
 * 搜索结果页。
 * 原型：search.html（删除了原型自检用的「有结果/无结果」切换按钮）。
 */

get_header();

$oy_q     = get_search_query();
$oy_found = (int) ( isset( $GLOBALS['wp_query']->found_posts ) ? $GLOBALS['wp_query']->found_posts : 0 );
$oy_time  = timer_stop( 3, false );

/* 热门搜索词：评论数最多的标签 top5（真实数据）。 */
$oy_hot_tags = get_tags( array( 'orderby' => 'count', 'order' => 'DESC', 'number' => 5, 'hide_empty' => true ) );
?>

<main class="wrap">
	<!-- 搜索头部 -->
	<section class="search-hero">
		<div class="sh-kicker">Search · 全站检索</div>
		<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="big-search">
			<span class="screen-reader-text"><?php esc_html_e( '搜索：', 'oneyer-stuflow' ); ?></span>
			<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
			<input type="search" name="s" value="<?php echo esc_attr( $oy_q ); ?>" placeholder="输入关键词，回车搜索…">
			<button type="submit">搜 索</button>
		</form>
		<?php if ( $oy_hot_tags ) : ?>
		<div class="hotwords">
			<span class="lb">
				<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2s3 4.5 3 8a3 3 0 1 1-6 0c0-1 .5-2 1-3-2 1.5-4 3.8-4 7a6 6 0 0 0 12 0c0-5-4-9-6-12Z"/></svg>
				热门搜索
			</span>
			<?php foreach ( $oy_hot_tags as $oy_i => $oy_ht ) : ?>
			<a href="<?php echo esc_url( get_tag_link( $oy_ht ) ); ?>"><b><?php echo esc_html( $oy_i + 1 ); ?></b><?php echo esc_html( $oy_ht->name ); ?></a>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
	</section>

	<?php if ( have_posts() ) : ?>

	<!-- 结果统计 -->
	<div class="result-bar">
		<span class="rb-t">找到 <b><?php echo esc_html( number_format_i18n( $oy_found ) ); ?></b> 篇与 <span class="q"><?php echo esc_html( $oy_q ); ?></span> 相关的文章</span>
		<span class="rb-time">耗时 <?php echo esc_html( $oy_time ); ?>s</span>
	</div>

	<!-- 结果列表 -->
	<div class="results">
		<?php
		while ( have_posts() ) :
			the_post();
			$oy_cats = get_the_category();
			?>
		<article class="sr">
			<?php oneyer_search_thumb(); ?>
			<div class="sr-main">
				<div class="sr-cat"><span class="dot"></span><?php echo esc_html( $oy_cats ? $oy_cats[0]->name : '' ); ?></div>
				<h3><a href="<?php the_permalink(); ?>"><?php echo wp_kses( oneyer_highlight( get_the_title(), $oy_q ), array( 'mark' => array() ) ); ?></a></h3>
				<p class="sr-exc"><?php echo wp_kses( oneyer_highlight( wp_trim_words( oneyer_clean_excerpt( get_the_excerpt() ), 64, '…' ), $oy_q ), array( 'mark' => array() ) ); ?></p>
				<div class="sr-meta">
					<span><?php echo esc_html( get_the_date() ); ?></span>
					<span class="cmt"><?php echo esc_html( number_format_i18n( get_comments_number() ) ); ?> 评论</span>
				</div>
			</div>
		</article>
		<?php endwhile; ?>
	</div>

	<?php oneyer_pagination( null, $oy_found ); ?>

	<?php else : ?>

	<!-- 无结果态 -->
	<div class="no-result">
		<div class="nr-ic">
			<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/><path d="M8.5 11h5"/></svg>
		</div>
		<h3>没有找到与「<span style="color:var(--blue)"><?php echo esc_html( $oy_q ); ?></span>」相关的文章</h3>
		<p>换个关键词试试？搜索引擎对错别字很敏感，也可以尝试更短、更核心的词，或者去标签页逛逛。</p>
		<?php if ( $oy_hot_tags ) : ?>
		<div class="nr-tips">
			<?php foreach ( $oy_hot_tags as $oy_ht ) : ?>
			<a href="<?php echo esc_url( get_tag_link( $oy_ht ) ); ?>"><?php echo esc_html( $oy_ht->name ); ?></a>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
		<a class="nr-btn" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="m15 6-6 6 6 6"/></svg>
			回首页随便看看
		</a>
	</div>

	<?php endif; ?>

	<?php if ( $oy_hot_tags && have_posts() ) : ?>
	<!-- 相关标签 -->
	<div class="rel-tags">
		<span class="lb">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20.6 13.4 12 22l-8-8V4a1 1 0 0 1 1-1h9l6.6 6.6a2 2 0 0 1 0 2.8Z"/><circle cx="7.5" cy="7.5" r="1"/></svg>
			相关标签
		</span>
		<?php foreach ( $oy_hot_tags as $oy_ht ) : ?>
		<a href="<?php echo esc_url( get_tag_link( $oy_ht ) ); ?>"><?php echo esc_html( $oy_ht->name ); ?> <?php echo esc_html( number_format_i18n( $oy_ht->count ) ); ?></a>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
</main>

<?php get_footer(); ?>
