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

/* ### THIS FILE IS DEPRECATED 					### */
/* ### IT IS ONLY USED FOR PLUGINS COMPATIBILITY ### */

require dirname(__FILE__).'/../inc/admin/prepend.php';

dcPage::check('usage,contentadmin');

$comments_actions_page = new dcCommentsActionsPage($core,'comments.php');

$comments_actions_page->process();

?>