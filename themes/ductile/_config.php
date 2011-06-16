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
	'body_link_w' => null,
	'body_link_v_c' => null,
	'body_link_f_c' => null,
	'body_font' => null,
	'alternate_font' => null
);

$ductile_user = $core->blog->settings->themes->ductile_style;
$ductile_user = @unserialize($ductile_user);
if (!is_array($ductile_user)) {
	$ductile_user = array();
}

$ductile_user = array_merge($ductile_base,$ductile_user);

if (!empty($_POST))
{
	try
	{
		$ductile_user['body_link_w'] = (integer) !empty($_POST['body_link_w']);

		$ductile_user['body_link_v_c'] = adjustColor($_POST['body_link_v_c']);
		$ductile_user['body_link_f_c'] = adjustColor($_POST['body_link_f_c']);
		
		$ductile_user['body_font'] = $_POST['body_font'];
		$ductile_user['alternate_font'] = $_POST['alternate_font'];
		
		$core->blog->settings->addNamespace('themes');
		$core->blog->settings->themes->put('ductile_style',serialize($ductile_user));
		$core->blog->triggerBlog();

		echo
		'<div class="message"><p>'.
		__('Style sheet upgraded.').
		'</p></div>';
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
	}
}

echo '<fieldset><legend>'.__('Fonts').'</legend>'.
'<p class="field"><label for="body_font">'.__('Main font:').' '.
form::combo('body_font',$fonts,$ductile_user['body_font']).'</label></p>'.

'<p class="field"><label for="alternate_font">'.__('Secondary font:').' '.
form::combo('alternate_font',$fonts,$ductile_user['alternate_font']).'</label></p>'.
'</fieldset>';

echo '<fieldset><legend>'.__('Inside posts links').'</legend>'.
'<p class="field"><label for="body_link_w">'.__('Links in bold:').' '.
form::checkbox('body_link_w',1,$ductile_user['body_link_w']).'</label>'.'</p>'.

'<p class="field"><label for="body_link_v_c">'.__('Normal and visited links color:').'</label> '.
form::field('body_link_v_c',7,7,$ductile_user['body_link_v_c'],'colorpicker').'</p>'.

'<p class="field"><label for="body_link_f_c">'.__('Active, hover and focus links color:').'</label> '.
form::field('body_link_f_c',7,7,$ductile_user['body_link_f_c'],'colorpicker').'</p>'.
'</fieldset>';

?>