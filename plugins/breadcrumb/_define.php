<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
# This file is part of breadcrumb, a plugin for Dotclear 2.
#
# Copyright (c) Franck Paul and contributors
# carnet.franck.paul@gmail.com
#
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "Breadcrumb",              // Name
    "Breadcrumb for Dotclear", // Description
    "Franck Paul",             // Author
    '0.7',                     // Version
    array(
        'permissions' => 'usage,contentadmin', // Permissions
        'type'        => 'plugin',             // Type
        'settings'    => array(
            'blog' => '#params.breadcrumb_params'
        )
    )
);
