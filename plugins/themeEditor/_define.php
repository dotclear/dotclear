<?php
/**
 * @brief themeEditor, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "themeEditor",     // Name
    "Theme Editor",    // Description
    "Olivier Meunier", // Author
    '1.3',             // Version
    array(
        'type'     => 'plugin',
        'settings' => array(
            'pref' => '#user-options.themeEditor_prefs'
        )
    )
);
