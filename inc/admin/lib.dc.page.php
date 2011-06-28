<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2010 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_RC_PATH')) { return; }

define('DC_AUTH_PAGE','auth.php');

class dcPage
{
	# Auth check
	public static function check($permissions)
	{
		global $core;
		
		if ($core->blog && $core->auth->check($permissions,$core->blog->id))
		{
			return;
		}
		
		if (session_id()) {
			$core->session->destroy();
		}
		http::redirect(DC_AUTH_PAGE);
	}
	
	# Check super admin
	public static function checkSuper()
	{
		global $core;
		
		if (!$core->auth->isSuperAdmin())
		{
			if (session_id()) {
				$core->session->destroy();
			}
			http::redirect(DC_AUTH_PAGE);
		}
	}
	
	# Top of admin page
	public static function open($title='', $head='')
	{
		global $core;
		
		# List of user's blogs
		if ($core->auth->blog_count == 1 || $core->auth->blog_count > 20)
		{
			$blog_box =
			__('Blog:').' <strong title="'.html::escapeHTML($core->blog->url).'">'.
			html::escapeHTML($core->blog->name).'</strong>';
			
			if ($core->auth->blog_count > 20) {
				$blog_box .= ' - <a href="blogs.php">'.__('Change blog').'</a>';
			}
		}
		else
		{
			$rs_blogs = $core->getBlogs(array('order'=>'LOWER(blog_name)','limit'=>20));
			$blogs = array();
			while ($rs_blogs->fetch()) {
				$blogs[html::escapeHTML($rs_blogs->blog_name.' - '.$rs_blogs->blog_url)] = $rs_blogs->blog_id;
			}
			$blog_box =
			'<label for="switchblog" class="classic">'.
			__('Blogs:').' '.
			$core->formNonce().
			form::combo('switchblog',$blogs,$core->blog->id,	'',1).
			'</label>'.
			'<noscript><div><input type="submit" value="'.__('ok').'" /></div></noscript>';
		}
		
		$safe_mode = isset($_SESSION['sess_safe_mode']) && $_SESSION['sess_safe_mode'];
		
		# Display
		header('Content-Type: text/html; charset=UTF-8');
		echo
		'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" '.
		' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n".
		'<html xmlns="http://www.w3.org/1999/xhtml" '.
		'xml:lang="'.$core->auth->getInfo('user_lang').'" '.
		'lang="'.$core->auth->getInfo('user_lang').'">'."\n".
		"<head>\n".
		'  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'."\n".
		'  <title>'.$title.' - '.html::escapeHTML($core->blog->name).' - '.html::escapeHTML(DC_VENDOR_NAME).' - '.DC_VERSION.'</title>'."\n".
		
		'  <meta name="ROBOTS" content="NOARCHIVE,NOINDEX,NOFOLLOW" />'."\n".
		'  <meta name="GOOGLEBOT" content="NOSNIPPET" />'."\n".
		
		self::jsLoadIE7().
		'  <style type="text/css">'."\n". 
		'  @import "style/default.css";'."\n".
		"  </style>\n";
		if (l10n::getTextDirection($GLOBALS['_lang']) == 'rtl') {
			echo '  <style type="text/css">'."\n".'  @import "style/default-rtl.css";'."\n"."  </style>\n";
		}
		
		echo
		self::jsCommon().
		$head;
		
		# --BEHAVIOR-- adminPageHTMLHead
		$core->callBehavior('adminPageHTMLHead');
		
		echo
		"</head>\n".
		'<body id="dotclear-admin'.
		($safe_mode ? ' safe-mode' : '').
		'">'."\n".
		
		'<div id="top"><h1><a href="index.php">'.DC_VENDOR_NAME.'</a></h1></div>'."\n";
		
		
		echo
		'<div id="info-box">'.
		'<form action="index.php" method="post"><div>'.
		$blog_box.
		'<a href="'.$core->blog->url.'" onclick="window.open(this.href);return false;" title="'.__('Go to site').' ('.__('new window').')'.'">'.__('Go to site').' <img src="images/outgoing.png" alt="" /></a>'.
		'</div></form>'.
		'</div>'.
		'<div id="info-box2"><div>'.
		' '.__('User:').' <strong>'.$core->auth->userID().'</strong>'.
		' - <a href="index.php?logout=1" class="logout">'.__('Logout').' <img src="images/logout.png" alt="" /></a>'.
		'</div>'.
		'</div>';
		
		echo
		'<div id="wrapper">'."\n".
		'<div id="main">'."\n".
		'<div id="content">'."\n";
		
		# Safe mode
		if ($safe_mode)
		{
			echo
			'<div class="error"><h3>'.__('Safe mode').'</h3>'.
			'<p>'.__('You are in safe mode. All plugins have been temporarily disabled. Remind to log out then log in again normally to get back all functionalities').'</p>'.
			'</div>';
		}
		
		if ($core->error->flag()) {
			echo
			'<div class="error"><strong>'.__('Errors:').'</strong>'.
			$core->error->toHTML().
			'</div>';
		}
	}
	
