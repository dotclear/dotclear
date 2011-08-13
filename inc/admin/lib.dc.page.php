<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2011 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_RC_PATH')) { return; }

define('DC_AUTH_PAGE','auth.php');

class dcPage
{
	private static $loaded_js=array();

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
			form::combo('switchblog',$blogs,$core->blog->id).
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
		'  	<link rel="stylesheet" href="style/default.css" type="text/css" media="screen" />'."\n"; 
		if (l10n::getTextDirection($GLOBALS['_lang']) == 'rtl') {
			echo
		'  	<link rel="stylesheet" href="style/default-rtl.css" type="text/css" media="screen" />'."\n"; 
		}

		$core->auth->user_prefs->addWorkspace('interface');
		$user_ui_hide_std_favicon = $core->auth->user_prefs->interface->hide_std_favicon;
		if (!$user_ui_hide_std_favicon) {
			echo '<link rel="icon" type="image/png" href="images/favicon.png" />';
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
		
		'<div id="header">'.
		'<ul id="prelude"><li><a href="#content">Aller au contenu</a></li><li><a href="#main-menu">Aller au menu</a></li></ul>'."\n".
		'<div id="top"><h1><a href="index.php">'.DC_VENDOR_NAME.'</a></h1></div>'."\n";	
		
		echo
		'<div id="info-boxes">'.
		'<div id="info-box1">'.
		'<form action="index.php" method="post">'.
		$blog_box.
		'<a href="'.$core->blog->url.'" onclick="window.open(this.href);return false;" title="'.__('Go to site').' ('.__('new window').')'.'">'.__('Go to site').' <img src="images/outgoing.png" alt="" /></a>'.
		'</form>'.
		'</div>'.
		'<div id="info-box2">'.
		'<a'.(preg_match('/index.php$/',$_SERVER['REQUEST_URI']) ? ' class="active"' : '').' href="index.php">'.__('My dashboard').'</a>'.
		'<span> | </span><a'.(preg_match('/preferences.php(\?.*)?$/',$_SERVER['REQUEST_URI']) ? ' class="active"' : '').' href="preferences.php">'.__('My preferences').'</a>'.
		'<span> | </span><a href="index.php?logout=1" class="logout">'.sprintf(__('Logout %s'),$core->auth->userID()).' <img src="images/logout.png" alt="" /></a>'.
		'</div>'.
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
		'  	<link rel="stylesheet" href="style/default.css" type="text/css" media="screen" />'."\n"; 
		if (l10n::getTextDirection($GLOBALS['_lang']) == 'rtl') {
			echo
			'  	<link rel="stylesheet" href="style/default-rtl.css" type="text/css" media="screen" />'."\n"; 
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
				$prof_url .= (strpos($prof_url,'?') === false) ? '?' : '&';
				$prof_url .= 'XDEBUG_PROFILE';
				$res .= '<p><a href="'.html::escapeURL($prof_url).'">Trigger profiler</a></p>';
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
		$escaped_src = html::escapeHTML($src);
		if (!isset(self::$loaded_js[$escaped_src])) {
			self::$loaded_js[$escaped_src]=true;
			return '<script type="text/javascript" src="'.$escaped_src.'"></script>'."\n";
		}
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
		self::jsLoad('js/prelude.js').
		
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
		$res =
		'<link rel="stylesheet" type="text/css" href="style/jsToolBar/jsToolBar.css" />'.
		'<script type="text/javascript" src="js/jsToolBar/jsToolBar.js"></script>';
		
		if (isset($GLOBALS['core']->auth) && $GLOBALS['core']->auth->getOption('enable_wysiwyg')) {
			$res .= '<script type="text/javascript" src="js/jsToolBar/jsToolBar.wysiwyg.js"></script>';
		}
		
		$res .=
		'<script type="text/javascript" src="js/jsToolBar/jsToolBar.dotclear.js"></script>'.
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		"jsToolBar.prototype.dialog_url = 'popup.php'; ".
		"jsToolBar.prototype.iframe_css = '".
			'body{'.
				'font: 12px "DejaVu Sans","Lucida Grande","Lucida Sans Unicode",Arial,sans-serif;'.
				'color : #000;'.
				'background: #f9f9f9;'.
				'margin: 0;'.
				'padding : 2px;'.
				'border: none;'.
				(l10n::getTextDirection($GLOBALS['_lang']) == 'rtl' ? 'direction:rtl;' : '').
			'}'.
			'pre, code, kbd, samp {'.
				'font-family:"Courier New",Courier,monospace;'.
				'font-size : 1.1em;'.
			'}'.
			'code {'.
				'color : #666;'.
				'font-weight : bold;'.
			'}'.
			'body > p:first-child {'.
				'margin-top: 0;'.
			'}'.
		"'; ".
		"jsToolBar.prototype.base_url = '".html::escapeJS($GLOBALS['core']->blog->host)."'; ".
		"jsToolBar.prototype.switcher_visual_title = '".html::escapeJS(__('visual'))."'; ".
		"jsToolBar.prototype.switcher_source_title = '".html::escapeJS(__('source'))."'; ".
		"jsToolBar.prototype.legend_msg = '".
		html::escapeJS(__('You can use the following shortcuts to format your text.'))."'; ".
		"jsToolBar.prototype.elements.blocks.options.none = '".html::escapeJS(__('-- none --'))."'; ".
		"jsToolBar.prototype.elements.blocks.options.nonebis = '".html::escapeJS(__('-- block format --'))."'; ".
		"jsToolBar.prototype.elements.blocks.options.p = '".html::escapeJS(__('Paragraph'))."'; ".
		"jsToolBar.prototype.elements.blocks.options.h1 = '".html::escapeJS(__('Level 1 header'))."'; ".
		"jsToolBar.prototype.elements.blocks.options.h2 = '".html::escapeJS(__('Level 2 header'))."'; ".
		"jsToolBar.prototype.elements.blocks.options.h3 = '".html::escapeJS(__('Level 3 header'))."'; ".
		"jsToolBar.prototype.elements.blocks.options.h4 = '".html::escapeJS(__('Level 4 header'))."'; ".
		"jsToolBar.prototype.elements.blocks.options.h5 = '".html::escapeJS(__('Level 5 header'))."'; ".
		"jsToolBar.prototype.elements.blocks.options.h6 = '".html::escapeJS(__('Level 6 header'))."'; ".
		"jsToolBar.prototype.elements.strong.title = '".html::escapeJS(__('Strong emphasis'))."'; ".
		"jsToolBar.prototype.elements.em.title = '".html::escapeJS(__('Emphasis'))."'; ".
		"jsToolBar.prototype.elements.ins.title = '".html::escapeJS(__('Inserted'))."'; ".
		"jsToolBar.prototype.elements.del.title = '".html::escapeJS(__('Deleted'))."'; ".
		"jsToolBar.prototype.elements.quote.title = '".html::escapeJS(__('Inline quote'))."'; ".
		"jsToolBar.prototype.elements.code.title = '".html::escapeJS(__('Code'))."'; ".
		"jsToolBar.prototype.elements.br.title = '".html::escapeJS(__('Line break'))."'; ".
		"jsToolBar.prototype.elements.blockquote.title = '".html::escapeJS(__('Blockquote'))."'; ".
		"jsToolBar.prototype.elements.pre.title = '".html::escapeJS(__('Preformated text'))."'; ".
		"jsToolBar.prototype.elements.ul.title = '".html::escapeJS(__('Unordered list'))."'; ".
		"jsToolBar.prototype.elements.ol.title = '".html::escapeJS(__('Ordered list'))."'; ".
		
		"jsToolBar.prototype.elements.link.title = '".html::escapeJS(__('Link'))."'; ".
		"jsToolBar.prototype.elements.link.href_prompt = '".html::escapeJS(__('URL?'))."'; ".
		"jsToolBar.prototype.elements.link.hreflang_prompt = '".html::escapeJS(__('Language?'))."'; ".
		
		"jsToolBar.prototype.elements.img.title = '".html::escapeJS(__('External image'))."'; ".
		"jsToolBar.prototype.elements.img.src_prompt = '".html::escapeJS(__('URL?'))."'; ".
		
		"jsToolBar.prototype.elements.img_select.title = '".html::escapeJS(__('Media chooser'))."'; ".
		"jsToolBar.prototype.elements.post_link.title = '".html::escapeJS(__('Link to an entry'))."'; ";
		
		if (!$GLOBALS['core']->auth->check('media,media_admin',$GLOBALS['core']->blog->id)) {
			$res .= "jsToolBar.prototype.elements.img_select.disabled = true;\n";
		}
		
		$res .=
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
		'<script type="text/javascript" src="js/tool-man/core.js"></script>'.
		'<script type="text/javascript" src="js/tool-man/events.js"></script>'.
		'<script type="text/javascript" src="js/tool-man/css.js"></script>'.
		'<script type="text/javascript" src="js/tool-man/coordinates.js"></script>'.
		'<script type="text/javascript" src="js/tool-man/drag.js"></script>'.
		'<script type="text/javascript" src="js/tool-man/dragsort.js"></script>'.
		'<script type="text/javascript" src="js/dragsort-tablerows.js"></script>';
	}
	
	public static function jsMetaEditor()
	{
		return
		'<script type="text/javascript" src="js/meta-editor.js"></script>';
	}
}
?>