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
use Dotclear\Core\Core;
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

        Core::frontend()->tpl->addBlock('Attachments', [FrontendTemplate::class, 'Attachments']);
        Core::frontend()->tpl->addBlock('AttachmentsHeader', [FrontendTemplate::class, 'AttachmentsHeader']);
        Core::frontend()->tpl->addBlock('AttachmentsFooter', [FrontendTemplate::class, 'AttachmentsFooter']);
        Core::frontend()->tpl->addValue('AttachmentMimeType', [FrontendTemplate::class, 'AttachmentMimeType']);
        Core::frontend()->tpl->addValue('AttachmentType', [FrontendTemplate::class, 'AttachmentType']);
        Core::frontend()->tpl->addValue('AttachmentFileName', [FrontendTemplate::class, 'AttachmentFileName']);
        Core::frontend()->tpl->addValue('AttachmentSize', [FrontendTemplate::class, 'AttachmentSize']);
        Core::frontend()->tpl->addValue('AttachmentTitle', [FrontendTemplate::class, 'AttachmentTitle']);
        Core::frontend()->tpl->addValue('AttachmentThumbnailURL', [FrontendTemplate::class, 'AttachmentThumbnailURL']);
        Core::frontend()->tpl->addValue('AttachmentURL', [FrontendTemplate::class, 'AttachmentURL']);
        Core::frontend()->tpl->addValue('MediaURL', [FrontendTemplate::class, 'MediaURL']);
        Core::frontend()->tpl->addBlock('AttachmentIf', [FrontendTemplate::class, 'AttachmentIf']);

        Core::frontend()->tpl->addValue('EntryAttachmentCount', [FrontendTemplate::class, 'EntryAttachmentCount']);

        Core::behavior()->addBehavior('tplIfConditions', [FrontendBehaviors::class, 'tplIfConditions']);

        return true;
    }
}
