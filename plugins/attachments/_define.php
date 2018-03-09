<?php
/**
 * @brief attachments, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "attachments",             // Name
    "Manage post attachments", // Description
    "Dotclear Team",           // Author
    '1.1',                     // Version
    array(
        'permissions' => 'usage,contentadmin,pages',
        'priority'    => 999,
        'type'        => 'plugin'
    )
);
