<?php
/**
 * @brief akismet, an antispam filter plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
use Dotclear\Core\Core;

$this->registerModule(
    'Akismet',                        // Name
    'Akismet interface for Dotclear', // Description
    'Olivier Meunier',                // Author
    '2.0',                            // Version
    [
        'permissions' => Core::auth()->makePermissions([
            Core::auth()::PERMISSION_USAGE,
            Core::auth()::PERMISSION_CONTENT_ADMIN,
        ]),
        'priority' => 200,
        'type'     => 'plugin',
    ]
);
