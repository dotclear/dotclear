<?php
/**
 * @file
 * @brief       The plugin dcCKEditor definition
 * @ingroup     dcCKEditor
 * 
 * @defgroup    dcCKEditor Plugin dcCKEditor.
 * 
 * dcCKEditor, dotclear CKEditor integration.
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
use Dotclear\App;

$this->registerModule(
    'dcCKEditor',                    // Name
    'dotclear CKEditor integration', // Description
    'dotclear Team',                 // Author
    '2.1',                           // Version
    [
        'permissions' => App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]),
        'type'     => 'plugin',
        'settings' => [
            'self' => '',
            'pref' => '#user-options.user_options_edition',
        ],
    ]
);
