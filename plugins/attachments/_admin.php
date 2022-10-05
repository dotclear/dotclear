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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

dcCore::app()->addBehavior('adminPostFormItems', [attachmentAdminBehaviors::class, 'adminPostFormItems']);
dcCore::app()->addBehavior('adminPostAfterForm', [attachmentAdminBehaviors::class, 'adminPostAfterForm']);

dcCore::app()->addBehavior(
    'adminPostHeaders',
    fn () => dcPage::jsModuleLoad('attachments/js/post.js')
);

dcCore::app()->addBehavior('adminPageFormItems', [attachmentAdminBehaviors::class, 'adminPostFormItems']);
dcCore::app()->addBehavior('adminPageAfterForm', [attachmentAdminBehaviors::class, 'adminPostAfterForm']);

dcCore::app()->addBehavior(
    'adminPageHeaders',
    fn () => dcPage::jsModuleLoad('attachments/js/post.js')
);

dcCore::app()->addBehavior('adminPageHelpBlock', [attachmentAdminBehaviors::class, 'adminPageHelpBlock']);
