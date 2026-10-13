<?php
/**
 * 空状态卡（无文章 / 无结果 / 空归档通用）。
 * 参数：$args['title']、$args['desc']、$args['links'] = array( label => url )、$args['icon']（SVG 字符串，需可信）
 */
$oy_title = isset( $args['title'] ) ? $args['title'] : '这里还什么都没有';
$oy_desc  = isset( $args['desc'] ) ? $args['desc'] : '';
$oy_links = isset( $args['links'] ) && is_array( $args['links'] ) ? $args['links'] : array();
$oy_icon  = isset( $args['icon'] ) ? $args['icon'] : '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="3" y="4" width="18" height="16" rx="3"/><path d="M3 9h18M8 4V2m8 2V2M7 14h4"/></svg>';
?>
<div class="empty-state">
	<div class="es-ic" aria-hidden="true"><?php echo $oy_icon; // phpcs:ignore WordPress.Security.EscapeOutput -- 仅主题内置 SVG ?></div>
	<h3><?php echo esc_html( $oy_title ); ?></h3>
	<?php if ( $oy_desc ) : ?><p><?php echo esc_html( $oy_desc ); ?></p><?php endif; ?>
	<?php if ( $oy_links ) : ?>
	<div class="es-actions">
		<?php foreach ( $oy_links as $oy_label => $oy_url ) : ?>
		<a href="<?php echo esc_url( $oy_url ); ?>"><?php echo esc_html( $oy_label ); ?></a>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
</div>