	public static function close()
	{
		$menu =& $GLOBALS['_menu'];
		
		echo
		"</div>\n".		// End of #content
		"</div>\n".		// End of #main
		
		'<div id="main-menu">'."\n";
		
		foreach ($menu as $k => $v) {
			echo $menu[$k]->draw();
		}
		
		echo
		'</div>'."\n".		// End of #main-menu
		'<div id="footer"><p>'.
		sprintf(__('Thank you for using %s.'),'<a href="http://dotclear.org/">Dotclear '.DC_VERSION.'</a>').
		' <span class="credit"> (Icons by <a href="http://dryicons.com/">Dryicons</a>)</span>'.
		'</p></div>'."\n".
		"</div>\n";		// End of #wrapper
		
		if (defined('DC_DEV') && DC_DEV === true) {
			echo self::debugInfo();
		}
		
		echo
		'</body></html>';
	}
	
	public static function openPopup($title='', $head='')
	{
		global $core;
		
		# Display
		header('Content-Type: text/html; charset=UTF-8');
		echo
		'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" '.
		' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n".
		'<html xmlns="http://www.w3.org/1999/xhtml" '.
		'xml:lang="'.$core->auth->getInfo('user_lang').'" '.
		'lang="'.$core->auth->getInfo('user_lang').'">'."\n".
		"<head>\n".
		'  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'."\n".
		'  <title>'.$title.' - '.html::escapeHTML($core->blog->name).' - '.html::escapeHTML(DC_VENDOR_NAME).' - '.DC_VERSION.'</title>'."\n".
		
		'  <meta name="ROBOTS" content="NOARCHIVE,NOINDEX,NOFOLLOW" />'."\n".
		'  <meta name="GOOGLEBOT" content="NOSNIPPET" />'."\n".
		
		self::jsLoadIE7().
		'  <style type="text/css">'."\n". 
		'  @import "style/default.css";'."\n".
		"  </style>\n";
		if (l10n::getTextDirection($GLOBALS['_lang']) == 'rtl') {
			echo '  <style type="text/css">'."\n".'  @import "style/default-rtl.css";'."\n"."  </style>\n";
		}
		
		echo
		self::jsCommon().
		$head;
		
		# --BEHAVIOR-- adminPageHTMLHead
		$core->callBehavior('adminPageHTMLHead');
		
		echo
		"</head>\n".
		'<body id="dotclear-admin" class="popup">'."\n".
		
		'<div id="top"><h1>'.DC_VENDOR_NAME.'</h1></div>'."\n";
		
		echo
		'<div id="wrapper">'."\n".
		'<div id="main">'."\n".
		'<div id="content">'."\n";
		
		if ($core->error->flag()) {
			echo
			'<div class="error"><strong>'.__('Errors:').'</strong>'.
			$core->error->toHTML().
			'</div>';
		}
	}
	
	public static function closePopup()
	{
		echo
		"</div>\n".		// End of #content
		"</div>\n".		// End of #main
		'<div id="footer"><p>&nbsp;</p></div>'."\n".
		"</div>\n".		// End of #wrapper
		'</body></html>';
	}
	
