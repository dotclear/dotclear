<?php
/**
 * @brief dcCKEditor, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
use Dotclear\Core\Core;

$this->registerModule(
    'dcCKEditor',                    // Name
    'dotclear CKEditor integration', // Description
    'dotclear Team',                 // Author
    '2.1',                           // Version
    [
        'permissions' => Core::auth()->makePermissions([
            Core::auth()::PERMISSION_USAGE,
            Core::auth()::PERMISSION_CONTENT_ADMIN,
        ]),
        'type'        => 'plugin',
        'settings'    => [
            'self' => '',
            'pref' => '#user-options.user_options_edition',
        ],
    ]
);
