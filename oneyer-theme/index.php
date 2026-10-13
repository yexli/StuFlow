<?php
/**
 * 首页 / 文章索引（模板层级的最终兜底）。
 * 「设置 → 阅读」为「最新文章」时由 front-page.php/home.php 走入本文件，
 * 网格使用主查询；若为「静态页面」，本文件改用显式查询并以 /page/N/ 分页。
 * 原型：index.html。
 */

get_header();

$oy_is_static_front = is_front_page() && ! is_home();
$oy_paged           = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
$oy_home_ppp        = max( 1, (int) oneyer_opt( 'oneyer_home_ppp', '9' ) );

if ( $oy_is_static_front ) {
	// 静态首页：主查询是页面本身，卡片流改用显式查询。
	$oy_featured_id = oneyer_featured_post_id();
	$oy_query       = new WP_Query( array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'paged'               => $oy_paged,
		'posts_per_page'      => $oy_home_ppp,
		'ignore_sticky_posts' => true,
		'post__not_in'        => ( 1 === $oy_paged && $oy_featured_id ) ? array( $oy_featured_id ) : array(),
	) );
} else {
	global $wp_query;
	$oy_query = $wp_query; // 主查询：pre_get_posts 已做置顶排除与每页数量。
}

$oy_found    = (int) $oy_query->found_posts;
$oy_max      = (int) $oy_query->max_num_pages;
$oy_featured = ( ! $oy_is_static_front || 1 === $oy_paged ) ? oneyer_featured_post_id() : 0;
?>