	private static function debugInfo()
	{
		$global_vars = implode(', ',array_keys($GLOBALS));
		
		$res =
		'<div id="debug"><div>'.
		'<p>memory usage: '.memory_get_usage().' ('.files::size(memory_get_usage()).')</p>';
		
		if (function_exists('xdebug_get_profiler_filename'))
		{
			$res .= '<p>Elapsed time: '.xdebug_time_index().' seconds</p>';
			
			$prof_file = xdebug_get_profiler_filename();
			if ($prof_file) {
				$res .= '<p>Profiler file : '.xdebug_get_profiler_filename().'</p>';
			} else {
				$prof_url = http::getSelfURI();
				$prof_url .= (strpos($prof_url,'?') === false) ? '?' : '&amp;';
				$prof_url .= 'XDEBUG_PROFILE';
				$res .= '<p><a href="'.$prof_url.'">Trigger profiler</a></p>';
			}
			
			/* xdebug configuration:
			zend_extension = /.../xdebug.so
			xdebug.auto_trace = On
			xdebug.trace_format = 0
			xdebug.trace_options = 1
			xdebug.show_mem_delta = On
			xdebug.profiler_enable = 0
			xdebug.profiler_enable_trigger = 1
			xdebug.profiler_output_dir = /tmp
			xdebug.profiler_append = 0
			xdebug.profiler_output_name = timestamp
			*/
		}
		
		$res .=
		'<p>Global vars: '.$global_vars.'</p>'.
		'</div></div>';
		
		return $res;
	}
	
	public static function help($page,$index='')
	{
		# Deprecated but we keep this for plugins.
	}
	
	public static function helpBlock()
	{
		$args = func_get_args();
		if (empty($args)) {
			return;
		};
		
		global $__resources;
		if (empty($__resources['help'])) {
			return;
		}
		
		$content = '';
		foreach ($args as $v)
		{
			if (is_object($v) && isset($v->content)) {
				$content .= $v->content;
				continue;
			}
			
			if (!isset($__resources['help'][$v])) {
				continue;
			}
			$f = $__resources['help'][$v];
			if (!file_exists($f) || !is_readable($f)) {
				continue;
			}
			
			$fc = file_get_contents($f);
			if (preg_match('|<body[^>]*?>(.*?)</body>|ms',$fc,$matches)) {
				$content .= $matches[1];
			} else {
				$content .= $fc;
			}
		}
		
		if (trim($content) == '') {
			return;
		}
		
		echo
		'<div id="help"><hr /><div class="help-content clear"><h2>'.__('Help').'</h2>'.
		$content.
		'</div></div>';
	}
	
	public static function jsLoad($src)
	{
		return '<script type="text/javascript" src="'.html::escapeHTML($src).'"></script>'."\n";
	}
	
	public static function jsVar($n,$v)
	{
		return $n." = '".html::escapeJS($v)."';\n";
	}
	
