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

$core->addBehavior('initWidgets',array('simpleMenuWidgets','initWidgets'));

class simpleMenuWidgets
{
	public static function initWidgets($w)
	{
		$w->create('simplemenu',__('Simple menu'),array('tplSimpleMenu','simpleMenuWidget'),null,'List of simple menu items');
		$w->simplemenu->setting('title',__('Title:'),__('Menu'));
		$w->simplemenu->setting('homeonly',__('Display on:'),0,'combo',
			array(
				__('All pages') => 0,
				__('Home page only') => 1,
				__('Except on home page') => 2
				)
		);
		$w->simplemenu->setting('content_only',__('Content only'),0,'check');
		$w->simplemenu->setting('class',__('CSS class:'),'');
	}
}
?>