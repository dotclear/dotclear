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

use Dotclear\App;
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

        App::frontend()->tpl->addBlock('Attachments', FrontendTemplate::Attachments(...));
        App::frontend()->tpl->addBlock('AttachmentsHeader', FrontendTemplate::AttachmentsHeader(...));
        App::frontend()->tpl->addBlock('AttachmentsFooter', FrontendTemplate::AttachmentsFooter(...));
        App::frontend()->tpl->addValue('AttachmentMimeType', FrontendTemplate::AttachmentMimeType(...));
        App::frontend()->tpl->addValue('AttachmentType', FrontendTemplate::AttachmentType(...));
        App::frontend()->tpl->addValue('AttachmentFileName', FrontendTemplate::AttachmentFileName(...));
        App::frontend()->tpl->addValue('AttachmentSize', FrontendTemplate::AttachmentSize(...));
        App::frontend()->tpl->addValue('AttachmentTitle', FrontendTemplate::AttachmentTitle(...));
        App::frontend()->tpl->addValue('AttachmentThumbnailURL', FrontendTemplate::AttachmentThumbnailURL(...));
        App::frontend()->tpl->addValue('AttachmentURL', FrontendTemplate::AttachmentURL(...));
        App::frontend()->tpl->addValue('MediaURL', FrontendTemplate::MediaURL(...));
        App::frontend()->tpl->addBlock('AttachmentIf', FrontendTemplate::AttachmentIf(...));

        App::frontend()->tpl->addValue('EntryAttachmentCount', FrontendTemplate::EntryAttachmentCount(...));

        App::behavior()->addBehavior('tplIfConditions', FrontendBehaviors::tplIfConditions(...));

        return true;
    }
}
