<?php
/**
 * @brief maintenance, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "Maintenance",                            // Name
    "Maintain your installation",             // Description
    "Olivier Meunier & Association Dotclear", // Author
    '1.3.1',                                  // Version
    array(
        'permissions' => 'admin',
        'type'        => 'plugin',
        'settings'    => array(
            'self' => '#settings'
        )
    )

);
