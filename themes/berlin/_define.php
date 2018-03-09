<?php
/**
 * @brief Berlin, a theme for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Themes
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "Berlin",                      // Name
    "Dotclear 2.7+ default theme", // Description
    "Dotclear Team",               // Author
    '1.2',                         // Version
    array(                         // Properties
        'type'   => 'theme',
        'tplset' => 'dotty'
    )
);
