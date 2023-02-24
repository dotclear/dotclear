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
$this->registerModule(
    'dcProxyV1',
    'Cope with old core classes (< 2.26)',
    'Franck Paul',
    '1.0',
    [
        'permissions' => dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_USAGE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]),
        'type'     => 'plugin',
        'priority' => 0,    // No plugins/themes loaded before this one
    ]
);
