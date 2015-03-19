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

$version = $core->plugins->moduleInfo('dcLegacyEditor', 'version');
if (version_compare($core->getVersion('dcLegacyEditor'), $version,'>=')) {
  return;
}

$settings = $core->blog->settings;
$settings->addNamespace('dclegacyeditor');
$settings->dclegacyeditor->put('active', true, 'boolean', 'dcLegacyEditor plugin activated ?', false, true);

$core->setVersion('dcLegacyEditor', $version);
return true;
