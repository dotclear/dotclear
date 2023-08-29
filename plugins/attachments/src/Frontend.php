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

        Core::frontend()->tpl->addBlock('Attachments', FrontendTemplate::Attachments(...));
        Core::frontend()->tpl->addBlock('AttachmentsHeader', FrontendTemplate::AttachmentsHeader(...));
        Core::frontend()->tpl->addBlock('AttachmentsFooter', FrontendTemplate::AttachmentsFooter(...));
        Core::frontend()->tpl->addValue('AttachmentMimeType', FrontendTemplate::AttachmentMimeType(...));
        Core::frontend()->tpl->addValue('AttachmentType', FrontendTemplate::AttachmentType(...));
        Core::frontend()->tpl->addValue('AttachmentFileName', FrontendTemplate::AttachmentFileName(...));
        Core::frontend()->tpl->addValue('AttachmentSize', FrontendTemplate::AttachmentSize(...));
        Core::frontend()->tpl->addValue('AttachmentTitle', FrontendTemplate::AttachmentTitle(...));
        Core::frontend()->tpl->addValue('AttachmentThumbnailURL', FrontendTemplate::AttachmentThumbnailURL(...));
        Core::frontend()->tpl->addValue('AttachmentURL', FrontendTemplate::AttachmentURL(...));
        Core::frontend()->tpl->addValue('MediaURL', FrontendTemplate::MediaURL(...));
        Core::frontend()->tpl->addBlock('AttachmentIf', FrontendTemplate::AttachmentIf(...));

        Core::frontend()->tpl->addValue('EntryAttachmentCount', FrontendTemplate::EntryAttachmentCount(...));

        Core::behavior()->addBehavior('tplIfConditions', FrontendBehaviors::tplIfConditions(...));

        return true;
    }
}
