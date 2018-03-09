<?php
/**
 * @brief blowupConfig, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "Blowup Config",               // Name
    "Configure your Blowup Theme", // Description
    "Olivier Meunier",             // Author
    '1.2',                         // Version
    array(
        'permissions' => 'admin',
        'type'        => 'plugin'
    )
);
