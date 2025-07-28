<?php

/**
 * @file
 * @brief       The plugin dcLegacyEditor definition
 * @ingroup     dcLegacyEditor
 *
 * @defgroup    dcLegacyEditor Plugin dcLegacyEditor.
 *
 * dcLegacyEditor, dotclear editor.
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
$this->registerModule(
    'Dotclear editor',
    'Dotclear editor',
    'dotclear Team',
    '1.1',
    [
        'permissions' => 'My',
        'type'        => 'plugin',
        'settings'    => [
            'self' => '',
            'pref' => '#user-options.user_options_edition',
        ],
    ]
);
