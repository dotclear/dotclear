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
use Dotclear\Core\Core;

$this->registerModule(
    'buildtools',                             // Name
    'Internal build tools for dotclear team', // Description
    'dcTeam',                                 // Author
    '2.0',                                    // Version
    [
        'type'        => 'plugin',
        'permissions' => Core::auth()->makePermissions([
            Core::auth()::PERMISSION_ADMIN,
        ]),
    ]
);
