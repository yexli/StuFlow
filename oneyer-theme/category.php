<?php
/**
 * 分类归档页。
 * 原型：category.html（cat-banner / 子分类 chips / grid / pager）。
 */

get_header();

$oy_cat   = get_queried_object();
$oy_count = (int) $oy_cat->count;
$oy_glyph = oneyer_glyph( $oy_cat->name, $oy_cat->slug );

/* 分类横幅统计：评论数取该分类（含子分类）下文章的已批准评论总数（真实数据）。 */
$oy_cat_ids = get_terms( array(
	'taxonomy'   => 'category',
	'object_ids' => get_objects_in_term( $oy_cat->term_id, 'category' ),
	'fields'     => 'ids',
	'hide_empty' => false,
) );
if ( is_wp_error( $oy_cat_ids ) || ! $oy_cat_ids ) {
	$oy_cat_ids = array( $oy_cat->term_id );
}
$oy_comments   = 0;
$oy_comments_q = new WP_Query( array(
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'posts_per_page'      => -1,
	'fields'              => 'ids',
	'category__in'        => $oy_cat_ids,
	'no_found_rows'       => true,
	'ignore_sticky_posts' => true,
) );
foreach ( $oy_comments_q->posts as $oy_pid ) {
	$oy_comments += (int) get_comments_number( $oy_pid );
}
?>

<main class="wrap">
	<section class="cat-hero">
		<nav class="crumb" aria-label="面包屑">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">首页</a><span class="sep">/</span>
			<span class="cur"><?php echo esc_html( $oy_cat->name ); ?></span>
		</nav>

		<div class="cat-banner">
			<div class="cb-ic" aria-hidden="true"><?php echo esc_html( $oy_glyph ); ?></div>
			<div class="cb-tx">
				<div class="cb-kicker">Category · <?php echo esc_html( $oy_cat->name ); ?></div>
				<h1><?php echo esc_html( $oy_cat->name ); ?></h1>
				<?php if ( $oy_cat->description ) : ?>
				<p><?php echo esc_html( $oy_cat->description ); ?></p>
				<?php endif; ?>
			</div>
			<div class="cb-stats">
				<div><b><?php echo esc_html( number_format_i18n( $oy_count ) ); ?></b><span>文章</span></div>
				<div><b><?php echo esc_html( number_format_i18n( $oy_comments ) ); ?></b><span>评论</span></div>
			</div>
		</div>

		<?php
		/* 子分类筛选条（有子分类才渲染；点击进入子分类归档，服务端筛选）。 */
		$oy_children = get_terms( array(
			'taxonomy'   => 'category',
			'parent'     => $oy_cat->term_id,
			'hide_empty' => true,
		) );
		if ( $oy_children && ! is_wp_error( $oy_children ) ) :
			?>
		<div class="chips">
			<a class="chip on" href="<?php echo esc_url( get_category_link( $oy_cat ) ); ?>"><span class="dot"></span>全部</a>
			<?php foreach ( $oy_children as $oy_child ) : ?>
			<a class="chip" href="<?php echo esc_url( get_category_link( $oy_child ) ); ?>"><span class="dot"></span><?php echo esc_html( $oy_child->name ); ?><span class="cnt"><?php echo esc_html( number_format_i18n( $oy_child->count ) ); ?></span></a>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<div class="section-head">
			<span class="bar" aria-hidden="true"></span>
			<div><h3><?php echo esc_html( $oy_cat->name ); ?>下的文章</h3><div class="sub">All Posts</div></div>
		</div>

		<?php if ( have_posts() ) : ?>
		<div class="grid" id="grid">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/post-card' );
			endwhile;
			?>
		</div>
		<?php oneyer_pagination(); ?>
		<?php else : ?>
			<?php
			get_template_part( 'template-parts/empty-state', null, array(
				'title' => '该分类下还没有文章',
				'desc'  => '文章发布并归入此分类后就会出现在这里。',
				'links' => array( '回首页看看' => home_url( '/' ) ),
			) );
			?>
		<?php endif; ?>
	</section>
</main>

<?php get_footer(); ?>
