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
	'body_link_c' => null,
	'body_link_v_c' => null,
	'body_link_f_c' => null
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
		$ductile_user['body_link_c'] = adjustColor($_POST['body_link_c']);
		$ductile_user['body_link_f_c'] = adjustColor($_POST['body_link_f_c']);
		$ductile_user['body_link_v_c'] = adjustColor($_POST['body_link_v_c']);
		
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

echo '<fieldset><legend>'.__('Links').'</legend>'.
'<p class="field"><label for="body_link_c">'.__('Links color:').' '.
form::field('body_link_c',7,7,$ductile_user['body_link_c'],'colorpicker').'</label></p>'.

'<p class="field"><label for="body_link_v_c">'.__('Visited links color:').' '.
form::field('body_link_v_c',7,7,$ductile_user['body_link_v_c'],'colorpicker').'</label></p>'.

'<p class="field"><label for="body_link_f_c">'.__('Focus links color:').' '.
form::field('body_link_f_c',7,7,$ductile_user['body_link_f_c'],'colorpicker').'</label></p>'.
'</fieldset>';

?>