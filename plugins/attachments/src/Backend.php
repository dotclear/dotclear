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

dcCore::app()->addBehaviors([
    'adminPostFormItems' => [attachmentAdminBehaviors::class, 'adminPostFormItems'],
    'adminPostAfterForm' => [attachmentAdminBehaviors::class, 'adminPostAfterForm'],
    'adminPostHeaders'   => fn () => dcPage::jsModuleLoad('attachments/js/post.js'),
    'adminPageFormItems' => [attachmentAdminBehaviors::class, 'adminPostFormItems'],
    'adminPageAfterForm' => [attachmentAdminBehaviors::class, 'adminPostAfterForm'],
    'adminPageHeaders'   => fn () => dcPage::jsModuleLoad('attachments/js/post.js'),
]);

dcCore::app()->addBehavior('adminPageHelpBlock', [attachmentAdminBehaviors::class, 'adminPageHelpBlock']);
