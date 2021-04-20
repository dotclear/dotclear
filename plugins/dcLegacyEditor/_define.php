<?php
/**
 * @brief dcLegacyEditor, a plugin for Dotclear 2
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
    'dcLegacyEditor',         // Name
    'dotclear legacy editor', // Description
    'dotclear Team',          // Author
    '0.1.4',                  // Version
    [
        'permissions' => 'usage,contentadmin',
        'type'        => 'plugin',
        'settings'    => [
            'self' => '',
            'pref' => '#user-options.user_options_edition'
        ]
    ]
);
