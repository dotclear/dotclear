<?php
/**
 * @file
 * @brief       The plugin dcLegacyEditor definition
 * @ingroup     dcLegacyEditor
 *
 * @defgroup    dcLegacyEditor Plugin dcLegacyEditor.
 *
 * dcLegacyEditor, dotclear legacy editor.
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
$this->registerModule(
    'dcLegacyEditor',         // Name
    'dotclear legacy editor', // Description
    'dotclear Team',          // Author
    '1.1',                  // Version
    [
        'permissions' => 'My',
        'type'        => 'plugin',
        'settings'    => [
            'self' => '',
            'pref' => '#user-options.user_options_edition',
        ],
    ]
);
