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
use Dotclear\Core\Core;

$this->registerModule(
    'Maintenance',                            // Name
    'Maintain your installation',             // Description
    'Olivier Meunier & Association Dotclear', // Author
    '2.0',                                    // Version
    [
        'permissions' => Core::auth()->makePermissions([
            Core::auth()::PERMISSION_ADMIN,
        ]),
        'type'     => 'plugin',
        'settings' => [
            'self' => '#settings',
        ],
    ]
);