	public static function jsCommon()
	{
		return
		self::jsLoad('js/jquery/jquery.js').
		self::jsLoad('js/jquery/jquery.biscuit.js').
		self::jsLoad('js/jquery/jquery.bgFade.js').
		self::jsLoad('js/common.js').
		
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		self::jsVar('dotclear.nonce',$GLOBALS['core']->getNonce()).
		
		self::jsVar('dotclear.img_plus_src','images/plus.png').
		self::jsVar('dotclear.img_plus_alt',__('uncover')).
		self::jsVar('dotclear.img_minus_src','images/minus.png').
		self::jsVar('dotclear.img_minus_alt',__('hide')).
		self::jsVar('dotclear.img_menu_on','images/menu_on.png').
		self::jsVar('dotclear.img_menu_off','images/menu_off.png').
		
		self::jsVar('dotclear.msg.help',
			__('help')).
		self::jsVar('dotclear.msg.no_selection',
			__('no selection')).
		self::jsVar('dotclear.msg.select_all',
			__('select all')).
		self::jsVar('dotclear.msg.invert_sel',
			__('invert selection')).
		self::jsVar('dotclear.msg.website',
			__('Web site:')).
		self::jsVar('dotclear.msg.email',
			__('Email:')).
		self::jsVar('dotclear.msg.ip_address',
			__('IP address:')).
		self::jsVar('dotclear.msg.error',
			__('Error:')).
		self::jsVar('dotclear.msg.entry_created',
			__('Entry has been successfully created.')).
		self::jsVar('dotclear.msg.edit_entry',
			__('Edit entry')).
		self::jsVar('dotclear.msg.view_entry',
			__('view entry')).
		self::jsVar('dotclear.msg.confirm_delete_posts',
			__("Are you sure you want to delete selected entries (%s)?")).
		self::jsVar('dotclear.msg.confirm_delete_post',
			__("Are you sure you want to delete this entry?")).
		self::jsVar('dotclear.msg.confirm_delete_comments',
			__('Are you sure you want to delete selected comments (%s)?')).
		self::jsVar('dotclear.msg.confirm_delete_comment',
			__('Are you sure you want to delete this comment?')).
		self::jsVar('dotclear.msg.cannot_delete_users',
			__('Users with posts cannot be deleted.')).
		self::jsVar('dotclear.msg.confirm_delete_user',
			__('Are you sure you want to delete selected users (%s)?')).
		self::jsVar('dotclear.msg.confirm_delete_category',
			__('Are you sure you want to delete category "%s"?')).
		self::jsVar('dotclear.msg.confirm_reorder_categories',
			__('Are you sure you want to reorder all categories?')).
		self::jsVar('dotclear.msg.confirm_delete_media',
			__('Are you sure you want to remove media "%s"?')).
		self::jsVar('dotclear.msg.confirm_extract_current',
			__('Are you sure you want to extract archive in current directory?')).
		self::jsVar('dotclear.msg.confirm_remove_attachment',
			__('Are you sure you want to remove attachment "%s"?')).
		self::jsVar('dotclear.msg.confirm_delete_lang',
			__('Are you sure you want to delete "%s" language?')).
		self::jsVar('dotclear.msg.confirm_delete_plugin',
			__('Are you sure you want to delete "%s" plugin?')).
		self::jsVar('dotclear.msg.use_this_theme',
			__('Use this theme')).
		self::jsVar('dotclear.msg.remove_this_theme',
			__('Remove this theme')).
		self::jsVar('dotclear.msg.confirm_delete_theme',
			__('Are you sure you want to delete "%s" theme?')).
		self::jsVar('dotclear.msg.zip_file_content',
			__('Zip file content')).
		self::jsVar('dotclear.msg.xhtml_validator',
			__('XHTML markup validator')).
		self::jsVar('dotclear.msg.xhtml_valid',
			__('XHTML content is valid.')).
		self::jsVar('dotclear.msg.xhtml_not_valid',
			__('There are XHTML markup errors.')).
		self::jsVar('dotclear.msg.confirm_change_post_format',
			__('You have unsaved changes. Switch post format will loose these changes. Proceed anyway?')).
		self::jsVar('dotclear.msg.load_enhanced_uploader',
			__('Loading enhanced uploader, please wait.')).
		"\n//]]>\n".
		"</script>\n";
	}
	
	public static function jsLoadIE7()
	{
		return
		'<!--[if lt IE 8]>'."\n".
		self::jsLoad('js/ie7/IE8.js').
		'<link rel="stylesheet" type="text/css" href="style/iesucks.css" />'."\n".
		'<![endif]-->'."\n";
	}
	
	public static function jsConfirmClose()
	{
		$args = func_get_args();
		if (count($args) > 0) {
			foreach ($args as $k => $v) {
				$args[$k] = "'".html::escapeJS($v)."'";
			}
			$args = implode(',',$args);
		} else {
			$args = '';
		}
		
		return
		self::jsLoad('js/confirm-close.js').
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		"confirmClosePage = new confirmClose(".$args."); ".
		"confirmClose.prototype.prompt = '".html::escapeJS(__('You have unsaved changes.'))."'; ".
		"\n//]]>\n".
		"</script>\n";
	}
	
	public static function jsPageTabs($default=null)
	{
		if ($default) {
			$default = "'".html::escapeJS($default)."'";
		}
		
		return
		self::jsLoad('js/jquery/jquery.pageTabs.js').
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		"\$(function() {\n".
		"	\$.pageTabs(".$default.");\n".
		"});\n".
		"\n//]]>\n".
		"</script>\n";
	}
	
	public static function jsModal()
	{
		return
		'<link rel="stylesheet" type="text/css" href="style/modal/modal.css" />'."\n".
		self::jsLoad('js/jquery/jquery.modal.js').
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		self::jsVar('$.modal.prototype.params.loader_img','style/modal/loader.gif').
		self::jsVar('$.modal.prototype.params.close_img','style/modal/close.png').
		"\n//]]>\n".
		"</script>\n";
	}
	
	public static function jsColorPicker()
	{
		return
		'<link rel="stylesheet" type="text/css" href="style/farbtastic/farbtastic.css" />'."\n".
		self::jsLoad('js/jquery/jquery.farbtastic.js').
		self::jsLoad('js/color-picker.js');
	}
	
