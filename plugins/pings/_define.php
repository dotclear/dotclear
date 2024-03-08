<?php
/**
 * @file
 * @brief       The plugin pings definition
 * @ingroup     pings
 *
 * @defgroup    pings Plugin pings.
 *
 * pings, ping services.
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
$this->registerModule(
    'Pings',           // Name
    'Ping services',   // Description
    'Olivier Meunier', // Author
    '2.0',             // Version
    [
        'permissions' => 'My',
        'type'        => 'plugin',
        'settings'    => [
            'self' => '',
        ],
    ]
);
