<?php
/**
 * @brief breadcrumb, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
$this->registerModule(
    'Breadcrumb',              // Name
    'Breadcrumb for Dotclear', // Description
    'Franck Paul',             // Author
    '1.0',                     // Version
    [
        'permissions' => dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_USAGE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]),
        'type'        => 'plugin',             // Type
        'settings'    => [
            'blog' => '#params.breadcrumb_params',
        ],
    ]
);
