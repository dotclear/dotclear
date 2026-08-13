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
 * @copyright   AGPL-3.0
 */
if (isset($this) && is_object($this) && method_exists($this, 'registerModule') && isset($this->id) && is_string($this->id)) {
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
}
