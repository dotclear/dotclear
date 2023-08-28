<?php
/**
 * @brief widgets, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
use Dotclear\Core\Core;

$this->registerModule(
    'Widgets',                         // Name
    'Widgets for your blog sidebars',  // Description
    'Olivier Meunier & Dotclear Team', // Author
    '4.0',                             // Version
    [
        'permissions' => Core::auth()->makePermissions([
            Core::auth()::PERMISSION_ADMIN,
        ]),
        'priority' => 1_000_000_000,
        'type'     => 'plugin',
    ]
);
