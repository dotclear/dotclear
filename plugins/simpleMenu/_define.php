<?php
/**
 * @brief simpleMenu, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
use Dotclear\App;

$this->registerModule(
    'Simple menu',               // Name
    'Simple menu for Dotclear', // Description
    'Franck Paul',              // Author
    '2.0',                      // Version
    [
        'permissions' => App::auth()->makePermissions([
            App::auth()::PERMISSION_ADMIN,
        ]),
        'type'     => 'plugin',
        'settings' => [
            'self' => '',
        ],
    ]
);
