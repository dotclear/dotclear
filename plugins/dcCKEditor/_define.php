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
 * @copyright   AGPL-3.0
 */
$this->registerModule(
    'dcCKEditor',                    // Name
    'dotclear CKEditor integration', // Description
    'dotclear Team',                 // Author
    '2.1',                           // Version
    [
        'permissions' => 'My',
        'type'        => 'plugin',
        'settings'    => [
            'self' => '',
            'pref' => '#user-options.user_options_edition',
        ],
    ]
);
