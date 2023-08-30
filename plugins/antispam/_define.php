<?php
/**
 * @brief antispam, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
use Dotclear\App;

$this->registerModule(
    'Antispam',                             // Name
    'Generic antispam plugin for Dotclear', // Description
    'Alain Vagner',                         // Author
    '2.0',                                // Version
    [
        'type'        => 'plugin',
        'permissions' => App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]),
        'priority' => 10,
        'settings' => [
            'self' => '',
            'blog' => '#params.antispam_params',
        ],
    ]
);
