<?php
/**
 * @brief userPref, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "user:preferences",                       // Name
    "Manage every user preference directive", // Description
    "Franck Paul",                            // Author
    '0.3',                                    // Version
    array(
        'type' => 'plugin'
    )
);
