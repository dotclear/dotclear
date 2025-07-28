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
    'CKEditor',
    'CKEditor',
    'dotclear Team',
    '2.1',
    [
        'permissions' => 'My',
        'type'        => 'plugin',
        'settings'    => [
            'self' => '',
            'pref' => '#user-options.user_options_edition',
        ],
    ]
);
