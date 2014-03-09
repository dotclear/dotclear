<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

class dcLegacyEditorBehaviors
{
	protected static $p_url = 'index.php?pf=dcLegacyEditor';

	public static function adminPostEditor($editor='') {
        global $core;

		if (!$core->auth->getOption('editor') || $core->auth->getOption('editor')!='dcLegacyEditor') {return;}

		return
			self::jsToolBar().
			dcPage::jsLoad(self::$p_url.'/js/_post_editor.js');
	}

	public static function adminPopupMedia() {
		return dcPage::jsLoad(self::$p_url.'/js/jsToolBar/popup_media.js');
	}

	public static function adminPopupLink() {
		return dcPage::jsLoad(self::$p_url.'/js/jsToolBar/popup_link.js');
	}

	public static function adminPopupPosts() {
		return dcPage::jsLoad(self::$p_url.'/js/jsToolBar/popup_posts.js');
	}

	protected static function jsToolBar() {
		$res =
		'<link rel="stylesheet" type="text/css" href="'.self::$p_url.'/css/jsToolBar/jsToolBar.css" />'.
		'<script type="text/javascript" src="'.self::$p_url.'/js/jsToolBar/jsToolBar.js"></script>';

		if (isset($GLOBALS['core']->auth) && $GLOBALS['core']->auth->getOption('enable_wysiwyg')) {
			$res .= '<script type="text/javascript" src="'.self::$p_url.'/js/jsToolBar/jsToolBar.wysiwyg.js"></script>';
		}

		$res .=
		'<script type="text/javascript" src="'.self::$p_url.'/js/jsToolBar/jsToolBar.dotclear.js"></script>'.
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
		"jsToolBar.prototype.elements.post_link.title = '".html::escapeJS(__('Link to an entry'))."'; ".
		"jsToolBar.prototype.elements.removeFormat = jsToolBar.prototype.elements.removeFormat || {}; ".
		"jsToolBar.prototype.elements.removeFormat.title = '".html::escapeJS(__('Remove text formating'))."'; ";

		if (!$GLOBALS['core']->auth->check('media,media_admin',$GLOBALS['core']->blog->id)) {
			$res .= "jsToolBar.prototype.elements.img_select.disabled = true;\n";
		}

		$res .=
		"\n//]]>\n".
		"</script>\n";

		return $res;
	}
}