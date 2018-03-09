<?php
/**
 * @brief pings, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "Pings",           // Name
    "Ping services",   // Description
    "Olivier Meunier", // Author
    '1.3',             // Version
    array(
        'permissions' => 'usage,contentadmin',
        'type'        => 'plugin',
        'settings'    => array(
            'self' => ''
        )
    )
);
