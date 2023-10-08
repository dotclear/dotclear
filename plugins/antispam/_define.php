<?php
/**
 * @file
 * @brief       The plugin antispam definition
 * @ingroup     antispam
 * 
 * @defgroup    antispam Plugin antispam.
 * 
 * antispam, generic antispam plugin for Dotclear.
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
$this->registerModule(
    'Antispam',                             // Name
    'Generic antispam plugin for Dotclear', // Description
    'Alain Vagner',                         // Author
    '2.0',                                // Version
    [
        'type'        => 'plugin',
        'permissions' => 'My',
        'priority'    => 10,
        'settings'    => [
            'self' => '',
            'blog' => '#params.antispam_params',
        ],
    ]
);
