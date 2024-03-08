<?php
/**
 * @file
 * @brief       The plugin breadcrumb definition
 * @ingroup     breadcrumb
 *
 * @defgroup    breadcrumb Plugin breadcrumb.
 *
 * breadcrumb, breadcrumb plugin for Dotclear.
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
$this->registerModule(
    'Breadcrumb',              // Name
    'Breadcrumb for Dotclear', // Description
    'Franck Paul',             // Author
    '1.1',                     // Version
    [
        'permissions' => 'My',
        'type'        => 'plugin',  // Type
        'settings'    => [
            'blog' => '#params.breadcrumb_params',
        ],
    ]
);
