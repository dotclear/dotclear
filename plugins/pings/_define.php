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
use Dotclear\App;

$this->registerModule(
    'Pings',           // Name
    'Ping services',   // Description
    'Olivier Meunier', // Author
    '2.0',             // Version
    [
        'permissions' => App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]),
        'type'     => 'plugin',
        'settings' => [
            'self' => '',
        ],
    ]
);
