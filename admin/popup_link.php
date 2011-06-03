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

$href	= !empty($_GET['href']) ? $_GET['href'] : '';
$hreflang	= !empty($_GET['hreflang']) ? $_GET['hreflang'] : '';
$title	= !empty($_GET['title']) ? $_GET['title'] : '';
$q		= !empty($_GET['q']) ? $_GET['q'] : null;
$page	= !empty($_GET['page']) ? (integer) $_GET['page'] : 1;
$nb_per_page = 10;

$params = array();
$params['limit'] = array((($page-1)*$nb_per_page),$nb_per_page);
$params['no_content'] = true;
$params['order'] = 'post_dt DESC';

if ($q) {
	$params['search'] = $q;
}

dcPage::openPopup(__('Add a link'),
	dcPage::jsLoad('js/_posts_list.js').
	dcPage::jsLoad('js/tiny_mce/tiny_mce_popup.js').
	dcPage::jsLoad('js/tiny_mce/plugins/dcControls/js/popup_link.js')
);

echo '<h2>'.__('Add a link').'</h2>';

# Languages combo
$rs = $core->blog->getLangs(array('order'=>'asc'));
$all_langs = l10n::getISOcodes(0,1);
$lang_combo = array('' => '', __('Most used') => array(), __('Available') => l10n::getISOcodes(1,1));
while ($rs->fetch()) {
	if (isset($all_langs[$rs->post_lang])) {
		$lang_combo[__('Most used')][$all_langs[$rs->post_lang]] = $rs->post_lang;
		unset($lang_combo[__('Available')][$all_langs[$rs->post_lang]]);
	} else {
		$lang_combo[__('Most used')][$rs->post_lang] = $rs->post_lang;
	}
}
unset($all_langs);
unset($rs);

echo
'<form id="link-insert-form" action="#" method="get">'.
'<h4>'.__('Enter a destination URL').'</h4>'.
'<p><label class="required"><abbr title="'.__('Required field').'">*</abbr> '.__('Link URL:').' '.
form::field('href',35,512,html::escapeHTML($href)).'</label></p>'.
'<p><label>'.__('Link title:').' '. 
form::field('title',35,512,html::escapeHTML($title)).'</label></p>'. 
'<p><label>'.__('Link language:').' '.
form::combo('hreflang',$lang_combo,$hreflang).
'</label></p>'.
'<p><a class="button reset" href="#" id="link-insert-cancel">'.__('cancel').'</a> - '.
'<strong><a class="button" href="#" id="link-insert-ok">'.__('insert').'</a></strong></p>'."\n".

'<div id="div-entries">'.
'<h4>'.__('Or link to existing content').'</h4>'.
'<p class="form-note">'.__('Click on a title to select the link').'</p>'.
'<p><label for="q" class="classic">'.__('Search entry:').' '.form::field('q',30,255,html::escapeHTML($q)).'</label> '.
' <input type="submit" value="'.__('ok').'" /></p>';

try {
	$posts = $core->blog->getPosts($params);
	$counter = $core->blog->getPosts($params,true);
	$post_list = new adminPostMiniList($core,$posts,$counter->f(0));
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

echo '<div id="form-entries">'; # I know it's not a form but we just need the ID
$post_list->display($page,$nb_per_page);
echo
'</div>'.
'</div>'.
'</form>'.

'<script type="text/javascript">'."\n".
'//<![CDATA['."\n".
'$(\'input[name="href"]\').get(0).focus();'."\n".
'//]]>'."\n".
'</script>'."\n";

dcPage::closePopup();

?>