<?php
/**
 * 区块标题（.section-head）。
 * 参数（get_template_part 传参或变量）：
 *   $args['title'] 标题（必填）
 *   $args['sub']   英文小标
 *   $args['more']  「查看全部」链接（可选）
 */
if ( ! empty( $args ) ) {
	$oy_title = isset( $args['title'] ) ? $args['title'] : '';
	$oy_sub   = isset( $args['sub'] ) ? $args['sub'] : '';
	$oy_more  = isset( $args['more'] ) ? $args['more'] : '';
} else {
	$oy_title = isset( $oy_title ) ? $oy_title : '';
	$oy_sub   = isset( $oy_sub ) ? $oy_sub : '';
	$oy_more  = isset( $oy_more ) ? $oy_more : '';
}
if ( '' === $oy_title ) {
	return;
}
?>
<div class="section-head">
	<span class="bar" aria-hidden="true"></span>
	<div><h3><?php echo esc_html( $oy_title ); ?></h3><?php if ( $oy_sub ) : ?><div class="sub"><?php echo esc_html( $oy_sub ); ?></div><?php endif; ?></div>
	<?php if ( $oy_more ) : ?>
	<a class="more" href="<?php echo esc_url( $oy_more ); ?>">查看全部
		<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
	</a>
	<?php endif; ?>
</div>
