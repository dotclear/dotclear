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

if (!defined('DC_RC_PATH')) { return; }

# Behaviors
$GLOBALS['core']->addBehavior('adminPageHTMLHead',array('tplDuctileThemeAdmin','adminPageHTMLHead'));

class tplDuctileThemeAdmin
{
	public static function adminPageHTMLHead()
	{
		global $core;
		if ($core->blog->settings->system->theme != 'ductile') { return; }

		echo "\n".'<!-- Header directives for Ductile configuration -->'."\n";
		$core->auth->user_prefs->addWorkspace('accessibility');
		if (!$core->auth->user_prefs->accessibility->nodragdrop) {
			echo
				dcPage::jsLoad('js/jquery/jquery-ui.custom.js').
				dcPage::jsLoad('js/jquery/jquery.ui.touch-punch.js');
				echo <<<EOT
<script type="text/javascript">
//<![CDATA[
$(function() {
	$("#stickerslist").sortable({'cursor':'move'});
	$("#stickerslist tr").hover(function () {
		$(this).css({'cursor':'move'});
	}, function () {
		$(this).css({'cursor':'auto'});
	});
	$('#theme_config').submit(function() {
		var order=[];
		$("#stickerslist tr td input.position").each(function() {
			order.push(this.name.replace(/^order\[([^\]]+)\]$/,'$1'));
		});
		$("input[name=ds_order]")[0].value = order.join(',');
		return true;
	});
	$("#stickerslist tr td input.position").hide();
	$("#stickerslist tr td.handle").addClass('handler');
});
//]]>
</script>
EOT;
		}

	}
}
?>
