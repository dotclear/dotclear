<?php
/**
 * @brief pages, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
dcCore::app()->addBehaviors([
    'initWidgets'        => [pagesWidgets::class, 'initWidgets'],
    'initDefaultWidgets' => [pagesWidgets::class, 'initDefaultWidgets'],
]);
