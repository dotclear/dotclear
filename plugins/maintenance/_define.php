<?php
/**
 * @file
 * @brief       The plugin maintenance definition
 * @ingroup     maintenance
 * 
 * @defgroup    maintenance Plugin maintenance.
 * 
 * maintenance, Maintain your installation.
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
$this->registerModule(
    'Maintenance',                            // Name
    'Maintain your installation',             // Description
    'Olivier Meunier & Association Dotclear', // Author
    '2.0',                                    // Version
    [
        'permissions' => 'My',
        'type'        => 'plugin',
        'settings'    => [
            'self' => '#settings',
        ],
    ]
);
