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
use Dotclear\App;

$this->registerModule(
    'Blogroll',             // Name
    'Manage your blogroll', // Description
    'Olivier Meunier',      // Author
    '2.0',                  // Version
    [
        'permissions' => App::auth()->makePermissions([
            initBlogroll::PERMISSION_BLOGROLL,
        ]),
        'type' => 'plugin',
    ]
);
