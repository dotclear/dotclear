<?php
/**
 * @file
 * @brief       The plugin akismet definition
 * @ingroup     akismet
 *
 * @defgroup    akismet Plugin akismet.
 *
 * akismet, aksimet antispam filter plugin for Dotclear 2
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
$this->registerModule(
    'Akismet',                        // Name
    'Akismet interface for Dotclear', // Description
    'Olivier Meunier',                // Author
    '2.0',                            // Version
    [
        'permissions' => 'My',
        'priority'    => 200,
        'type'        => 'plugin',
    ]
);