	public static function jsDatePicker()
	{
		return
		'<link rel="stylesheet" type="text/css" href="style/date-picker.css" />'."\n".
		self::jsLoad('js/date-picker.js').
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		
		"datePicker.prototype.months[0] = '".html::escapeJS(__('January'))."'; ".
		"datePicker.prototype.months[1] = '".html::escapeJS(__('February'))."'; ".
		"datePicker.prototype.months[2] = '".html::escapeJS(__('March'))."'; ".
		"datePicker.prototype.months[3] = '".html::escapeJS(__('April'))."'; ".
		"datePicker.prototype.months[4] = '".html::escapeJS(__('May'))."'; ".
		"datePicker.prototype.months[5] = '".html::escapeJS(__('June'))."'; ".
		"datePicker.prototype.months[6] = '".html::escapeJS(__('July'))."'; ".
		"datePicker.prototype.months[7] = '".html::escapeJS(__('August'))."'; ".
		"datePicker.prototype.months[8] = '".html::escapeJS(__('September'))."'; ".
		"datePicker.prototype.months[9] = '".html::escapeJS(__('October'))."'; ".
		"datePicker.prototype.months[10] = '".html::escapeJS(__('November'))."'; ".
		"datePicker.prototype.months[11] = '".html::escapeJS(__('December'))."'; ".
		
		"datePicker.prototype.days[0] = '".html::escapeJS(__('Monday'))."'; ".
		"datePicker.prototype.days[1] = '".html::escapeJS(__('Tuesday'))."'; ".
		"datePicker.prototype.days[2] = '".html::escapeJS(__('Wednesday'))."'; ".
		"datePicker.prototype.days[3] = '".html::escapeJS(__('Thursday'))."'; ".
		"datePicker.prototype.days[4] = '".html::escapeJS(__('Friday'))."'; ".
		"datePicker.prototype.days[5] = '".html::escapeJS(__('Saturday'))."'; ".
		"datePicker.prototype.days[6] = '".html::escapeJS(__('Sunday'))."'; ".
		
		"datePicker.prototype.img_src = 'images/date-picker.png'; ".
		
		"datePicker.prototype.close_msg = '".html::escapeJS(__('close'))."'; ".
		"datePicker.prototype.now_msg = '".html::escapeJS(__('now'))."'; ".
		
		"\n//]]>\n".
		"</script>\n";
	}
	
