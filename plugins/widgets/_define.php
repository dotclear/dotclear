<?php
/**
 * @brief widgets, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "Widgets",                         // Name
    "Widgets for your blog sidebars",  // Description
    "Olivier Meunier & Dotclear Team", // Author
    '3.4',                             // Version
    array(
        'permissions' => 'admin',
        'priority'    => 1000000000,
        'type'        => 'plugin'
    )
);
