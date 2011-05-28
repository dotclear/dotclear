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

$core->addBehavior('initWidgets',array('pagesWidgets','initWidgets'));
$core->addBehavior('initDefaultWidgets',array('pagesWidgets','initDefaultWidgets'));

class pagesWidgets
{
	public static function initWidgets($w)
	{
		$w->create('pages',__('Pages'),array('tplPages','pagesWidget'));
		$w->pages->setting('title',__('Title:'),__('Pages'));
		$w->pages->setting('homeonly',__('Home page only'),1,'check');
		$w->pages->setting('sortby',__('Order by:'),'post_title','combo',
			array(
				__('Page title') => 'post_title',
				__('Page position') => 'post_position',
				__('Publication date') => 'post_dt'
			)
		);
		$w->pages->setting('orderby',__('Sort:'),'asc','combo',
			array(__('Ascending') => 'asc', __('Descending') => 'desc')
		);
	}
	
	public static function initDefaultWidgets($w,$d)
	{
		$d['extra']->append($w->pages);
	}
}
?>