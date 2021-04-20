<?php
/**
 * @brief dcCKEditor, a plugin for Dotclear 2
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
    'dcCKEditor',                    // Name
    'dotclear CKEditor integration', // Description
    'dotclear Team',                 // Author
    '1.2',                           // Version
    [
        'permissions' => 'usage,contentadmin',
        'type'        => 'plugin',
        'settings'    => [
            'self' => '',
            'pref' => '#user-options.user_options_edition'
        ]
    ]
);