	public static function jsToolBar()
	{
		$tb = new dcToolBar($GLOBALS['core']);
		
		// Add xhtml toolbar
		$tb->addFormatter('xhtml');
		$tb->addSettings('xhtml',array(
			'mode' => 'textareas',
			'relative_urls' => 'false',
			'theme' => 'advanced',
			'skin' => 'dotclear',
			'theme_advanced_toolbar_location' => 'top',
			'theme_advanced_toolbar_align' => 'left',
			'theme_advanced_statusbar_location' => 'bottom',
			'theme_advanced_resizing' => 'true',
			'theme_advanced_resize_horizontal' => 'false',
			'theme_advanced_blockformats' => 'p,pre,h3,h4,h5,h6',
			'paste_auto_cleanup_on_paste' => 'true',
			'forced_root_block' => '',
			'formats' => '{underline: {inline: "ins"},strikethrough: {inline: "del"},inlinecode: {inline: "code"},quote: {inline: "q"}}'
		));
		$tb->addPlugins('xhtml',array(
			'fullscreen' => true,
			'paste' => true,
			'searchreplace' => true,
			'inlinepopups' => true,
			'dcControls' => true
		));
		$tb->addButtons('xhtml',array(
			1 => array(
				'justifyleft',
				'justifycenter',
				'justifyright',
				'separator',
				'bold',
				'italic',
				'underline',
				'strikethrough',
				'inlinecode',
				'quote',
				'sub',
				'sup',
				'separator',
				'blockquote',
				'bullist',
				'numlist',
				'outdent',
				'indent',
				'separator',
				'undo',
				'redo',
				'separator',
				'fullscreen',
				'separator',
				'code'
			),
			2 => array(
				'formatselect',
				'removeformat',
				'cleanup',
				'separator',
				'pastetext',
				'pasteword',
				'separator',
				'search',
				'replace',
				'autosave'
			),
			3 => array(
				'link',
				'unlink',
				'anchor',
				'separator',
				'media',
				'webmedia',
				'separator',
				'hr',
				'charmap',
				'visualchars'
			)
		));
		
		// Add wiki toolbar
		$tb->addFormatter('wiki');
		$tb->addSettings('wiki',array(
			'mode' => 'none',
			'relative_urls' => 'false',
			'theme' => 'advanced',
			'skin' => 'dotclear',
			'theme_advanced_toolbar_location' => 'top',
			'theme_advanced_toolbar_align' => 'left',
			'theme_advanced_statusbar_location' => 'bottom',
			'theme_advanced_resizing' => 'true',
			'theme_advanced_resize_horizontal' => 'false',
			'theme_advanced_path'  => 'false',
			'theme_advanced_blockformats' => 'p,pre,h3,h4,h5,h6',
			'entity_encoding' => 'raw',
			'remove_linebreaks' => 'false',
			'inline_styles' => 'false',
			'convert_fonts_to_spans' => 'false',
			'paste_auto_cleanup_on_paste' => 'true',
			'force_br_newlines' => 'true',
			'force_p_newlines' => 'false',
			'forced_root_block' => '',
			'formats' => '{underline: {inline: "ins"},strikethrough: {inline: "del"},inlinecode: {inline: "code"},quote: {inline: "q"}}'
		));
		$tb->addPlugins('wiki',array(
			'fullscreen' => true,
			'paste' => true,
			'searchreplace' => true,
			'dcControls' => true
		));
		$tb->addButtons('wiki',array(
			1 => array(
				'formatselect',
				'bold',
				'italic',
				'underline',
				'strikethrough',
				'quote',
				'inlinecode',
				'separator',
				'blockquote',
				'bullist',
				'numlist',
				'separator',
				'link',
				'unlink',
				'separator',
				'search',
				'replace',
				'separator',
				'undo',
				'redo',
				'separator',
				'fullscreen',
				'separator',
				'code'
			)
		));
		
		$tb->addI18n('common',array(
			'edit_confirm' => __('Do you want to use the WYSIWYG mode for this textarea?'),
			'apply' => __('Apply'),
			'insert' => __('Insert'),
			'update' => __('Update'),
			'cancel' => __('Cancel'),
			'close' => __('Close'),
			'browse' => __('Browse'),
			'class_name' => __('Class'),
			'not_set' => __('-- Not set --'),
			'clipboard_msg' => __('Copy/Cut/Paste is not available in Mozilla and Firefox. Do you want more information about this issue?'),
			'clipboard_no_support' => __('Currently not supported by your browser, use keyboard shortcuts instead.'),
			'popup_blocked' => __('Sorry, but we have noticed that your popup-blocker has disabled a window that provides application functionality. You will need to disable popup blocking on this site in order to fully utilize this tool.'),
			'invalid_data' => __('{#field} is invalid'),
			'invalid_data_number' => __('{#field} must be a number'),
			'invalid_data_min' => __('{#field} must be a number greater than {#min}'),
			'invalid_data_size' => __('{#field} must be a number or percentage'),
			'more_colors' => __('More colors')
		));
		$tb->addI18n('advanced',array(
			'style_select' => __('Styles'),
			'font_size' => __('Font size'),
			'fontdefault' => __('Font family'),
			'block' => __('Format'),
			'paragraph' => __('Paragraph'),
			'div' => __('Div'),
			'address' => __('Address'),
			'pre' => __('Preformatted'),
			'h1' => __('Heading blog'),
			'h2' => __('Heading entry'),
			'h3' => __('Heading 1'),
			'h4' => __('Heading 2'),
			'h5' => __('Heading 3'),
			'h6' => __('Heading 4'),
			'blockquote' => __('Blockquote'),
			'code' => __('Code'),
			'samp' => __('Code sample'),
			'dt' => __('Definition term '),
			'dd' => __('Definition description'),
			'bold_desc' => __('Bold (Ctrl+B)'),
			'italic_desc' => __('Italic (Ctrl+I)'),
			'underline_desc' => __('Underline (Ctrl+U)'),
			'striketrough_desc' => __('Strikethrough'),
			'justifyleft_desc' => __('Align left'),
			'justifycenter_desc' => __('Align center'),
			'justifyright_desc' => __('Align right'),
			'justifyfull_desc' => __('Align full'),
			'bullist_desc' => __('Unordered list'),
			'numlist_desc' => __('Ordered list'),
			'outdent_desc' => __('Outdent'),
			'indent_desc' => __('Indent'),
			'undo_desc' => __('Undo (Ctrl+Z)'),
			'redo_desc' => __('Redo (Ctrl+Y)'),
			'link_desc' => __('Insert/edit link'),
			'unlink_desc' => __('Unlink'),
			'image_desc' => __('Insert/edit image'),
			'cleanup_desc' => __('Cleanup messy code'),
			'code_desc' => __('Edit HTML Source'),
			'sub_desc' => __('Subscript'),
			'sup_desc' => __('Superscript'),
			'hr_desc' => __('Insert horizontal ruler'),
			'removeformat_desc' => __('Remove formatting'),
			'custom1_desc' => __('Your custom description here'),
			'forecolor_desc' => __('Select text color'),
			'backcolor_desc' => __('Select background color'),
			'charmap_desc' => __('Insert custom character'),
			'visualaid_desc' => __('Toggle guidelines/invisible elements'),
			'anchor_desc' => __('Insert/edit anchor'),
			'cut_desc' => __('Cut'),
			'copy_desc' => __('Copy'),
			'paste_desc' => __('Paste'),
			'image_props_desc' => __('Image properties'),
			'newdocument_desc' => __('New document'),
			'help_desc' => __('Help'),
			'blockquote_desc' => __('Blockquote'),
			'clipboard_msg' => __('Copy/Cut/Paste is not available in Mozilla and Firefox.\r\nDo you want more information about this issue?'),
			'path' => __('Path'),
			'newdocument' => __('Are you sure you want clear all contents?'),
			'toolbar_focus' => __('Jump to tool buttons - Alt+Q, Jump to editor - Alt-Z, Jump to element path - Alt-X'),
			'more_colors' => __('More colors'),
			'shortcuts_desc' => __('Accessibility Help'),
			'help_shortcut' => __('. Press ALT F10 for toolbar. Press ALT 0 for help.'),
			'rich_text_area' => __('Rich Text Area'),
			'toolbar' => __('Toolbar'),
			'anchor_delta_width' => '40',
			'anchor_delta_height' => '50',
			'charmap_delta_width' => '50',
			'charmap_delta_height' => '70',
		));
		$tb->addI18n('advanced_dlg',array(
			'about_title' => __('About TinyMCE'),
			'about_general' => __('About'),
			'about_help' => __('Help'),
			'about_license' => __('License'),
			'about_plugins' => __('Plugins'),
			'about_plugin' => __('Plugin'),
			'about_author' => __('Author'),
			'about_version' => __('Version'),
			'about_loaded' => __('Loaded plugins'),
			'anchor_title' => __('Insert/edit anchor'),
			'anchor_name' => __('Anchor name'),
			'anchor_invalid' => __('Please specify a valid anchor name.'),
			'code_title' => __('HTML Source Editor'),
			'code_wordwrap' => __('Word wrap'),
			'charmap_title' => __('Select custom character'),
			'accessibility_help' => __('Accessibility Help'),
			'accessibility_usage_title' => __('General Usage')
		));
		$tb->addI18n('paste',array(
			'paste_text_desc' => __('Paste as Plain Text'),
			'paste_word_desc' => __('Paste from Word'),
			'selectall_desc' => __('Select All'),
			'plaintext_mode_sticky' => __('Paste is now in plain text mode. Click again to toggle back to regular paste mode. After you paste something you will be returned to regular paste mode.'),
			'plaintext_mode' => __('Paste is now in plain text mode. Click again to toggle back to regular paste mode.')
		));
		$tb->addI18n('paste_dlg',array(
			'text_title' => __('Use CTRL+V on your keyboard to paste the text into the window.'),
			'text_linebreaks' => __('Keep linebreaks'),
			'word_title' => __('Use CTRL+V on your keyboard to paste the text into the window.')
		));
		$tb->addI18n('fullscreen',array('desc' => __('Toggle fullscreen mode')));
		$tb->addI18n('aria',array('rich_text_area' => __('Rich Text Area')));
		$tb->addI18n('dcControls',array(
			'inlinecode_desc' => __('Code'),
			'quote_desc' => __('Quote'),
			'link_desc' => __('Link'),
			'media_desc' => __('Add media from media manager'),
			'webmedia_desc' => __('Add media from web')
		));
		$tb->addI18n('dcControls_dlg',array(
			'provider_not_supported' => __('Provider not supported.'),
			'webmedia_no_information' => __('Impossible to get media information. Please, try again later.'),
			'no_media_loaded' => __('No media loaded. Please, load one beore inserting.')
		));
		
		$res =
		'<script type="text/javascript" src="js/tiny_mce/tiny_mce.js"></script>'.
		'<script type="text/javascript" src="js/dcToolBar.js"></script>'.
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		$tb->getJS().
		"\n//]]>\n".
		"</script>\n";
		
		return $res;
	}
	
