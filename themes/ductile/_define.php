<?php
/**
 * @brief Ductile, a theme for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Themes
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "Ductile",                              // Name
    "Mediaqueries compliant elegant theme", // Description
    "Dotclear Team",                        // Author
    '1.5',                                  // Version
    array(                                  // Properties
        'standalone_config' => true,
        'type'              => 'theme'
    )
);
