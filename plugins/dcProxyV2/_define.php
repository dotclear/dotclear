<?php
/**
 * @brief dcProxyV2, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
use Dotclear\Core\Core;

$this->registerModule(
    'dcProxyV2',
    'Cope with function/method footprint V1 (< 2.24, 2.25)',
    'Franck Paul',
    '2.0',
    [
        'permissions' => Core::auth()->makePermissions([
            Core::auth()::PERMISSION_USAGE,
            Core::auth()::PERMISSION_CONTENT_ADMIN,
        ]),
        'type'     => 'plugin',
        'priority' => 99_999_999_998,
    ]
);
