<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

require dirname(__FILE__).'/../inc/admin/prepend.php';

dcPage::check('usage,contentadmin');

$href = !empty($_GET['href']) ? $_GET['href'] : '';
$hreflang = !empty($_GET['hreflang']) ? $_GET['hreflang'] : '';
$title = !empty($_GET['title']) ? $_GET['title'] : '';
$plugin_id = !empty($_GET['plugin_id']) ? html::sanitizeURL($_GET['plugin_id']) : '';

dcPage::openPopup(__('Add a link'),$core->callBehavior('adminPopupLink', $plugin_id));

echo '<h2 class="page-title">'.__('Add a link').'</h2>';

# Languages combo
$rs = $core->blog->getLangs(array('order'=>'asc'));
$lang_combo = dcAdminCombos::getLangsCombo($rs,true);

echo
'<form id="link-insert-form" action="#" method="get">'.
'<p><label class="required" for="href"><abbr title="'.__('Required field').'">*</abbr> '.__('Link URL:').'</label> '.
form::field('href',35,512,html::escapeHTML($href)).'</p>'.
'<p><label for="title">'.__('Link title:').'</label> '.
form::field('title',35,512,html::escapeHTML($title)).'</p>'.
'<p><label for="hreflang">'.__('Link language:').'</label> '.
form::combo('hreflang',$lang_combo,$hreflang).
'</p>'.

'</form>'.

'<p><button type="button" class="reset" id="link-insert-cancel">'.__('Cancel').'</button> - '.
'<button type="button" id="link-insert-ok"><strong>'.__('Insert').'</strong></button></p>'."\n".

'<script type="text/javascript">'."\n".
'$(\'input[name="href"]\').get(0).focus();'."\n".
'</script>'."\n";

dcPage::closePopup();
