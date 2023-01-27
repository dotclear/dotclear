<?php
/**
 * @brief buildtools, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
$this->registerModule(
    'buildtools',                             // Name
    'Internal build tools for dotclear team', // Description
    'dcTeam',                                 // Author
    '2.0',                                    // Version
    [
        'permissions' => dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_ADMIN,
        ]),
    ]
);