	public static function jsCandyUpload($params=array(),$base_url=null)
	{
		if (!$base_url) {
			$base_url = path::clean(dirname(preg_replace('/(\?.*$)?/','',$_SERVER['REQUEST_URI']))).'/';
		}
		
		$params = array_merge($params,array(
			'sess_id='.session_id(),
			'sess_uid='.$_SESSION['sess_browser_uid'],
			'xd_check='.$GLOBALS['core']->getNonce()
		));
		
		return
		'<link rel="stylesheet" type="text/css" href="style/candyUpload/style.css" />'."\n".
		self::jsLoad('js/jquery/jquery.candyUpload.js').
		
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		"dotclear.candyUpload = {};\n".
		self::jsVar('dotclear.msg.activate_enhanced_uploader',__('Activate enhanced uploader')).
		self::jsVar('dotclear.msg.disable_enhanced_uploader',__('Disable enhanced uploader')).
		self::jsVar('$._candyUpload.prototype.locales.file_uploaded',__('File successfully uploaded.')).
		self::jsVar('$._candyUpload.prototype.locales.max_file_size',__('Maximum file size allowed:')).
		self::jsVar('$._candyUpload.prototype.locales.limit_exceeded',__('Limit exceeded.')).
		self::jsVar('$._candyUpload.prototype.locales.size_limit_exceeded',__('File size exceeds allowed limit.')).
		self::jsVar('$._candyUpload.prototype.locales.canceled',__('Canceled.')).
		self::jsVar('$._candyUpload.prototype.locales.http_error',__('HTTP Error:')).
		self::jsVar('$._candyUpload.prototype.locales.error',__('Error:')).
		self::jsVar('$._candyUpload.prototype.locales.choose_file',__('Choose file')).
		self::jsVar('$._candyUpload.prototype.locales.choose_files',__('Choose files')).
		self::jsVar('$._candyUpload.prototype.locales.cancel',__('Cancel')).
		self::jsVar('$._candyUpload.prototype.locales.clean',__('Clean')).
		self::jsVar('$._candyUpload.prototype.locales.upload',__('Upload')).
		self::jsVar('$._candyUpload.prototype.locales.no_file_in_queue',__('No file in queue.')).
		self::jsVar('$._candyUpload.prototype.locales.file_in_queue',__('1 file in queue.')).
		self::jsVar('$._candyUpload.prototype.locales.files_in_queue',__('%d files in queue.')).
		self::jsVar('$._candyUpload.prototype.locales.queue_error',__('Queue error:')).
		self::jsVar('dotclear.candyUpload.base_url',$base_url).
		self::jsVar('dotclear.candyUpload.movie_url',$base_url.'index.php?pf=swfupload.swf').
		self::jsVar('dotclear.candyUpload.params',implode('&',$params)).
		"\n//]]>\n".
		"</script>\n";
	}
	
	public static function jsToolMan()
	{
		return
		self::jsLoad('js/tool-man/core.js').
		self::jsLoad('js/tool-man/events.js').
		self::jsLoad('js/tool-man/css.js').
		self::jsLoad('js/tool-man/coordinates.js').
		self::jsLoad('js/tool-man/drag.js').
		self::jsLoad('js/tool-man/dragsort.js').
		self::jsLoad('js/dragsort-tablerows.js');
	}
	
	public static function jsMetaEditor()
	{
		return self::jsLoad('js/meta-editor.js');
	}
	
	public static function jsOEmbed()
	{
		return self::jsLoad('js/jquery/jquery.oembed.js');
	}
}
?>