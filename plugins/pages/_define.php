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
if (!defined('DC_RC_PATH')) {
    return;
}

$this->registerModule(
    'Pages',                             // Name
    'Serve entries as simple web pages', // Description
    'Olivier Meunier',                   // Author
    '1.5',                               // Version
    [
        'permissions' => dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_CONTENT_ADMIN,
            dcPages::PERMISSION_PAGES,
        ]),
        'priority' => 999,
        'type'     => 'plugin',
    ]
);
