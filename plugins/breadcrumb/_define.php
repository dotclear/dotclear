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

if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "Breadcrumb",              // Name
    "Breadcrumb for Dotclear", // Description
    "Franck Paul",             // Author
    '0.7',                     // Version
    array(
        'permissions' => 'usage,contentadmin', // Permissions
        'type'        => 'plugin',             // Type
        'settings'    => array(
            'blog' => '#params.breadcrumb_params'
        )
    )
);
