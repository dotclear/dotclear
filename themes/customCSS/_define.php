<?php
/**
 * @brief Custom, a theme for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Themes
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "Custom theme",             // Name
    "A CSS customizable theme", // Description
    "Olivier",                  // Author
    '1.2',                      // Version
    array(
        'type' => 'theme'
    )
);
