<?php
/**
 * @file
 * @brief       The plugin dcProxyV1 definition
 * @ingroup     dcProxyV1
 * 
 * @defgroup    dcProxyV1 Plugin dcProxyV1.
 * 
 * dcProxyV1, Cope with old core classes (< 2.26).
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
use Dotclear\App;

$this->registerModule(
    'dcProxyV1',
    'Cope with old core classes (< 2.26)',
    'Franck Paul',
    '1.0',
    [
        'permissions' => App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]),
        'type'     => 'plugin',
        'priority' => 0,    // No plugins/themes loaded before this one
    ]
);
