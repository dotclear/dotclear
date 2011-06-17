<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2010 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

require dirname(__FILE__).'/../inc/admin/prepend.php';

dcPage::check('usage,contentadmin');

dcPage::openPopup(__('Add a web media'),
	dcPage::jsLoad('js/jquery/jquery.oembed.js').
	dcPage::jsLoad('js/tiny_mce/tiny_mce_popup.js').
	dcPage::jsLoad('js/tiny_mce/plugins/dcControls/js/popup_web_media.js').
	'<style type="text/css">
	#src {
		padding-left: 25px;
		background: transparent url(images/menu/search.png) no-repeat 5px center;
	}
	#src.loading {
		background-color: #ccc;
	}
	#src.error {
		background: #ff9999 url(images/check-off.png) no-repeat 5px center;
	}
	#src.success {
		background: #99ff99 url(images/check-on.png) no-repeat 5px center;
	}
	</style>'
);

$align = array(
	'none' => array(__('None'),1),
	'left' => array(__('Left'),0),
	'right' => array(__('Right'),0),
	'center' => array(__('Center'),0)
);
$v_insert = array(
	'media' => array(__('Full media'),1),
	'thumbnail' => array(__('Media thumbnail'),0),
	'link' => array(__('Media link'),0),
);

echo
'<h2>'.__('Add a web media').'</h2>'.

'<form id="video-insert-form" action="#" method="get">'.
'<p><label class="required"><abbr title="'.__('Required field').'">*</abbr> '.__('Source URL:').'</label>'.
form::field('src',85,512,'').'&nbsp;'.
'<a class="button" href="#" id="webmedia-insert-search">'.__('search').'</a> - '.
'<strong><a class="button" href="#" id="webmedia-insert-ok">'.__('insert').'</a></strong> - '.
'<a class="button reset" href="#" id="webmedia-insert-cancel">'.__('cancel').'</a></p>'.

'<div class="two-cols" style="display:none;"><div class="col">'.
'<p><label class="required"><abbr title="'.__('Width').'">*</abbr> '.__('Width:').'</label>'.
form::field('width',35,4,'').'</p>'.
'<p><label class="required"><abbr title="'.__('Height').'">*</abbr> '.__('Height:').'</label>'.
form::field('height',35,4,'').'</label></p>'.
'<p><label for="alt">'.__('Alternative text:').' '.
form::field('alt',35,512,'').'</label></p>'.
'<p><label for="title">'.__('Title:').' '.
form::field('title',35,512,'').'</label></p>'.
'<p><label class="required"><abbr title="'.__('Required field').'">*</abbr> '.__('Insertion').' '.
'</label></p>'.
'<p>';
foreach ($v_insert as $k => $v) {
	echo '<label class="classic">'.
	form::radio(array('insertion'),$k,$v[1]).' '.$v[0].'</label><br /> ';
}
echo
'</p>'.
'<p><label class="required"><abbr title="'.__('Required field').'">*</abbr> '.__('Alignment').' '.
'</label></p>'.
'<p>';
foreach ($align as $k => $v) {
	echo '<label class="classic">'.
	form::radio(array('alignment'),$k,$v[1]).' '.$v[0].'</label><br /> ';
}
echo
'</p>'."\n".
'</div><div class="col preview">'.
'</div></div>'.
'</form>'.

'<script type="text/javascript">'."\n".
'//<![CDATA['."\n".
'$(\'input[name="src"]\').get(0).focus();'."\n".
'//]]>'."\n".
'</script>'."\n";

dcPage::closePopup();

?>