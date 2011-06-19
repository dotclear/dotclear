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
if (!defined('DC_CONTEXT_ADMIN')) { return; }

l10n::set(dirname(__FILE__).'/locales/'.$_lang.'/main');

$fonts = array(
	__('default') => '',
	__('Ductile primary') => 'Ductile body',
	__('Ductile secondary') => 'Ductile alternate',
	__('Times New Roman') => 'Times New Roman',
	__('Georgia') => 'Georgia',
	__('Garamond') => 'Garamond',
	__('Helvetica/Arial') => 'Helvetica/Arial',
	__('Verdana') => 'Verdana',
	__('Trebuchet MS') => 'Trebuchet MS',
	__('Impact') => 'Impact',
	__('Monospace') => 'Monospace'
);

function adjustFontSize($s)
{
	if (preg_match('/^([0-9.]+)\s*(%|pt|px|em|ex)?$/',$s,$m)) {
		if (empty($m[2])) {
			$m[2] = 'em';
		}
		return $m[1].$m[2];
	}

	return null;
}

function adjustColor($c)
{
	if ($c === '') {
		return '';
	}

	$c = strtoupper($c);

	if (preg_match('/^[A-F0-9]{3,6}$/',$c)) {
		$c = '#'.$c;
	}

	if (preg_match('/^#[A-F0-9]{6}$/',$c)) {
		return $c;
	}

	if (preg_match('/^#[A-F0-9]{3,}$/',$c)) {
		return '#'.substr($c,1,1).substr($c,1,1).substr($c,2,1).substr($c,2,1).substr($c,3,1).substr($c,3,1);
	}

	return '';
}

$ductile_base = array(
	// HTML
	'subtitle_hidden' => null,
	// CSS
	'body_font' => null,
	'alternate_font' => null,
	'blog_title_w' => null,
	'blog_title_s' => null,
	'blog_title_c' => null,
	'post_title_w' => null,
	'post_title_s' => null,
	'post_title_c' => null,
	'post_link_w' => null,
	'post_link_v_c' => null,
	'post_link_f_c' => null,
	'blog_title_w_m' => null,
	'blog_title_s_m' => null,
	'blog_title_c_m' => null,
	'post_title_w_m' => null,
	'post_title_s_m' => null,
	'post_title_c_m' => null
);

$ductile_user = $core->blog->settings->themes->ductile_style;
$ductile_user = @unserialize($ductile_user);
if (!is_array($ductile_user)) {
	$ductile_user = array();
}

$ductile_user = array_merge($ductile_base,$ductile_user);

$conf_tab = isset($_POST['conf_tab']) ? $_POST['conf_tab'] : 'html';

if (!empty($_POST))
{
	try
	{
		# HTML
		if ($conf_tab == 'html') {
			$ductile_user['subtitle_hidden'] = (integer) !empty($_POST['subtitle_hidden']);
		}
		
		# CSS
		if ($conf_tab == 'css') {
			$ductile_user['body_font'] = $_POST['body_font'];
			$ductile_user['alternate_font'] = $_POST['alternate_font'];

			$ductile_user['blog_title_w'] = (integer) !empty($_POST['blog_title_w']);
			$ductile_user['blog_title_s'] = adjustFontSize($_POST['blog_title_s']);
			$ductile_user['blog_title_c'] = adjustColor($_POST['blog_title_c']);
		
			$ductile_user['post_title_w'] = (integer) !empty($_POST['post_title_w']);
			$ductile_user['post_title_s'] = adjustFontSize($_POST['post_title_s']);
			$ductile_user['post_title_c'] = adjustColor($_POST['post_title_c']);
		
			$ductile_user['post_link_w'] = (integer) !empty($_POST['post_link_w']);
			$ductile_user['post_link_v_c'] = adjustColor($_POST['post_link_v_c']);
			$ductile_user['post_link_f_c'] = adjustColor($_POST['post_link_f_c']);
		
			$ductile_user['blog_title_w_m'] = (integer) !empty($_POST['blog_title_w_m']);
			$ductile_user['blog_title_s_m'] = adjustFontSize($_POST['blog_title_s_m']);
			$ductile_user['blog_title_c_m'] = adjustColor($_POST['blog_title_c_m']);
		
			$ductile_user['post_title_w_m'] = (integer) !empty($_POST['post_title_w_m']);
			$ductile_user['post_title_s_m'] = adjustFontSize($_POST['post_title_s_m']);
			$ductile_user['post_title_c_m'] = adjustColor($_POST['post_title_c_m']);
		}
		
		$core->blog->settings->addNamespace('themes');
		$core->blog->settings->themes->put('ductile_style',serialize($ductile_user));
		$core->blog->triggerBlog();

		echo
		'<div class="message"><p>'.
		__('Theme configuration upgraded.').
		'</p></div>';
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
	}
}

echo '</form>';

# HTML Tab

echo '<div class="multi-part" id="themes-list'.($conf_tab == 'html' ? '' : '-html').'" title="'.__('Content').'">';

echo '<form id="theme_config" action="blog_theme.php?conf=1" method="post" enctype="multipart/form-data">';

echo '<fieldset><legend>'.__('Header').'</legend>'.
'<p class="field"><label for="subtitle_hidden">'.__('Hide blog description:').' '.
form::checkbox('subtitle_hidden',1,$ductile_user['subtitle_hidden']).'</label>'.'</p>'.
'</fieldset>';

