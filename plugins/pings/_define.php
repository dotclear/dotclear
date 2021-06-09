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
if (!defined('DC_RC_PATH')) {
    return;
}

$this->registerModule(
    'Pings',           // Name
    'Ping services',   // Description
    'Olivier Meunier', // Author
    '1.4',             // Version
    [
        'permissions' => 'usage,contentadmin',
        'type'        => 'plugin',
        'settings'    => [
            'self' => ''
        ]
    ]
);
