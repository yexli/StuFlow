<?php
/**
 * 评论区：WordPress 原生评论（免登录由后台「设置 → 讨论」控制）。
 * 原型：single.html .comments / .cmt / .cmt-form。
 */

if ( post_password_required() ) {
	return;
}

$oy_count = (int) get_comments_number();
?>
<section class="comments reveal" id="comments">
	<h3><?php esc_html_e( '评论', 'oneyer-stuflow' ); ?> <?php if ( $oy_count ) : ?><span class="cnt"><?php echo esc_html( number_format_i18n( $oy_count ) ); ?></span><?php endif; ?></h3>
	<p class="sub"><?php esc_html_e( '留言无需注册，填个昵称就能说上两句。', 'oneyer-stuflow' ); ?></p>

	<?php if ( have_comments() ) : ?>
	<div class="comment-list">
		<?php
		wp_list_comments( array(
			'style'       => 'div',
			'callback'    => 'oneyer_comment',
			'max_depth'   => 2,
			'avatar_size' => 42,
			'short_ping'  => true,
		) );
		?>
	</div>

	<?php
	the_comments_pagination( array(
		'mid_size'  => 1,
		'prev_text' => '‹ 上一页',
		'next_text' => '下一页 ›',
	) );
	?>
	<?php elseif ( ! comments_open() ) : ?>
	<p class="no-comment"><?php esc_html_e( '本文评论已关闭。', 'oneyer-stuflow' ); ?></p>
	<?php endif; ?>

	<?php
	comment_form( array(
		'title_reply'         => __( '发表评论', 'oneyer-stuflow' ),
		'title_reply_before'  => '<h4>',
		'title_reply_after'   => '</h4>',
		'class_submit'        => 'submit',
		'label_submit'        => __( '提交评论', 'oneyer-stuflow' ),
		'comment_notes_before'=> '<p class="comment-notes">✓ 无需注册，提交即显示 · 首次留言会进入审核队列</p>',
		'comment_field'       => '<p class="comment-form-comment"><label for="comment">' . esc_html__( '评论内容', 'oneyer-stuflow' ) . '</label><textarea id="comment" name="comment" rows="5" placeholder="' . esc_attr__( '说点什么吧… 支持 Markdown 语法（**加粗**、`代码`、> 引用）', 'oneyer-stuflow' ) . '"></textarea></p>',
	) );
	?>
</section>
