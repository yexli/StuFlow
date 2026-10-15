<?php
/**
 * 全站公共头部：阅读进度条、毛玻璃导航、下拉菜单、搜索、移动抽屉。
 * 原型：各 HTML 页 <div class="nav-wrap"> 至 </aside> 段。
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php wp_head(); ?>
</head>
<body <?php body_class( 'no-js' ); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#oneyer-main">跳到主要内容</a>
<div class="progress" id="progress"></div>
<div class="pjax-bar" aria-hidden="true"></div>

<div class="nav-wrap">
  <nav class="nav" id="nav" aria-label="主导航">
    <div class="wrap nav-inner">
      <a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
        <?php echo oneyer_logo_html( 36 ); ?><span><?php bloginfo( 'name' ); ?></span>
      </a>
      <?php oneyer_primary_menu(); ?>
      <div class="nav-search-wrap"><?php get_search_form(); ?></div>
      <button class="nav-burger" id="burger" aria-label="打开菜单" aria-expanded="false" aria-controls="drawer">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
      </button>
    </div>
  </nav>
</div>

<!-- 移动抽屉 -->
<div class="drawer-mask" id="drawerMask"></div>
<aside class="drawer" id="drawer" aria-label="移动端菜单" aria-hidden="true">
  <div class="d-head">
    <a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo oneyer_logo_html( 30 ); ?><span><?php bloginfo( 'name' ); ?></span></a>
    <button class="d-close" id="drawerClose" aria-label="关闭菜单">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
    </button>
  </div>
  <div class="d-search-wrap"><?php oneyer_search_form( true ); ?></div>
  <?php oneyer_drawer_menu(); ?>

  <?php $oy_dstats = oneyer_stats(); if ( $oy_dstats ) : ?>
  <div class="d-stats" aria-label="站点统计">
    <?php foreach ( $oy_dstats as $oy_ds ) : ?>
    <div class="d-stat"><b><?php echo esc_html( number_format_i18n( $oy_ds[1] ) ); ?><?php echo ! empty( $oy_ds[2] ) ? '<i>+</i>' : ''; ?></b><span><?php echo esc_html( $oy_ds[0] ); ?></span></div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php
  $oy_dquick = array(
    'page-templates/page-about.php'            => array( '关于本站', 'M4 11a8 8 0 0 1 16 0' ),
    'page-templates/page-archive-timeline.php' => array( '文章归档', 'M4 19V6a2 2 0 0 1 2-2h5v15H4ZM13 19V6a2 2 0 0 1 2-2h5v15h-7Z' ),
    'page-templates/page-links.php'            => array( '友情链接', 'M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7' ),
  );
  $oy_dquick_items = array();
  foreach ( $oy_dquick as $oy_tpl => $oy_cfg ) {
    $oy_u = oneyer_template_page_url( $oy_tpl );
    if ( $oy_u ) {
      $oy_dquick_items[] = array( $oy_u, $oy_cfg[0], $oy_cfg[1] );
    }
  }
  if ( $oy_dquick_items ) :
  ?>
  <div class="d-quick" aria-label="快捷页面">
    <?php foreach ( $oy_dquick_items as $oy_i => $oy_q ) : ?>
    <a class="d-qcard" href="<?php echo esc_url( $oy_q[0] ); ?>">
      <span class="d-qic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="<?php echo esc_attr( $oy_q[2] ); ?>"/></svg></span>
      <span class="d-qt"><?php echo esc_html( $oy_q[1] ); ?></span>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</aside>
