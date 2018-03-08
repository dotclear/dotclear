<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of importExport, a plugin for DotClear2.
#
# Copyright (c) 2003-2012 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "Import / Export",                // Name
    "Import and Export your blog",    // Description
    "Olivier Meunier & Contributors", // Author
    '3.2',                            // Version
    array(
        'permissions' => 'admin',
        'type'        => 'plugin'
    )
);
