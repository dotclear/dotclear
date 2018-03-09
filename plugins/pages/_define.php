<?php
/**
 * @brief pages, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "Pages",                             // Name
    "Serve entries as simple web pages", // Description
    "Olivier Meunier",                   // Author
    '1.4',                               // Version
    array(
        'permissions' => 'contentadmin,pages',
        'priority'    => 999,
        'type'        => 'plugin'
    )
);
