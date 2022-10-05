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

// Attachments
dcCore::app()->tpl->addBlock('Attachments', [attachmentTpl::class, 'Attachments']);
dcCore::app()->tpl->addBlock('AttachmentsHeader', [attachmentTpl::class, 'AttachmentsHeader']);
dcCore::app()->tpl->addBlock('AttachmentsFooter', [attachmentTpl::class, 'AttachmentsFooter']);
dcCore::app()->tpl->addValue('AttachmentMimeType', [attachmentTpl::class, 'AttachmentMimeType']);
dcCore::app()->tpl->addValue('AttachmentType', [attachmentTpl::class, 'AttachmentType']);
dcCore::app()->tpl->addValue('AttachmentFileName', [attachmentTpl::class, 'AttachmentFileName']);
dcCore::app()->tpl->addValue('AttachmentSize', [attachmentTpl::class, 'AttachmentSize']);
dcCore::app()->tpl->addValue('AttachmentTitle', [attachmentTpl::class, 'AttachmentTitle']);
dcCore::app()->tpl->addValue('AttachmentThumbnailURL', [attachmentTpl::class, 'AttachmentThumbnailURL']);
dcCore::app()->tpl->addValue('AttachmentURL', [attachmentTpl::class, 'AttachmentURL']);
dcCore::app()->tpl->addValue('MediaURL', [attachmentTpl::class, 'MediaURL']);
dcCore::app()->tpl->addBlock('AttachmentIf', [attachmentTpl::class, 'AttachmentIf']);

dcCore::app()->tpl->addValue('EntryAttachmentCount', [attachmentTpl::class, 'EntryAttachmentCount']);

dcCore::app()->addBehavior('tplIfConditions', [attachmentBehavior::class, 'tplIfConditions']);
