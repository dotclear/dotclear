<?php

class dcAdminContext extends Twig_Extension {
	protected $loaded_js;
	protected $js;
	protected $js_var;
	protected $head;
	
	protected $core;
	public function __construct($core) {
		$this->core = $core;
		$this->js = array();
		$this->js_var = array();
		$this->loaded_js = array();
		$this->head = array();
		$this->jsCommon();
		$this->blogs = array();
		if ($this->core->auth->blog_count > 1 && $this->core->auth->blog_count < 20) {
			$rs_blogs = $core->getBlogs(array('order'=>'LOWER(blog_name)','limit'=>20));
			while ($rs_blogs->fetch()) {
				$this->blogs[html::escapeHTML($rs_blogs->blog_name.' - '.$rs_blogs->blog_url)] = $rs_blogs->blog_id;
			}
		}
		$this->blog = array();
		if ($this->core->auth->blog_count) { // no blog on auth.php
			$this->blog = array(
				'url' => $core->blog->url,
				'name' => $core->blog->name
			);
		}
	}
	
	public function jsLoad($src)
	{
		$escaped_src = html::escapeHTML($src);
		if (!isset($this->loaded_js[$escaped_src])) {
			$this->loaded_js[$escaped_src]=true;
			$this->js[]='<script type="text/javascript" src="'.$escaped_src.'"></script>';
		}
		return $this;
	}

	public function jsAdd($code)
	{
		$this->js[]=$code;
		return $this;
	}

	public function head($code) {
		$this->head[]=$code;
		return $this;
	}
	public function jsVar($n,$v)
	{
		$this->js_vars[$n] = $v;
	}

	public function jsVars($arr)
	{
		foreach($arr as $n => $v) {
			$this->js_vars[$n] = $v;
		}
		return $this;
	}	

	public function getJS(){
		$jsvars = array();
		
		foreach ($this->js_vars as $n => $v) {
			$jsvars[] = $n." = '".html::escapeJS($v)."';";
		}
		return join("\n",$this->head).
			join("\n",$this->js).
			'<script type="text/javascript">'."\n".
			"//<![CDATA[\n".
				join("\n",$jsvars).
			"\n//]]>\n".
			"</script>\n";;
	}

	public function pageHead() {
		global $core;
		$this->jsLoadIE7();
		echo '	<link rel="stylesheet" href="style/default.css" type="text/css" media="screen" />'."\n"; 
		if (l10n::getTextDirection($GLOBALS['_lang']) == 'rtl') {
			echo
		'  	<link rel="stylesheet" href="style/default-rtl.css" type="text/css" media="screen" />'."\n"; 
		}
		$core->auth->user_prefs->addWorkspace('interface');
		$user_ui_hide_std_favicon = $core->auth->user_prefs->interface->hide_std_favicon;
		if (!$user_ui_hide_std_favicon) {
			echo '<link rel="icon" type="image/png" href="images/favicon.png" />';
		}
		echo $this->getJS();
	}
	
	public function pageMenu() {
		$menu =& $GLOBALS['_menu'];
		foreach ($menu as $k => $v) {
			echo $menu[$k]->draw();
		}
	}
	public function getFunctions()
	{
		return array(
			'page_head' => new Twig_Function_Method($this, 'pageHead', array('is_safe' => array('html'))),
			'page_menu' => new Twig_Function_Method($this, 'pageMenu', array('is_safe' => array('html'))),
			'__' => new Twig_Function_Function("__", array('is_safe' => array('html')))
		);
	}
	public function getGlobals() {
		return array();
	}
	public function getName() {
		return 'AdminPage';
	}
	public function getFilters()
	{
		return array(
			'trans' => new Twig_Filter_Function("__", array('is_safe' => array('html')))
		);
	}


