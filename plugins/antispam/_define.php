<?php
/**
 * @brief antispam, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

$this->registerModule(
    'Antispam',                             // Name
    'Generic antispam plugin for Dotclear', // Description
    'Alain Vagner',                         // Author
    '1.4.1',                                // Version
    [
        'permissions' => 'usage,contentadmin',
        'priority'    => 10,
        'settings'    => [
            'self' => '',
            'blog' => '#params.antispam_params'
        ]
    ]
);
