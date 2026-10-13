<?php
/**
 * 原生搜索表单：导航版（.nav-search）与抽屉紧凑版（.d-search）共用。
 * 快捷键 Ctrl/Cmd + K 由 assets/js/main.js 绑定聚焦导航搜索框。
 */
$compact = ! empty( $GLOBALS['oneyer_search_compact'] );
$wrap    = $compact ? 'd-search' : 'nav-search';
?>
<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="oneyer-searchform">
	<label class="<?php echo esc_attr( $wrap ); ?>">
		<span class="screen-reader-text"><?php esc_html_e( '搜索：', 'oneyer-stuflow' ); ?></span>
		<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
		<input type="search" name="s" placeholder="<?php esc_attr_e( '搜索文章…', 'oneyer-stuflow' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>">
		<?php if ( ! $compact ) : ?>
		<kbd aria-hidden="true">Ctrl K</kbd>
		<?php endif; ?>
	</label>
</form>
