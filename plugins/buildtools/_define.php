<?php
/**
 * @file
 * @brief       The plugin buildtools definition
 * @ingroup     buildtools
 * 
 * @defgroup    buildtools Plugin buildtools.
 * 
 * buildtools, Internal build tools for dotclear team.
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
use Dotclear\App;

$this->registerModule(
    'buildtools',                             // Name
    'Internal build tools for dotclear team', // Description
    'dcTeam',                                 // Author
    '2.0',                                    // Version
    [
        'type'        => 'plugin',
        'permissions' => App::auth()->makePermissions([
            App::auth()::PERMISSION_ADMIN,
        ]),
    ]
);
