<?php
/**
 * @brief akismet, an antispam filter plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "Akismet",                        // Name
    "Akismet interface for Dotclear", // Description
    "Olivier Meunier",                // Author
    '1.1',                            // Version
    array(
        'permissions' => 'usage,contentadmin',
        'priority'    => 200,
        'type'        => 'plugin'
    )
);
