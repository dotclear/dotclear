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
declare(strict_types=1);

namespace Dotclear\Plugin\attachments;

use dcCore;
use dcPage;
use dcNsProcess;

class Frontend extends dcNsProcess
{
    public static function init(): bool
    {
        self::$init = defined('DC_RC_PATH');

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        dcCore::app()->tpl->addBlock('Attachments', [FrontendTemplate::class, 'Attachments']);
        dcCore::app()->tpl->addBlock('AttachmentsHeader', [FrontendTemplate::class, 'AttachmentsHeader']);
        dcCore::app()->tpl->addBlock('AttachmentsFooter', [FrontendTemplate::class, 'AttachmentsFooter']);
        dcCore::app()->tpl->addValue('AttachmentMimeType', [FrontendTemplate::class, 'AttachmentMimeType']);
        dcCore::app()->tpl->addValue('AttachmentType', [FrontendTemplate::class, 'AttachmentType']);
        dcCore::app()->tpl->addValue('AttachmentFileName', [FrontendTemplate::class, 'AttachmentFileName']);
        dcCore::app()->tpl->addValue('AttachmentSize', [FrontendTemplate::class, 'AttachmentSize']);
        dcCore::app()->tpl->addValue('AttachmentTitle', [FrontendTemplate::class, 'AttachmentTitle']);
        dcCore::app()->tpl->addValue('AttachmentThumbnailURL', [FrontendTemplate::class, 'AttachmentThumbnailURL']);
        dcCore::app()->tpl->addValue('AttachmentURL', [FrontendTemplate::class, 'AttachmentURL']);
        dcCore::app()->tpl->addValue('MediaURL', [FrontendTemplate::class, 'MediaURL']);
        dcCore::app()->tpl->addBlock('AttachmentIf', [FrontendTemplate::class, 'AttachmentIf']);

        dcCore::app()->tpl->addValue('EntryAttachmentCount', [FrontendTemplate::class, 'EntryAttachmentCount']);

        dcCore::app()->addBehavior('tplIfConditions', [FrontendBehaviors::class, 'tplIfConditions']);

        return true;
    }
}