<main class="wrap">
	<!-- Hero -->
	<section class="hero">
		<div class="hero-top">
			<div>
				<div class="hero-greet"><?php echo esc_html( oneyer_opt( 'oneyer_hero_greeting', 'Hello, Coder' ) ); ?></div>
				<h1><?php echo wp_kses( oneyer_opt( 'oneyer_hero_title', '记录折腾，<br><span class="hl">一步一个脚印</span>踩亮前方。<span class="caret"></span>' ), array( 'br' => array(), 'span' => array( 'class' => array() ) ) ); ?></h1>
				<p><?php echo esc_html( oneyer_opt( 'oneyer_hero_desc', get_bloginfo( 'description' ) ) ); ?></p>
			</div>
			<?php if ( oneyer_opt_checked( 'oneyer_show_stats' ) ) : ?>
			<div class="hero-stats">
				<?php foreach ( oneyer_stats() as $oy_stat ) : list( $oy_label, $oy_value, $oy_plus ) = $oy_stat; ?>
				<div class="hstat">
					<b><span data-count="<?php echo esc_attr( $oy_value ); ?>">0</span><?php echo $oy_plus ? '<span class="plus">+</span>' : ''; ?></b>
					<span><?php echo esc_html( $oy_label ); ?></span>
				</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>

		<!-- 分类胶囊：服务端筛选（点击进入分类归档） -->
		<?php if ( oneyer_opt_checked( 'oneyer_show_chips' ) ) : ?>
		<div class="chips" id="chips">
			<a class="chip<?php echo ( is_home() || $oy_is_static_front ) && ! is_category() ? ' on' : ''; ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>"><span class="dot"></span>全部</a>
			<?php
			$oy_current_cat = is_category() ? get_queried_object_id() : 0;
			$oy_chip_cats   = get_categories( array( 'hide_empty' => true, 'number' => 10 ) );
				foreach ( $oy_chip_cats as $oy_cc ) {
					printf(
						'<a class="chip%1$s" href="%2$s"><span class="dot"></span>%3$s<span class="cnt">%4$s</span></a>',
						$oy_current_cat === $oy_cc->term_id ? ' on' : '',
						esc_url( get_category_link( $oy_cc ) ),
						esc_html( $oy_cc->name ),
						esc_html( number_format_i18n( $oy_cc->count ) )
					);
				}
				?>
			</div>
			<?php endif; ?>

		<?php
		/**
		 * 置顶大卡：仅在第一页展示。
		 * 有置顶用置顶；无置顶降级最新一篇；整站无文章则不渲染（走空状态）。
		 */
		if ( 1 === $oy_paged && $oy_featured ) :
			$oy_fq = new WP_Query( array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'p'                   => $oy_featured,
				'no_found_rows'       => true,
			) );
			if ( $oy_fq->have_posts() ) :
				while ( $oy_fq->have_posts() ) :
					$oy_fq->the_post();
					$oy_f_cats = get_the_category();
					$oy_f_cat  = $oy_f_cats ? $oy_f_cats[0] : null;
					$oy_f_pair = oneyer_gradients()[ oneyer_grad_class( $oy_f_cat ? $oy_f_cat->slug : 'featured' ) ];
					?>
		<article class="featured">
			<div class="featured-media">
				<?php if ( has_post_thumbnail() ) : ?>
				<img src="<?php echo esc_url( get_the_post_thumbnail_url( get_the_ID(), 'oneyer-card' ) ); ?>" alt="<?php echo esc_attr( the_title_attribute( array( 'echo' => false ) ) ); ?>" loading="lazy">
				<?php else : ?>
				<div class="fm-float" aria-hidden="true">
					<span><?php echo esc_html( $oy_f_cat ? $oy_f_cat->name : '' ); ?></span>
					<span><?php echo esc_html( get_the_date( 'Y-m-d' ) ); ?></span>
				</div>
				<div class="fm-code" aria-hidden="true">
					<div class="win"><i style="background:#ff5f57"></i><i style="background:#febc2e"></i><i style="background:#28c840"></i></div>
					<span class="c2">const</span> post = {<br>
					&nbsp;&nbsp;title: <span class="c3">'<?php echo esc_js( wp_trim_words( get_the_title(), 8, '' ) ); ?>'</span>,<br>
					&nbsp;&nbsp;date: <span class="c3">'<?php echo esc_js( get_the_date( 'Y-m-d' ) ); ?>'</span>,<br>
					&nbsp;&nbsp;<span class="c1">pinned</span>: <span class="c2">true</span><br>
					};
				</div>
				<?php endif; ?>
			</div>
			<div class="featured-body">
				<?php if ( is_sticky() ) : ?>
				<span class="pinned">
					<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M14 2l8 8-4 1-5 5 1 5-3-3-6 6-2-2 6-6-3-3 5 1 5-5 1-4z"/></svg>
					置顶<?php echo $oy_f_cat ? ' · ' . esc_html( $oy_f_cat->name ) : ''; ?>
				</span>
				<?php endif; ?>
				<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
				<p class="exc"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<div class="meta">
					<span class="m">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="3"/><path d="M16 2v4M8 2v4M3 10h18"/></svg><?php echo esc_html( get_the_date() ); ?>
					</span>
					<span class="m">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12a8 8 0 0 1-8 8H5l-2 2V12a8 8 0 0 1 8-8h2a8 8 0 0 1 8 8Z"/></svg><?php echo esc_html( number_format_i18n( get_comments_number() ) ); ?> 评论
					</span>
					<?php if ( $oy_f_cat ) : ?><span class="tagpill"><?php echo esc_html( $oy_f_cat->name ); ?></span><?php endif; ?>
				</div>
			</div>
		</article>
					<?php
				endwhile;
			endif;
			wp_reset_postdata();
		endif;
		?>

		<!-- 文章网格 -->
		<?php if ( 1 === $oy_paged || $oy_paged > 1 ) : ?>
		<div class="section-head reveal">
			<span class="bar" aria-hidden="true"></span>
			<div><h3>最新文章</h3><div class="sub">Latest Posts</div></div>
		</div>
		<?php endif; ?>

		<?php if ( $oy_query->have_posts() ) : ?>
		<div class="grid" id="grid">
			<?php
			while ( $oy_query->have_posts() ) :
				$oy_query->the_post();
				get_template_part( 'template-parts/post-card' );
			endwhile;
			wp_reset_postdata();
			?>
		</div>

		<div class="load-wrap reveal" id="loadWrap" data-max="<?php echo esc_attr( $oy_max ); ?>" data-found="<?php echo esc_attr( $oy_found ); ?>" data-per-page="<?php echo esc_attr( $oy_home_ppp ); ?>">
			<button class="loadmore" id="loadMore" style="display:none" type="button">
				<svg class="spin" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9"/></svg>
				加载更多
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 4v14m-6-6 6 6 6-6"/></svg>
			</button>
			<span class="load-hint" id="loadHint">已展示 <?php echo esc_html( number_format_i18n( min( $oy_home_ppp, $oy_found ) ) ); ?> / <?php echo esc_html( number_format_i18n( $oy_found ) ); ?> 篇</span>
		</div>
		<div id="homePager"><?php oneyer_pagination( $oy_query, $oy_found ); ?></div>

		<?php else : ?>
		<div class="load-wrap"></div>
			<?php
			get_template_part( 'template-parts/empty-state', null, array(
				'title' => '还没有发布任何文章',
				'desc'  => '第一篇文章正在路上。到 WordPress 后台「文章 → 写文章」发布第一篇，这里就会亮起来。',
				'links' => is_user_logged_in() ? array( '去写文章' => admin_url( 'post-new.php' ) ) : array( '回首页' => home_url( '/' ) ),
			) );
			?>
		<?php endif; ?>

		<!-- 混排区：热门阅读 / 标签云 / 友情链接 -->
		<?php if ( 1 === $oy_paged ) : ?>
		<?php
		$oy_hot_n   = (int) oneyer_opt( 'oneyer_hot_count', '6' );
		$oy_tag_n   = (int) oneyer_opt( 'oneyer_tag_count', '10' );
		$oy_links_n = (int) oneyer_opt( 'oneyer_links_count', '4' );
		?>
		<div class="mix">
			<?php if ( $oy_hot_n > 0 ) : ?>
			<?php
			$oy_hot = new WP_Query( array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => $oy_hot_n,
				'orderby'             => 'comment_count',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			) );
			?>
			<?php if ( $oy_hot->have_posts() ) : ?>
			<div class="list-card reveal">
				<h3><span class="bar"></span>热门阅读</h3>
				<?php
				$oy_rank = 0;
				while ( $oy_hot->have_posts() ) :
					$oy_hot->the_post();
					$oy_rank++;
					?>
					<a class="hot-item" href="<?php the_permalink(); ?>">
						<span class="rank"><?php echo esc_html( $oy_rank ); ?></span>
						<div>
							<div class="t"><?php the_title(); ?></div>
							<div class="s"><?php echo esc_html( number_format_i18n( get_comments_number() ) ); ?> 评论 · <?php echo esc_html( get_the_date() ); ?></div>
						</div>
					</a>
					<?php
				endwhile;
				wp_reset_postdata();
				?>
			</div>
			<?php endif; ?>
			<?php endif; // $oy_hot_n > 0 ?>

			<div class="reveal">
				<?php
				$oy_tags = $oy_tag_n > 0 ? get_tags( array( 'orderby' => 'count', 'order' => 'DESC', 'number' => $oy_tag_n, 'hide_empty' => true ) ) : array();
				if ( $oy_tags ) :
					?>
				<div class="list-card" style="margin-bottom:22px">
					<h3><span class="bar"></span>标签云</h3>
					<div class="tag-cloud">
						<?php foreach ( $oy_tags as $oy_tag ) : ?>
						<a href="<?php echo esc_url( get_tag_link( $oy_tag ) ); ?>"><?php echo esc_html( $oy_tag->name ); ?><b><?php echo esc_html( number_format_i18n( $oy_tag->count ) ); ?></b></a>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>

				<div class="list-card">
					<h3><span class="bar"></span>友情链接</h3>
					<?php
					$oy_links = $oy_links_n > 0 ? oneyer_friend_links( array( 'posts_per_page' => $oy_links_n, 'status' => 'normal' ) ) : array();
					if ( $oy_links ) :
						?>
					<div class="links">
						<?php
						$oy_li = 0;
						foreach ( $oy_links as $oy_link ) :
							$oy_li++;
							$oy_av = get_post_meta( $oy_link->ID, 'oy_avatar', true );
							$oy_url = get_post_meta( $oy_link->ID, 'oy_url', true );
							if ( ! $oy_av ) {
								$oy_av = mb_substr( get_the_title( $oy_link ), 0, 1, 'UTF-8' );
							}
							?>
						<a href="<?php echo esc_url( $oy_url ? $oy_url : get_permalink( $oy_link ) ); ?>" target="_blank" rel="noopener noreferrer">
							<span class="av" style="background:<?php echo esc_attr( oneyer_link_color( $oy_link->ID, $oy_li - 1 ) ); ?>"><?php echo esc_html( $oy_av ); ?></span><?php echo esc_html( get_the_title( $oy_link ) ); ?>
						</a>
						<?php endforeach; ?>
					</div>
					<?php else : ?>
					<div class="links"><span class="links-empty">暂无友链，欢迎到友链页申请。</span></div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php endif; ?>
	</main>

<?php get_footer(); ?>
