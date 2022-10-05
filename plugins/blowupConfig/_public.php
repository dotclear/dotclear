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

if (dcCore::app()->blog->settings->system->theme !== 'default') {
    // It's not Blowup
    return;
}

dcCore::app()->addBehavior('publicHeadContent', [tplBlowUpTheme::class, 'publicHeadContent']);
