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

dcPage::check('usage,contentadmin');

$p_file = '';
$p = !empty($_REQUEST['p']) ? $_REQUEST['p'] : null;
$popup = (integer) !empty($_REQUEST['popup']);

if ($popup) {
	$open_f = array('dcPage','openPopup');
	$close_f = array('dcPage','closePopup');
} else {
	$open_f = array('dcPage','open');
	$close_f = array('dcPage','close');
}

if ($core->plugins->moduleExists($p)) {
	$p_file = $core->plugins->moduleRoot($p).'/index.php';
}

if (file_exists($p_file))
{
	# Loading plugin
	$p_info = $core->plugins->getModules($p);
	
	$p_url = 'plugin.php?p='.$p;
	
	$p_title = 'no content - plugin';
	$p_head = '';
	$p_content = '<p>'.__('No content found on this plugin.').'</p>';
	
	ob_start();
	include $p_file;
	$res = ob_get_contents();
	ob_end_clean();
	
	if (preg_match('|<head>(.*?)</head|ms',$res,$m)) {
		if (preg_match('|<title>(.*?)</title>|ms',$m[1],$mt)) {
			$p_title = $mt[1];
		}
		
		if (preg_match_all('|(<script.*?>.*?</script>)|ms',$m[1],$ms)) {
			foreach ($ms[1] as $v) {
				$p_head .= $v."\n";
			}
		}
		
		if (preg_match_all('|(<style.*?>.*?</style>)|ms',$m[1],$ms)) {
			foreach ($ms[1] as $v) {
				$p_head .= $v."\n";
			}
		}
		
		if (preg_match_all('|(<link.*?/>)|ms',$m[1],$ms)) {
			foreach ($ms[1] as $v) {
				$p_head .= $v."\n";
			}
		}
	}
	
	if (preg_match('|<body.*?>(.+)</body>|ms',$res,$m)) {
		$p_content = $m[1];
	}
	
	call_user_func($open_f,$p_title,$p_head);
	echo $p_content;
	call_user_func($close_f);
}
else
{
	call_user_func($open_f,__('Plugin not found'));
	
	echo '<h2 class="page-title">'.__('Plugin not found').'</h2>';
	
	echo '<p>'.__('The plugin you reached does not exist or does not have an admin page.').'</p>';
	
	call_user_func($close_f);
}
?>