	public function jsCommon() {
		return $this->jsVars (array(
			'dotclear.nonce'			=> $GLOBALS['core']->getNonce(),
			'dotclear.img_plus_src'		=> 'images/plus.png',
			'dotclear.img_plus_alt'		=> __('uncover'),
			'dotclear.img_minus_src'	=> 'images/minus.png',
			'dotclear.img_minus_alt'	=> __('hide'),
			'dotclear.img_menu_on'		=> 'images/menu_on.png',
			'dotclear.img_menu_off'		=> 'images/menu_off.png',
			'dotclear.msg.help'			=> __('help'),
			'dotclear.msg.no_selection'	=> __('no selection'),
			'dotclear.msg.select_all'	=> __('select all'),
			'dotclear.msg.invert_sel'	=> __('invert selection'),
			'dotclear.msg.website'		=> __('Web site:'),
			'dotclear.msg.email'		=> __('Email:'),
			'dotclear.msg.ip_address'	=> __('IP address:'),
			'dotclear.msg.error'		=> __('Error:'),
			'dotclear.msg.entry_created'=> __('Entry has been successfully created.'),
			'dotclear.msg.edit_entry'	=> __('Edit entry'),
			'dotclear.msg.view_entry'	=> __('view entry'),
			'dotclear.msg.confirm_delete_posts' =>
				__("Are you sure you want to delete selected entries (%s)?"),
			'dotclear.msg.confirm_delete_post' =>
				__("Are you sure you want to delete this entry?"),
			'dotclear.msg.confirm_delete_comments' =>
				__('Are you sure you want to delete selected comments (%s)?'),
			'dotclear.msg.confirm_delete_comment' =>
				__('Are you sure you want to delete this comment?'),
			'dotclear.msg.cannot_delete_users' =>
				__('Users with posts cannot be deleted.'),
			'dotclear.msg.confirm_delete_user' =>
				__('Are you sure you want to delete selected users (%s)?'),
			'dotclear.msg.confirm_delete_category' =>
				__('Are you sure you want to delete category "%s"?'),
			'dotclear.msg.confirm_reorder_categories' =>
				__('Are you sure you want to reorder all categories?'),
			'dotclear.msg.confirm_delete_media' =>
				__('Are you sure you want to remove media "%s"?'),
			'dotclear.msg.confirm_extract_current' =>
				__('Are you sure you want to extract archive in current directory?'),
			'dotclear.msg.confirm_remove_attachment' =>
				__('Are you sure you want to remove attachment "%s"?'),
			'dotclear.msg.confirm_delete_lang' =>
				__('Are you sure you want to delete "%s" language?'),
			'dotclear.msg.confirm_delete_plugin' =>
				__('Are you sure you want to delete "%s" plugin?'),
			'dotclear.msg.use_this_theme' => __('Use this theme'),
			'dotclear.msg.remove_this_theme' => __('Remove this theme'),
			'dotclear.msg.confirm_delete_theme' =>
				__('Are you sure you want to delete "%s" theme?'),
			'dotclear.msg.zip_file_content' => __('Zip file content'),
			'dotclear.msg.xhtml_validator' => __('XHTML markup validator'),
			'dotclear.msg.xhtml_valid' => __('XHTML content is valid.'),
			'dotclear.msg.xhtml_not_valid' => __('There are XHTML markup errors.'),
			'dotclear.msg.confirm_change_post_format' =>
				__('You have unsaved changes. Switch post format will loose these changes. Proceed anyway?'),
			'dotclear.msg.load_enhanced_uploader' => __('Loading enhanced uploader =>please wait.')))
			->jsLoad('js/jquery/jquery.js')
			->jsLoad('js/jquery/jquery.biscuit.js')
			->jsLoad('js/jquery/jquery.bgFade.js')
			->jsLoad('js/jquery/jquery.constantfooter.js')
			->jsLoad('js/common.js')
			->jsLoad('js/prelude.js');
	}

	public function jsLoadIE7()
	{
		return $this->jsAdd(
		'<!--[if lt IE 8]>'."\n".
		'<script type="text/javascript" src="js/ie7/IE8.js"></script>'."\n".
		'<link rel="stylesheet" type="text/css" href="style/iesucks.css" />'."\n".
		'<![endif]-->');
	}
	
	public function jsConfirmClose()
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
		
