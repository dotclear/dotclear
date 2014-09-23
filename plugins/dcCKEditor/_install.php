<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2014 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

if (!defined('DC_CONTEXT_ADMIN')) { return; }

$version = $core->plugins->moduleInfo('dcCKEditor', 'version');
if (version_compare($core->getVersion('dcCKEditor'), $version,'>=')) {
    return;
}

$settings = $core->blog->settings;
$settings->addNamespace('dcckeditor');

$settings->dcckeditor->put('active', true, 'boolean', 'dcCKEditor plugin activated?', false, true);
$settings->dcckeditor->put('alignment_buttons', true, 'boolean', 'Add alignment buttons?', false, true);
$settings->dcckeditor->put('list_buttons', true, 'boolean', 'Add list buttons?', false, true);
$settings->dcckeditor->put('textcolor_button', false, 'boolean', 'Add text color button?', false, true);
$settings->dcckeditor->put('cancollapse_button', false, 'boolean', 'Add collapse button?', false, true);
$settings->dcckeditor->put('format_select', false, 'boolean', 'Add format selection?', false, true);
$settings->dcckeditor->put('textareas', DEFAULT_TEXTAREAS, 'string', 'Text areas to be used by CKEditor', false, true);
$settings->dcckeditor->put('table_button', false, 'boolean', 'Add table button?', false, true);
$settings->dcckeditor->put('clipboard_buttons', false, 'boolean', 'Add clipboard buttons?', false, true);

$core->setVersion('dcCKEditor', $version);
return true;
