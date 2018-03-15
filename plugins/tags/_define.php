<?php
/**
 * @brief tags, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "Tags",            // Name
    "Tags for posts",  // Description
    "Olivier Meunier", // Author
    '1.5',             // Version
    array(
        'permissions' => 'usage,contentadmin',
        'priority'    => 1001, // Must be higher than dcLegacyEditor/dcCKEditor priority (ie 1000)
        'type'        => 'plugin',
        'settings'    => array(
            'pref' => '#user-options.tags_prefs'
        )
    )
);
