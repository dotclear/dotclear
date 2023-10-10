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
$this->registerModule(
    'Simple menu',               // Name
    'Simple menu for Dotclear', // Description
    'Franck Paul',              // Author
    '2.0',                      // Version
    [
        'permissions' => 'My',
        'type'        => 'plugin',
        'settings'    => [
            'self' => '',
        ],
    ]
);
