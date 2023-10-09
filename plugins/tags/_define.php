<?php
/**
 * @file
 * @brief       The plugin tags definition
 * @ingroup     tags
 *
 * @defgroup    tags Plugin tags.
 *
 * tags, tags for posts.
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
$this->registerModule(
    'Tags',            // Name
    'Tags for posts',  // Description
    'Olivier Meunier', // Author
    '2.0',             // Version
    [
        'permissions' => 'My',
        'priority'    => 1001, // Must be higher than dcLegacyEditor/dcCKEditor priority (ie 1000)
        'type'        => 'plugin',
        'settings'    => [
            'pref' => '#user-options.tags_prefs',
        ],
    ]
);
