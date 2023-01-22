<?php
/**
 * @brief blogroll, a plugin for Dotclear 2
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
    'Blogroll',             // Name
    'Manage your blogroll', // Description
    'Olivier Meunier',      // Author
    '2.0',                  // Version
    [
        'permissions' => dcCore::app()->auth->makePermissions([
            dcBlogroll::PERMISSION_BLOGROLL,
        ]),
        'type'        => 'plugin',
    ]
);
