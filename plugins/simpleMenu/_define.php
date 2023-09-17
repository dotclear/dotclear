<?php
/**
 * @file
 * @brief       The plugin simpleMenu definition
 * @ingroup     simpleMenu
 * 
 * @defgroup    simpleMenu Plugin simpleMenu.
 * 
 * simpleMenu, simple menu for themes.
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
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
