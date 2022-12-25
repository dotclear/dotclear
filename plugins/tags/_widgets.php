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
if (!defined('DC_RC_PATH')) {
    return;
}

dcCore::app()->addBehaviors([
    'initWidgets'        => [tagsWidgets::class, 'initWidgets'],
    'initDefaultWidgets' => [tagsWidgets::class, 'initDefaultWidgets'],
]);
