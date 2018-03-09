<?php
/**
 * @brief blogroll, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "Blogroll",             // Name
    "Manage your blogroll", // Description
    "Olivier Meunier",      // Author
    '1.4',                  // Version
    array(
        'permissions' => 'blogroll',
        'type'        => 'plugin'
    )
);
