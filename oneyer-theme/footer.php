<?php
/**
 * 深色页脚：品牌列 + 栏目/站点/订阅 + 版权与备案行。
 * 原型：各 HTML 页 <footer> 整段。备案号未配置时不输出对应节点。
 */

$icp    = oneyer_opt( 'oneyer_icp' );
$police = oneyer_opt( 'oneyer_police' );
$since  = oneyer_opt( 'oneyer_since' );
$start  = $since ? (string) intval( substr( $since, 0, 4 ) ) : '';
$year   = gmdate( 'Y' );
$range  = ( $start && $start !== $year ) ? $start . '-' . $year : $year;
?>
<button class="backtop" id="backtop" aria-label="返回顶部">
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 20V6m-6 6 6-6 6 6"/></svg>
</button>

<footer>
  <div class="wrap">
    <div class="foot-grid">
      <div class="foot-brand">
        <a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo oneyer_logo_html( 36 ); ?><span><?php bloginfo( 'name' ); ?></span></a>
        <p><?php bloginfo( 'description' ); ?></p>
        <?php $oy_footer_note = oneyer_opt( 'oneyer_footer_note', '' ); ?>
        <?php if ( $oy_footer_note ) : ?>
        <p class="foot-note" style="margin-top:10px"><?php echo wp_kses( $oy_footer_note, array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ), 'b' => array(), 'strong' => array(), 'em' => array(), 'br' => array() ) ); ?></p>
        <?php endif; ?>
      </div>

      <div class="foot-col">
        <h5><?php esc_html_e( '栏目', 'oneyer-stuflow' ); ?></h5>
        <?php if ( has_nav_menu( 'footer-cats' ) ) : ?>
          <?php wp_nav_menu( array( 'theme_location' => 'footer-cats', 'container' => false, 'menu_class' => '', 'depth' => 1 ) ); ?>
        <?php else : ?>
          <ul>
            <?php
            $foot_cats = get_categories( array( 'number' => 5, 'hide_empty' => true ) );
            foreach ( $foot_cats as $fc ) {
              echo '<li><a href="' . esc_url( get_category_link( $fc ) ) . '">' . esc_html( $fc->name ) . '</a></li>';
            }
            ?>
          </ul>
        <?php endif; ?>
      </div>

      <div class="foot-col">
        <h5><?php esc_html_e( '站点', 'oneyer-stuflow' ); ?></h5>
        <?php if ( has_nav_menu( 'footer-site' ) ) : ?>
          <?php wp_nav_menu( array( 'theme_location' => 'footer-site', 'container' => false, 'menu_class' => '', 'depth' => 1 ) ); ?>
        <?php else : ?>
        <ul>
          <?php
          $site_links = array(
            'page-templates/page-about.php'            => '关于本站',
            'page-templates/page-archive-timeline.php' => '文章归档',
            'page-templates/page-links.php'            => '友情链接',
          );
          foreach ( $site_links as $tpl => $label ) {
            $url = oneyer_template_page_url( $tpl );
            if ( $url ) {
              echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
            }
          }
          ?>
        </ul>
        <?php endif; ?>
      </div>

      <div class="foot-col">
        <h5><?php esc_html_e( '订阅', 'oneyer-stuflow' ); ?></h5>
        <ul>
          <li><a href="<?php echo esc_url( get_bloginfo( 'rss2_url' ) ); ?>"><?php esc_html_e( 'RSS 订阅', 'oneyer-stuflow' ); ?></a></li>
          <?php
          $wechat   = oneyer_opt( 'oneyer_wechat_url' );
          $telegram = oneyer_opt( 'oneyer_telegram' );
          if ( $wechat ) {
            echo '<li><a href="' . esc_url( $wechat ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( '微信公众号', 'oneyer-stuflow' ) . '</a></li>';
          }
          if ( $telegram ) {
            echo '<li><a href="' . esc_url( $telegram ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Telegram 频道', 'oneyer-stuflow' ) . '</a></li>';
          }
          ?>
        </ul>
      </div>
    </div>

    <div class="foot-bar">
      <span>&copy; <?php echo esc_html( $range ); ?> <?php bloginfo( 'name' ); ?></span>
      <?php if ( $icp || $police ) : ?>
      <span class="icp">
        <?php if ( $icp ) : ?><span><?php echo esc_html( $icp ); ?></span><?php endif; ?>
        <?php if ( $police ) : ?><span><?php echo esc_html( $police ); ?></span><?php endif; ?>
      </span>
      <?php endif; ?>
    </div>
  </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
