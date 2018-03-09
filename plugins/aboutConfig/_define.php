<?php
/**
 * @brief aboutConfig, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "about:config",                              // Name
    "Manage every blog configuration directive", // Description
    "Olivier Meunier",                           // Author
    '0.5',                                       // Version
    array(
        'type' => 'plugin'
    )

);
