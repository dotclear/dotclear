<?php
/**
 * @file
 * @brief       The plugin pages definition
 * @ingroup     pages
 * 
 * @defgroup    pages Plugin pages.
 * 
 * pages, Serve entries as simple web pages.
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
use Dotclear\App;

$this->registerModule(
    'Pages',                             // Name
    'Serve entries as simple web pages', // Description
    'Olivier Meunier',                   // Author
    '2.0',                               // Version
    [
        'permissions' => App::auth()->makePermissions([
            App::auth()::PERMISSION_CONTENT_ADMIN,
            initPages::PERMISSION_PAGES,
        ]),
        'priority' => 999,
        'type'     => 'plugin',
    ]
);
