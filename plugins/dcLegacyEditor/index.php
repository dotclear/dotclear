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

if (!defined('DC_CONTEXT_ADMIN')) { return; }

$is_admin = $core->auth->check('admin,contentadmin', $core->blog->id) || $core->auth->isSuperAdmin();

$core->blog->settings->addNameSpace('dclegacyeditor');
$dclegacyeditor_active = $core->blog->settings->dclegacyeditor->active;

if (!empty($_POST['saveconfig'])) {
    try {
        $dclegacyeditor_active = (empty($_POST['dclegacyeditor_active']))?false:true;
        $core->blog->settings->dclegacyeditor->put('active', $dclegacyeditor_active, 'boolean');

        dcPage::addSuccessNotice(__('The configuration has been updated.'));
        http::redirect($p_url);
    } catch(Exception $e) {
		$core->error->add($e->getMessage());
    }
}

include dirname(__FILE__).'/tpl/index.tpl';

