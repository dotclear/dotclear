<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
# This file is part of Ductile, a theme for Dotclear
#
# Copyright (c) 2011 - Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "Ductile",                              // Name
    "Mediaqueries compliant elegant theme", // Description
    "Dotclear Team",                        // Author
    '1.5',                                  // Version
    array(                                  // Properties
        'standalone_config' => true,
        'type'              => 'theme'
    )
);
