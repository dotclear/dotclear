<?php
/**
 * @brief dclegacy, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "dcLegacy",                    // Name
    "Legacy modules for dotclear", // Description
    "dc Team",                     // Author
    '1.0',                         // Version
    array(
        'priority' => 500,
        'type'     => 'plugin'
    )
);
