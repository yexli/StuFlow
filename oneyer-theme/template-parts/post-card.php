<?php
/**
 * 可复用文章卡片（首页网格 / 相关文章 / 加载更多）。
 * 依赖当前全局文章上下文（The Loop 内）。
 * 原型：index.html .card 结构。
 */

$oy_id     = get_the_ID();
$oy_cats   = get_the_category( $oy_id );
$oy_tags   = get_the_tags( $oy_id );
$oy_cat    = $oy_cats ? $oy_cats[0] : null;
?>
<article class="card" data-cat="<?php echo esc_attr( $oy_cat ? $oy_cat->slug : '' ); ?>">
	<?php oneyer_card_media( $oy_id ); ?>
	<div class="card-body">
		<h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
		<p><?php echo esc_html( oneyer_clean_excerpt( get_the_excerpt( $oy_id ) ) ); ?></p>
		<div class="card-foot">
			<span><?php echo esc_html( get_the_date( 'm-d', $oy_id ) ); ?></span>
			<?php if ( $oy_tags && ! is_wp_error( $oy_tags ) ) : ?>
			<div class="tags">
				<?php
				foreach ( array_slice( $oy_tags, 0, 2 ) as $oy_tag ) {
					echo '<a href="' . esc_url( get_tag_link( $oy_tag ) ) . '">' . esc_html( $oy_tag->name ) . '</a>';
				}
				?>
			</div>
			<?php endif; ?>
		</div>
	</div>
</article>