		$this->jsLoad('js/confirm-close.js');
		$this->jsAdd(
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		"confirmClosePage = new confirmClose(".$args."); ".
		"confirmClose.prototype.prompt = '".html::escapeJS(__('You have unsaved changes.'))."'; ".
		"\n//]]>\n".
		"</script>\n");
		return $this;
	}
	
	public function jsPageTabs($default=null)
	{
		if ($default) {
			$default = "'".html::escapeJS($default)."'";
		}
		
		return $this
			->jsLoad('js/jquery/jquery.pageTabs.js')
			->jsAdd('<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		"\$(function() {\n".
		"	\$.pageTabs(".$default.");\n".
		"});\n".
		"\n//]]>\n".
		"</script>\n");
	}
	
	public function jsModal()
	{
		return $this
			->head(
		'<link rel="stylesheet" type="text/css" href="style/modal/modal.css" />')
			->jsLoad('js/jquery/jquery.modal.js')
			->jsVars(array(
				'$.modal.prototype.params.loader_img' =>'style/modal/loader.gif',
				'$.modal.prototype.params.close_img' =>'style/modal/close.png'
			));
	}
	
	public static function jsColorPicker()
	{
		return
		'<link rel="stylesheet" type="text/css" href="style/farbtastic/farbtastic.css" />'."\n".
		self::jsLoad('js/jquery/jquery.farbtastic.js').
		self::jsLoad('js/color-picker.js');
	}
	
	public function jsDatePicker()
	{
		$this
			->head(
		'<link rel="stylesheet" type="text/css" href="style/date-picker.css" />')
			->jsLoad('js/date-picker.js')
			->jsVars(array(		
				"datePicker.prototype.months[0]" => __('January'),
				"datePicker.prototype.months[1]" => __('February'),
				"datePicker.prototype.months[2]" => __('March'),
				"datePicker.prototype.months[3]" => __('April'),
				"datePicker.prototype.months[4]" => __('May'),
				"datePicker.prototype.months[5]" => __('June'),
				"datePicker.prototype.months[6]" => __('July'),
				"datePicker.prototype.months[7]" => __('August'),
				"datePicker.prototype.months[8]" => __('September'),
				"datePicker.prototype.months[9]" => __('October'),
				"datePicker.prototype.months[10]" => __('November'),
				"datePicker.prototype.months[11]" => __('December'),
				"datePicker.prototype.days[0]" => __('Monday'),
				"datePicker.prototype.days[1]" => __('Tuesday'),
				"datePicker.prototype.days[2]" => __('Wednesday'),
				"datePicker.prototype.days[3]" => __('Thursday'),
				"datePicker.prototype.days[4]" => __('Friday'),
				"datePicker.prototype.days[5]" => __('Saturday'),
				"datePicker.prototype.days[6]" => __('Sunday'),
				
				"datePicker.prototype.img_src" => 'images/date-picker.png',
				
				"datePicker.prototype.close_msg" => __('close'),
				"datePicker.prototype.now_msg" => __('now')));
		return $this;
	}
	
	public function jsToolBar()
	{
		$this
			->head(
		'<link rel="stylesheet" type="text/css" href="style/jsToolBar/jsToolBar.css" />')
			->jsLoad("js/jsToolBar/jsToolBar.js");
		
		if (isset($GLOBALS['core']->auth) && $GLOBALS['core']->auth->getOption('enable_wysiwyg')) {
			$this->jsLoad("js/jsToolBar/jsToolBar.wysiwyg.js");
		}
		
		$this->jsLoad("js/jsToolBar/jsToolBar.dotclear.js")
		->jsVars(array(
		
		"jsToolBar.prototype.dialog_url" => 'popup.php',
		"jsToolBar.prototype.iframe_css" =>
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
			'}',
		"jsToolBar.prototype.base_url" => $GLOBALS['core']->blog->host,
		"jsToolBar.prototype.switcher_visual_title" => __('visual'),
		"jsToolBar.prototype.switcher_source_title" => __('source'),
		"jsToolBar.prototype.legend_msg" =>
			__('You can use the following shortcuts to format your text.'),
		"jsToolBar.prototype.elements.blocks.options.none" => __('-- none --'),
		"jsToolBar.prototype.elements.blocks.options.nonebis" => __('-- block format --'),
		"jsToolBar.prototype.elements.blocks.options.p" => __('Paragraph'),
		"jsToolBar.prototype.elements.blocks.options.h1" => __('Level 1 header'),
		"jsToolBar.prototype.elements.blocks.options.h2" => __('Level 2 header'),
		"jsToolBar.prototype.elements.blocks.options.h3" => __('Level 3 header'),
		"jsToolBar.prototype.elements.blocks.options.h4" => __('Level 4 header'),
		"jsToolBar.prototype.elements.blocks.options.h5" => __('Level 5 header'),
		"jsToolBar.prototype.elements.blocks.options.h6" => __('Level 6 header'),
		"jsToolBar.prototype.elements.strong.title" => __('Strong emphasis'),
		"jsToolBar.prototype.elements.em.title" => __('Emphasis'),
		"jsToolBar.prototype.elements.ins.title" => __('Inserted'),
		"jsToolBar.prototype.elements.del.title" => __('Deleted'),
		"jsToolBar.prototype.elements.quote.title" => __('Inline quote'),
		"jsToolBar.prototype.elements.code.title" => __('Code'),
		"jsToolBar.prototype.elements.br.title" => __('Line break'),
		"jsToolBar.prototype.elements.blockquote.title" => __('Blockquote'),
		"jsToolBar.prototype.elements.pre.title" => __('Preformated text'),
		"jsToolBar.prototype.elements.ul.title" => __('Unordered list'),
		"jsToolBar.prototype.elements.ol.title" => __('Ordered list'),
		
		"jsToolBar.prototype.elements.link.title" => __('Link'),
		"jsToolBar.prototype.elements.link.href_prompt" => __('URL?'),
		"jsToolBar.prototype.elements.link.hreflang_prompt" => __('Language?'),
		
		"jsToolBar.prototype.elements.img.title" => __('External image'),
		"jsToolBar.prototype.elements.img.src_prompt" => __('URL?'),
		
		"jsToolBar.prototype.elements.img_select.title" => __('Media chooser'),
		"jsToolBar.prototype.elements.post_link.title" => __('Link to an entry')));
		
		if (!$GLOBALS['core']->auth->check('media,media_admin',$GLOBALS['core']->blog->id)) {
			$this->jsVar("jsToolBar.prototype.elements.img_select.disabled",true);
		}
				
		return $this;
	}
	
	public function jsCandyUpload($params=array(),$base_url=null)
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
	
	public function jsMetaEditor()
	{
		return $this->jsLoad("js/meta-editor.js");
	}

}
?>
