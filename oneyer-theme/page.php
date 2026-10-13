<?php
/**
 * 普通页面兜底模板：不在关于/友链/时间线模板中的页面使用。
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
<main class="wrap" id="oneyer-main">
	<section class="cat-hero">
		<nav class="crumb" aria-label="面包屑">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">首页</a><span class="sep">/</span>
			<span class="cur"><?php the_title(); ?></span>
		</nav>

		<article class="post">
			<h1><?php the_title(); ?></h1>

			<?php if ( has_post_thumbnail() ) : ?>
			<div class="post-thumb"><?php the_post_thumbnail( 'large' ); ?></div>
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
		</article>

		<?php if ( comments_open() || get_comments_number() ) : ?>
			<?php comments_template(); ?>
		<?php endif; ?>
	</section>
</main>
	<?php
endwhile;

get_footer();
