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

require dirname(__FILE__).'/../inc/admin/prepend.php';

dcPage::check('usage');

function helpPage()
{
	$ret = array('content' => '', 'title' => '');

	$args = func_get_args();
	if (empty($args)) {
		return $ret;
	};
	
	global $__resources;
	if (empty($__resources['help'])) {
		return $ret;
	}
	
	$content = '';
	$title = '';
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
			if (preg_match('|<title[^>]*?>(.*?)</title>|ms',$fc,$matches)) {
				$title = $matches[1];
			}
		} else {
			$content .= $fc;
		}
	}
	
	if (trim($content) == '') {
		return $ret;
	}
	
	$ret['content'] = $content;
	if ($title != '') {
		$ret['title'] = $title;
	}
	return $ret;
}

$help_page = !empty($_GET['page']) ? html::escapeHTML($_GET['page']) : 'index';
$content_array = helpPage($help_page);
if (($content_array['content'] == '') || ($help_page == 'index')) {
	$content_array = helpPage('index');
}
if ($content_array['title'] != '') {
	$breadcrumb = dcPage::breadcrumb(
		array(
			__('Global help') => 'help.php',
			'<span class="page-title">'.$content_array['title'].'</span>' => ''
		));
} else {
	$breadcrumb = dcPage::breadcrumb(
		array(
			'<span class="page-title">'.__('Global help').'</span>' => ''
		));
}

/* DISPLAY
-------------------------------------------------------- */
dcPage::open(__('Global help'),
	# --BEHAVIOR-- adminPostHeaders
	$core->callBehavior('adminPostHeaders').
	dcPage::jsPageTabs('first-step'),
	$breadcrumb
);

echo $content_array['content'];

dcPage::close();
?>