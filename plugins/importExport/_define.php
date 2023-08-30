<?php
/**
 * @brief importExport, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
use Dotclear\App;

$this->registerModule(
    'Import / Export',                // Name
    'Import and Export your blog',    // Description
    'Olivier Meunier & Contributors', // Author
    '4.0',                            // Version
    [
        'permissions' => App::auth()->makePermissions([
            App::auth()::PERMISSION_ADMIN,
        ]),
        'type' => 'plugin',
    ]
);
