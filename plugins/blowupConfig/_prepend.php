<?php
/**
 * @brief blowupConfig, a plugin for Dotclear 2
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
    'blowupConfig'   => __DIR__ . '/inc/blowup.config.php',
    'tplBlowUpTheme' => __DIR__ . '/inc/public.tpl.php',
]);
