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

//* TODO: Do it better later, required by some javascripts
$some_globals = array(
	'rtl' => l10n::getTextDirection($_lang) == 'rtl',
	'Nonce' => $core->getNonce(),
	'sess_id' => session_id(),
	'sess_uid' => $_SESSION['sess_browser_uid'],
	'media_manage' => $core->auth->check('media,media_admin',$core->blog->id),
	'enable_wysiwyg' => isset($core->auth) && $core->auth->getOption('enable_wysiwyg'),
	'edit_size' => $core->auth->getOption('edit_size')
);
foreach($some_globals as $name => $value) {
	$_ctx->$name = $value;
};
//*/

$has_content = false;
$p_file = '';
$p = !empty($_REQUEST['p']) ? $_REQUEST['p'] : null;
$popup = $_ctx->popup = (integer) !empty($_REQUEST['popup']);

if ($core->plugins->moduleExists($p)) {
	$p_file = $core->plugins->moduleRoot($p).'/index.php';
}
if (file_exists($p_file)) {

//* Keep this for old style plugins using dcPage
	if ($popup) {
		$open_f = array('dcPage','openPopup');
		$close_f = array('dcPage','closePopup');
	} else {
		$open_f = array('dcPage','open');
		$close_f = array('dcPage','close');
	}
	
	$p_info = $core->plugins->getModules($p);
	$p_url = 'plugin.php?p='.$p;
	$p_title = $p_head = $p_content = '';
//*/	
	# Get page content
	ob_start();
	include $p_file;
	$res = ob_get_contents();
	ob_end_clean();

	# Check context and display
	if ($_ctx->hasPageTitle() && !empty($res)) {
		$has_content = true;
		echo $res;
	}
//* Keep this for old style plugins using dcPage
	elseif (!$_ctx->hasPageTitle()) {
		
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
			
			call_user_func($open_f,$p_title,$p_head);
			echo $p_content;
			call_user_func($close_f);
			
			$has_content = true;
		}
	}
//*/
}
# No plugin or content found
if (!$has_content) {
	$_ctx->setPageTitle(__('Plugin not found'));
	$_ctx->addError(__('The plugin you reached does not exist or does not have an admin page.'));
	$core->tpl->display('plugin.html.twig');
}
?>