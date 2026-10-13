<?php
/**
 * 相关阅读：当前分类下排除本篇取 3 篇；不足 3 篇时以最新文章补足。
 * 必须在单篇文章 Loop 内使用（依赖 get_the_ID()）。
 * 原型：single.html .related。
 */

$oy_current = get_the_ID();
if ( ! $oy_current ) {
	return;
}

/* 条数可在后台「ONEYER → 文章页 → 相关文章条数」配置，0 = 关闭该区块。 */
$oy_needed = (int) oneyer_opt( 'oneyer_related_count', '3' );
if ( $oy_needed < 1 ) {
	return;
}

$oy_cats   = wp_get_post_categories( $oy_current );
$oy_posts  = array();

if ( $oy_cats ) {
	$q = new WP_Query( array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $oy_needed,
		'post__not_in'        => array( $oy_current ),
		'category__in'        => $oy_cats,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'orderby'             => 'date',
	) );
	$oy_posts = $q->posts;
	wp_reset_postdata();
}

if ( count( $oy_posts ) < $oy_needed ) {
	$have     = wp_list_pluck( $oy_posts, 'ID' );
	$have[]   = $oy_current;
	$fill     = get_posts( array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => $oy_needed - count( $oy_posts ),
		'post__not_in'   => $have,
		'no_found_rows'  => true,
	) );
	$oy_posts = array_merge( $oy_posts, $fill );
}

if ( ! $oy_posts ) {
	return;
}
?>
<section class="related reveal">
	<?php get_template_part( 'template-parts/section-heading', null, array( 'title' => '相关阅读', 'sub' => 'Related Posts' ) ); ?>
	<div class="rel-grid">
		<?php
		global $post;
		$oy_backup = $post;
		foreach ( $oy_posts as $post ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride -- 模板部分依赖 Loop 上下文
			setup_postdata( $post );
			get_template_part( 'template-parts/post-card' );
		}
		wp_reset_postdata();
		$post = $oy_backup; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		?>
	</div>
</section>
