<?php
/**
 * @brief breadcrumb, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

// dead but useful code, in order to have translations
__('Breadcrumb') . __('Breadcrumb for Dotclear');

dcCore::app()->addBehavior('adminBlogPreferencesFormV2', [breadcrumbBehaviors::class, 'adminBlogPreferencesForm']);
dcCore::app()->addBehavior('adminBeforeBlogSettingsUpdate', [breadcrumbBehaviors::class, 'adminBeforeBlogSettingsUpdate']);
