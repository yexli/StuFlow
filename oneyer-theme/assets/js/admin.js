/* Oneyer 后台设置页脚本 */
(function () {
	'use strict';

	/* 1. 标签页地址写进 hash：刷新停留当前 tab（可选增强） */
	document.addEventListener('DOMContentLoaded', function () {
		var tab = new URLSearchParams(location.search).get('tab');
		if (tab) history.replaceState(null, '', location.pathname + location.search + '#' + tab);
	});

	/* 2. textarea（技能栈/本站构成）Tab 键支持：阻止跳焦点，插入两个空格 */
	document.querySelectorAll('.oy-field__ctrl textarea').forEach(function (ta) {
		ta.addEventListener('keydown', function (e) {
			if (e.key === 'Tab') {
				e.preventDefault();
				var s = ta.selectionStart, en = ta.selectionEnd;
				ta.value = ta.value.slice(0, s) + '  ' + ta.value.slice(en);
				ta.selectionStart = ta.selectionEnd = s + 2;
			}
		});
	});

	/* 3. 通知条关闭按钮 */
	var noticeX = document.querySelector('.oy-notice__x');
	if (noticeX) {
		noticeX.addEventListener('click', function () {
			var n = noticeX.closest('.oy-notice');
			if (n) n.remove();
		});
	}

	/* 4. 离开未保存提醒（表单 dirty 检测） */
	var form = document.querySelector('.oy-form');
	if (form) {
		var dirty = false;
		form.addEventListener('input', function () { dirty = true; });
		form.addEventListener('submit', function () { dirty = false; });
		window.addEventListener('beforeunload', function (e) {
			if (dirty) { e.preventDefault(); e.returnValue = ''; }
		});
	}
})();
