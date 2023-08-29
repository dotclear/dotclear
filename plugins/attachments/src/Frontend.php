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
use Dotclear\Core\Process;

class Frontend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        dcCore::app()->public->tpl->addBlock('Attachments', FrontendTemplate::Attachments(...));
        dcCore::app()->public->tpl->addBlock('AttachmentsHeader', FrontendTemplate::AttachmentsHeader(...));
        dcCore::app()->public->tpl->addBlock('AttachmentsFooter', FrontendTemplate::AttachmentsFooter(...));
        dcCore::app()->public->tpl->addValue('AttachmentMimeType', FrontendTemplate::AttachmentMimeType(...));
        dcCore::app()->public->tpl->addValue('AttachmentType', FrontendTemplate::AttachmentType(...));
        dcCore::app()->public->tpl->addValue('AttachmentFileName', FrontendTemplate::AttachmentFileName(...));
        dcCore::app()->public->tpl->addValue('AttachmentSize', FrontendTemplate::AttachmentSize(...));
        dcCore::app()->public->tpl->addValue('AttachmentTitle', FrontendTemplate::AttachmentTitle(...));
        dcCore::app()->public->tpl->addValue('AttachmentThumbnailURL', FrontendTemplate::AttachmentThumbnailURL(...));
        dcCore::app()->public->tpl->addValue('AttachmentURL', FrontendTemplate::AttachmentURL(...));
        dcCore::app()->public->tpl->addValue('MediaURL', FrontendTemplate::MediaURL(...));
        dcCore::app()->public->tpl->addBlock('AttachmentIf', FrontendTemplate::AttachmentIf(...));

        dcCore::app()->public->tpl->addValue('EntryAttachmentCount', FrontendTemplate::EntryAttachmentCount(...));

        dcCore::app()->behavior->addBehavior('tplIfConditions', FrontendBehaviors::tplIfConditions(...));

        return true;
    }
}
