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
use Dotclear\App;

$this->registerModule(
    'Maintenance',                            // Name
    'Maintain your installation',             // Description
    'Olivier Meunier & Association Dotclear', // Author
    '2.0',                                    // Version
    [
        'permissions' => App::auth()->makePermissions([
            App::auth()::PERMISSION_ADMIN,
        ]),
        'type'     => 'plugin',
        'settings' => [
            'self' => '#settings',
        ],
    ]
);
