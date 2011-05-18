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

function dcSystemCheck($con,&$err)
{
	$err = array();
	
	if (version_compare(phpversion(),'5.0','<')) {
		$err[] = sprintf(__('PHP version is %s (5.0 or earlier needed).'),phpversion());
	}
	
	if (!function_exists('mb_detect_encoding')) {
		$err[] = __('Multibyte string module (mbstring) is not available.');
	}
	
	if (!function_exists('iconv')) {
		$err[] = __('Iconv module is not available.');
	}
	
	if (!function_exists('ob_start')) {
		$err[] = __('Output control functions are not available.');
	}
	
	if (!function_exists('simplexml_load_string')) {
		$err[] = __('SimpleXML module is not available.');
	}
	
	if (!function_exists('dom_import_simplexml')) {
		$err[] = __('DOM XML module is not available.');
	}
	
	$pcre_str = base64_decode('w6nDqMOgw6o=');
	if (!@preg_match('/'.$pcre_str.'/u', $pcre_str)) {
		$err[] = __('PCRE engine does not support UTF-8 strings.');
	}
	
	if (!function_exists("spl_classes")) {
		$err[] = __('SPL module is not available.');
	}
	
	if ($con->driver() == 'mysql')
	{
		if (version_compare($con->version(),'4.1','<'))
		{
			$err[] = sprintf(__('MySQL version is %s (4.1 or earlier needed).'),$con->version());
		}
		else
		{
			$rs = $con->select('SHOW ENGINES');
			$innodb = false;
			while ($rs->fetch()) {
				if (strtolower($rs->f(0)) == 'innodb' && strtolower($rs->f(1)) != 'disabled' && strtolower($rs->f(1)) != 'no') {
					$innodb = true;
					break;
				}
			}
			
			if (!$innodb) {
				$err[] = __('MySQL InnoDB engine is not available.');
			}
		}
	}
	elseif ($con->driver() == 'pgsql')
	{
		if (version_compare($con->version(),'8.0','<'))
		{
			$err[] = sprintf(__('PostgreSQL version is %s (8.0 or earlier needed).'),$con->version());
		}
	}
	
	return count($err) == 0;
}
?>