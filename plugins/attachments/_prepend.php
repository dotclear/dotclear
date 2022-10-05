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
if (!defined('DC_RC_PATH')) {
    return;
}

Clearbricks::lib()->autoload([
    'attachmentAdminBehaviors' => __DIR__ . '/inc/admin.behaviors.php',
    'attachmentTpl'            => __DIR__ . '/inc/public.tpl.php',
    'attachmentBehavior'       => __DIR__ . '/inc/public.behaviors.php',
]);