echo '<input type="hidden" name="conf_tab" value="html">';
echo '<p class="clear"><input type="submit" value="'.__('Save').'" />'.$core->formNonce().'</p>';
echo '</form>';

echo '</div>'; // Close tab

# CSS tab

echo '<div class="multi-part" id="themes-list'.($conf_tab == 'css' ? '' : '-css').'" title="'.__('Presentation').'">';

echo '<form id="theme_config" action="blog_theme.php?conf=1" method="post" enctype="multipart/form-data">';

echo '<h3>'.__('General settings').'</h3>';

echo '<fieldset><legend>'.__('Fonts').'</legend>'.
'<p class="field"><label for="body_font">'.__('Main:').' '.
form::combo('body_font',$fonts,$ductile_user['body_font']).'</label></p>'.

'<p class="field"><label for="alternate_font">'.__('Secondary:').' '.
form::combo('alternate_font',$fonts,$ductile_user['alternate_font']).'</label></p>'.
'</fieldset>';

echo '<div class="two-cols">';
echo '<div class="col">';

echo '<fieldset><legend>'.__('Blog title').'</legend>'.
'<p class="field"><label for="blog_title_w">'.__('In bold:').' '.
form::checkbox('blog_title_w',1,$ductile_user['blog_title_w']).'</label>'.'</p>'.

'<p class="field"><label for="blog_title_s">'.__('Font size:').'</label> '.
form::field('blog_title_s',7,7,$ductile_user['blog_title_s']).' '.__('(in em by default)').'</p>'.

'<p class="field picker"><label for="blog_title_c">'.__('Color:').'</label> '.
form::field('blog_title_c',7,7,$ductile_user['blog_title_c'],'colorpicker').'</p>'.
'</fieldset>';

echo '</div>';
echo '<div class="col">';

echo '<fieldset><legend>'.__('Post title').'</legend>'.
'<p class="field"><label for="post_title_w">'.__('In bold:').' '.
form::checkbox('post_title_w',1,$ductile_user['post_title_w']).'</label>'.'</p>'.

'<p class="field"><label for="post_title_s">'.__('Font size:').'</label> '.
form::field('post_title_s',7,7,$ductile_user['post_title_s']).' '.__('(in em by default)').'</p>'.

'<p class="field picker"><label for="post_title_c">'.__('Color:').'</label> '.
form::field('post_title_c',7,7,$ductile_user['post_title_c'],'colorpicker').'</p>'.
'</fieldset>';

echo '</div>';
echo '</div>';

echo '<fieldset><legend>'.__('Inside posts links').'</legend>'.
'<p class="field"><label for="post_link_w">'.__('In bold:').' '.
form::checkbox('post_link_w',1,$ductile_user['post_link_w']).'</label>'.'</p>'.

'<p class="field picker"><label for="post_link_v_c">'.__('Normal and visited links color:').'</label> '.
form::field('post_link_v_c',7,7,$ductile_user['post_link_v_c'],'colorpicker').'</p>'.

'<p class="field picker"><label for="body_link_f_c">'.__('Active, hover and focus links color:').'</label> '.
form::field('post_link_f_c',7,7,$ductile_user['post_link_f_c'],'colorpicker').'</p>'.
'</fieldset>';

echo '<h3>'.__('Mobile specific settings').'</h3>';

echo '<div class="two-cols">';
echo '<div class="col">';

echo '<fieldset><legend>'.__('Blog title').'</legend>'.
'<p class="field"><label for="blog_title_w_m">'.__('In bold:').' '.
form::checkbox('blog_title_w_m',1,$ductile_user['blog_title_w_m']).'</label>'.'</p>'.

'<p class="field"><label for="blog_title_s_m">'.__('Font size:').'</label> '.
form::field('blog_title_s_m',7,7,$ductile_user['blog_title_s_m']).' '.__('(in em by default)').'</p>'.

'<p class="field picker"><label for="blog_title_c_m">'.__('Color:').'</label> '.
form::field('blog_title_c_m',7,7,$ductile_user['blog_title_c_m'],'colorpicker').'</p>'.
'</fieldset>';

echo '</div>';
echo '<div class="col">';

echo '<fieldset><legend>'.__('Post title').'</legend>'.
'<p class="field"><label for="post_title_w_m">'.__('In bold:').' '.
form::checkbox('post_title_w_m',1,$ductile_user['post_title_w_m']).'</label>'.'</p>'.

'<p class="field"><label for="post_title_s_m">'.__('Font size:').'</label> '.
form::field('post_title_s_m',7,7,$ductile_user['post_title_s_m']).' '.__('(in em by default)').'</p>'.

'<p class="field picker"><label for="post_title_c_m">'.__('Color:').'</label> '.
form::field('post_title_c_m',7,7,$ductile_user['post_title_c_m'],'colorpicker').'</p>'.
'</fieldset>';

echo '</div>';
echo '</div>';

echo '<input type="hidden" name="conf_tab" value="css">';
echo '<p class="clear"><input type="submit" value="'.__('Save').'" />'.$core->formNonce().'</p>';
echo '</form>';

echo '</div>'; // Close tab

echo '<form style="display:none">';

?>