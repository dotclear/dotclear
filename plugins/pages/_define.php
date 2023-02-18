<?php
/**
 * @brief pages, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
$this->registerModule(
    'Pages',                             // Name
    'Serve entries as simple web pages', // Description
    'Olivier Meunier',                   // Author
    '2.0',                               // Version
    [
        'permissions' => dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_CONTENT_ADMIN,
            initPages::PERMISSION_PAGES,
        ]),
        'priority'    => 999,
        'type'        => 'plugin',
    ]
);
