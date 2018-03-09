<?php
/**
 * @brief simpleMenu, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "simpleMenu",               // Name
    "Simple menu for Dotclear", // Description
    "Franck Paul",              // Author
    '1.5',                      // Version
    array(
        'permissions' => 'admin',
        'type'        => 'plugin',
        'settings'    => array(
            'self' => ''
        )
    )
);
