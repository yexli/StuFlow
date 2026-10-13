<?php
/**
 * 通用归档兜底：标签 / 作者 / 日期 / 自定义文章类型归档。
 * 复用分类页布局，横幅信息按查询对象生成。
 * 原型：category.html 布局。
 */

get_header();

$oy_obj    = get_queried_object();
$oy_name   = get_the_archive_title();
$oy_desc   = get_the_archive_description();
$oy_count  = 0;
$oy_glyph  = 'ARC';
$oy_kicker = 'Archive';

if ( is_tag() && $oy_obj instanceof WP_Term ) {
	$oy_kicker = 'Tag';
	$oy_count  = (int) $oy_obj->count;
	$oy_name   = $oy_obj->name;
	$oy_glyph  = oneyer_glyph( $oy_obj->name, $oy_obj->slug );
} elseif ( is_category() && $oy_obj instanceof WP_Term ) {
	$oy_kicker = 'Category';
	$oy_count  = (int) $oy_obj->count;
	$oy_name   = $oy_obj->name;
	$oy_glyph  = oneyer_glyph( $oy_obj->name, $oy_obj->slug );
} elseif ( is_author() && $oy_obj instanceof WP_User ) {
	$oy_kicker = 'Author';
	$oy_name   = $oy_obj->display_name;
	$oy_glyph  = mb_substr( $oy_obj->display_name, 0, 2, 'UTF-8' );
	$oy_count  = (int) $oy_obj->post_count;
} elseif ( is_date() ) {
	$oy_kicker = 'Archive';
	$oy_name   = get_the_date( 'Y 年 n 月' );
	if ( is_year() ) {
		$oy_name = get_the_date( 'Y 年' );
	} elseif ( is_day() ) {
		$oy_name = get_the_date( 'Y 年 n 月 j 日' );
	}
	global $wp_query;
	$oy_count = (int) $wp_query->found_posts;
	$oy_glyph = '📅';
} elseif ( $oy_obj instanceof WP_Post_Type ) {
	$oy_kicker = 'Post Type';
	$oy_name   = $oy_obj->labels->name;
	$oy_glyph  = oneyer_glyph( $oy_obj->labels->singular_name, $oy_obj->name );
	global $wp_query;
	$oy_count = (int) $wp_query->found_posts;
} else {
	global $wp_query;
	$oy_count = (int) $wp_query->found_posts;
	$oy_name  = wp_strip_all_tags( $oy_name );
}
?>

<main class="wrap">
	<section class="cat-hero">
		<nav class="crumb" aria-label="面包屑">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">首页</a><span class="sep">/</span>
			<span class="cur"><?php echo esc_html( $oy_name ); ?></span>
		</nav>

		<div class="cat-banner">
			<div class="cb-ic" aria-hidden="true"><?php echo esc_html( $oy_glyph ); ?></div>
			<div class="cb-tx">
				<div class="cb-kicker"><?php echo esc_html( $oy_kicker ); ?> · <?php echo esc_html( $oy_name ); ?></div>
				<h1><?php echo esc_html( $oy_name ); ?></h1>
				<?php if ( $oy_desc ) : ?>
				<p><?php echo wp_kses_post( $oy_desc ); ?></p>
				<?php endif; ?>
			</div>
			<div class="cb-stats">
				<div><b><?php echo esc_html( number_format_i18n( $oy_count ) ); ?></b><span>篇文章</span></div>
			</div>
		</div>

		<div class="section-head">
			<span class="bar" aria-hidden="true"></span>
			<div><h3><?php echo esc_html( $oy_name ); ?>下的文章</h3><div class="sub">All Posts</div></div>
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
				'title' => '该归档下还没有文章',
				'links' => array( '回首页看看' => home_url( '/' ) ),
			) );
			?>
		<?php endif; ?>
	</section>
</main>

<?php get_footer(); ?>
