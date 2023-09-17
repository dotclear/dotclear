<?php
/**
 * @file
 * @brief       The plugin themeEditor definition
 * @ingroup     themeEditor
 * 
 * @defgroup    themeEditor Plugin themeEditor.
 * 
 * themeEditor, Internal build tools for dotclear team.
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
$this->registerModule(
    'themeEditor',     // Name
    'Theme Editor',    // Description
    'Olivier Meunier', // Author
    '2.0',             // Version
    [
        'type'     => 'plugin',
        'settings' => [
            'pref' => '#user-options.themeEditor_prefs',
        ],
    ]
);
