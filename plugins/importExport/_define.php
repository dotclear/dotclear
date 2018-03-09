<?php
/**
 * @brief importExport, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "Import / Export",                // Name
    "Import and Export your blog",    // Description
    "Olivier Meunier & Contributors", // Author
    '3.2',                            // Version
    array(
        'permissions' => 'admin',
        'type'        => 'plugin'
    )
);
