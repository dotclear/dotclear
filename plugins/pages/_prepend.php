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

$core->url->register('pages','pages','^pages/(.+)$',array('urlPages','pages'));
$core->url->register('pagespreview','pagespreview','^pagespreview/(.+)$',array('urlPages','pagespreview'));

$core->setPostType('page','plugin.php?p=pages&act=page&id=%d',$core->url->getURLFor('pages','%s'));

# We should put this as settings later
$GLOBALS['page_url_format'] = '{t}';
?>