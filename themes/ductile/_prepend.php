<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
# This file is part of Ductile, a theme for Dotclear
#
# Copyright (c) 2011 - Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

# Public and Admin modes :

if (!defined('DC_RC_PATH')) { return; }

# Admin mode only :
$GLOBALS['core']->themes->loadModuleL10Nresources($GLOBALS['core']->blog->settings->system->theme,$GLOBALS['_lang']);

# Behaviors
$GLOBALS['core']->addBehavior('adminPageHTMLHead',array('tplDuctileThemeAdmin','adminPageHTMLHead'));

class tplDuctileThemeAdmin
{
	public static function adminPageHTMLHead()
	{
		global $core;
		
		echo "\n".'<!-- Header directives for Ductile configuration -->'."\n";
		echo dcPage::jsToolMan();

		// Need some more Js
		$core->auth->user_prefs->addWorkspace('accessibility'); 
		$user_dm_nodragdrop = $core->auth->user_prefs->accessibility->nodragdrop;
		if (!$user_dm_nodragdrop) {
			echo <<<EOT
<script type="text/javascript">
//<![CDATA[

var dragsort = ToolMan.dragsort();
$(function() {
	dragsort.makeTableSortable($("#stickerslist").get(0),
	dotclear.sortable.setHandle,dotclear.sortable.saveOrder);
});

dotclear.sortable = {
	setHandle: function(item) {
		var handle = $(item).find('td.handle').get(0);
		while (handle.firstChild) {
			handle.removeChild(handle.firstChild);
		}

		item.toolManDragGroup.setHandle(handle);
		handle.className = handle.className+' handler';
	},

	saveOrder: function(item) {
		var group = item.toolManDragGroup;
		var order = document.getElementById('ds_order');
		group.register('dragend', function() {
			order.value = '';
			items = item.parentNode.getElementsByTagName('tr');

			for (var i=0; i<items.length; i++) {
				order.value += items[i].id.substr(2)+',';
			}
		});
	}
};
//]]>
</script>
EOT;
		}
	}
}
?>