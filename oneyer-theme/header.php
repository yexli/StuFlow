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
</aside>
