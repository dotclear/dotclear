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
if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "simpleMenu",               // Name
    "Simple menu for Dotclear", // Description
    "Franck Paul",              // Author
    '1.5',                      // Version
    array(
        'permissions' => 'admin',
        'type'        => 'plugin',
        'settings'    => array(
            'self' => ''
        )
    )
);
