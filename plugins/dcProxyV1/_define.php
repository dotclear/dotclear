<?php
/**
 * @brief dcProxyV1, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
use Dotclear\Core\Core;

$this->registerModule(
    'dcProxyV1',
    'Cope with old core classes (< 2.26)',
    'Franck Paul',
    '1.0',
    [
        'permissions' => Core::auth()->makePermissions([
            Core::auth()::PERMISSION_USAGE,
            Core::auth()::PERMISSION_CONTENT_ADMIN,
        ]),
        'type'     => 'plugin',
        'priority' => 0,    // No plugins/themes loaded before this one
    ]
);
