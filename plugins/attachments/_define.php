<?php
/**
 * @brief attachments, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
use Dotclear\Core\Core;

$this->registerModule(
    'attachments',             // Name
    'Manage post attachments', // Description
    'Dotclear Team',           // Author
    '2.0',                     // Version
    [
        'permissions' => Core::auth()->makePermissions([
            Core::auth()::PERMISSION_USAGE,
            Core::auth()::PERMISSION_CONTENT_ADMIN,
            initPages::PERMISSION_PAGES,
        ]),
        'priority' => 999,
        'type'     => 'plugin',
    ]
);
