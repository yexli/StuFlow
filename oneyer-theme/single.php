<?php
/**
 * 文章详情页。
 * 原型：single.html（article-layout / post / pn-nav / related / comments + toc-wrap）。
 */

get_header();

while ( have_posts() ) :
	the_post();

	$oy_cats    = get_the_category();
	$oy_cat     = $oy_cats ? $oy_cats[0] : null;
	$oy_tags    = get_the_tags();
	$oy_reading  = oneyer_reading();
	$oy_share    = rawurlencode( get_permalink() );
	$oy_title_s  = rawurlencode( get_the_title() );
	$oy_toc_on   = oneyer_opt_checked( 'oneyer_show_toc' );
	$oy_readtime = oneyer_opt_checked( 'oneyer_show_readtime' );
	$oy_share_on = oneyer_opt_checked( 'oneyer_show_share' );
	?>

<div class="article-layout<?php echo $oy_toc_on ? '' : ' no-toc'; ?>" id="oneyer-main">
	<!-- 主栏 -->
	<div>
		<nav class="crumb" aria-label="面包屑">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">首页</a><span class="sep">/</span>
			<?php if ( $oy_cat ) : ?>
			<a href="<?php echo esc_url( get_category_link( $oy_cat ) ); ?>"><?php echo esc_html( $oy_cat->name ); ?></a><span class="sep">/</span>
			<?php endif; ?>
			<span class="cur"><?php the_title(); ?></span>
		</nav>

		<article class="post">
			<?php if ( $oy_cat ) : ?>
			<a class="post-cat" href="<?php echo esc_url( get_category_link( $oy_cat ) ); ?>"><?php echo esc_html( $oy_cat->name ); ?></a>
			<?php endif; ?>
			<h1><?php the_title(); ?></h1>
			<div class="post-meta">
				<span class="m">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="3"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
					<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
				</span>
				<span class="m">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12a8 8 0 0 1-8 8H5l-2 2V12a8 8 0 0 1 8-8h2a8 8 0 0 1 8 8Z"/></svg>
					<?php echo esc_html( number_format_i18n( get_comments_number() ) ); ?> 评论
				</span>
				<?php if ( $oy_readtime ) : ?>
				<span class="m readtime">约 <?php echo esc_html( number_format_i18n( $oy_reading['min'] ) ); ?> 分钟</span>
				<span class="m"><?php echo esc_html( number_format_i18n( $oy_reading['words'] ) ); ?> 字</span>
				<?php endif; ?>
			</div>

			<?php if ( has_post_thumbnail() ) : ?>
			<div class="post-thumb"><?php the_post_thumbnail( 'large', array( 'loading' => 'eager' ) ); ?></div>
			<?php endif; ?>

			<div class="prose">
				<?php the_content(); ?>
			</div>

			<?php
			wp_link_pages( array(
				'before' => '<nav class="oy-page-links" style="margin-top:28px">',
				'after'  => '</nav>',
			) );
			?>

			<div class="post-tags">
				<span class="lb">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20.6 13.4 12 22l-8-8V4a1 1 0 0 1 1-1h9l6.6 6.6a2 2 0 0 1 0 2.8Z"/><circle cx="7.5" cy="7.5" r="1"/></svg>
					标签
				</span>
				<?php
				if ( $oy_tags && ! is_wp_error( $oy_tags ) ) {
					foreach ( $oy_tags as $oy_tag ) {
						echo '<a href="' . esc_url( get_tag_link( $oy_tag ) ) . '">' . esc_html( $oy_tag->name ) . '</a>';
					}
				} else {
					echo '<span class="lb">' . esc_html__( '暂无标签', 'oneyer-stuflow' ) . '</span>';
				}
				?>
				<?php if ( $oy_share_on ) : ?>
				<div class="post-share">
					<a href="<?php echo esc_url( 'https://service.weibo.com/share/share.php?title=' . $oy_title_s . '&url=' . $oy_share ); ?>" target="_blank" rel="noopener noreferrer" title="分享到微博">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20 8.1c.6-.4 1.2-1 1.6-1.7-.6.3-1.2.5-1.9.5a3.3 3.3 0 0 0-5.6 3A9.3 9.3 0 0 1 6.9 7a3.3 3.3 0 0 0 1 4.4c-.5 0-1.1-.2-1.5-.4 0 1.5 1.1 2.8 2.5 3.1-.5.1-1 .1-1.5 0a3.3 3.3 0 0 0 3 2.3A9.3 9.3 0 0 1 3 18.2a13.1 13.1 0 0 0 7.1 2.1c8.5 0 13.2-7.2 12.9-13.6.6-.4 1.2-1 1.6-1.7l-2.6 1.1Z"/></svg>
					</a>
					<button class="share-copy" type="button" title="复制链接" data-share-copy="<?php echo esc_attr( get_permalink() ); ?>">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></svg>
					</button>
				</div>
				<?php endif; ?>
			</div>
		</article>

		<?php
		$oy_prev = get_previous_post();
		$oy_next = get_next_post();
		if ( $oy_prev || $oy_next ) :
			?>
		<nav class="pn-nav">
			<?php if ( $oy_prev ) : ?>
			<a class="pn prev" href="<?php echo esc_url( get_permalink( $oy_prev ) ); ?>">
				<span class="dir">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="m15 6-6 6 6 6"/></svg>
					上一篇
				</span>
				<span class="t"><?php echo esc_html( get_the_title( $oy_prev ) ); ?></span>
			</a>
			<?php else : ?><span class="pn placeholder" aria-hidden="true"></span><?php endif; ?>

			<?php if ( $oy_next ) : ?>
			<a class="pn next" href="<?php echo esc_url( get_permalink( $oy_next ) ); ?>">
				<span class="dir">下一篇
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
				</span>
				<span class="t"><?php echo esc_html( get_the_title( $oy_next ) ); ?></span>
			</a>
			<?php else : ?><span class="pn placeholder" aria-hidden="true"></span><?php endif; ?>
		</nav>
		<?php endif; ?>

		<?php get_template_part( 'template-parts/related-posts' ); ?>

		<?php comments_template(); ?>
	</div>

	<!-- 悬浮目录（设置开启且正文至少一个 h2/h3 时才渲染） -->
	<?php if ( $oy_toc_on && oneyer_has_toc() ) : ?>
	<aside class="toc-wrap">
		<nav class="toc" id="toc" aria-label="文章目录">
			<h5>文章目录</h5>
			<?php
			$oy_sec = 0;
			foreach ( $GLOBALS['oneyer_toc'] as $oy_h ) :
				if ( 2 === $oy_h['lvl'] ) {
					$oy_sec++;
					$oy_prefix = sprintf( '%02d · ', $oy_sec );
				} else {
					$oy_prefix = '';
				}
				?>
			<a href="#<?php echo esc_attr( $oy_h['id'] ); ?>" class="<?php echo esc_attr( 3 === $oy_h['lvl'] ? 'lvl3' : 'lvl2' ); ?>"><?php echo esc_html( $oy_prefix . $oy_h['text'] ); ?></a>
			<?php endforeach; ?>
		</nav>
		<div class="toc-side">
			<h5>本文数据</h5>
			<div class="row">
				<span class="ic">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
				</span>
				<div><b><?php echo esc_html( $oy_reading['min'] ); ?> 分钟</b><span>阅读时长</span></div>
			</div>
			<div class="row">
				<span class="ic">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19V6a2 2 0 0 1 2-2h5v15H4ZM13 19V6a2 2 0 0 1 2-2h5v15h-7Z"/></svg>
				</span>
				<div><b><?php echo esc_html( number_format_i18n( $oy_reading['words'] ) ); ?></b><span>全文字数</span></div>
			</div>
			<div class="row">
				<span class="ic">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12a8 8 0 0 1-8 8H5l-2 2V12a8 8 0 0 1 8-8h2a8 8 0 0 1 8 8Z"/></svg>
				</span>
				<div><b><?php echo esc_html( number_format_i18n( get_comments_number() ) ); ?></b><span>评论</span></div>
			</div>
		</div>
	</aside>
	<?php endif; ?>
</div>

	<?php
endwhile;

get_footer();
