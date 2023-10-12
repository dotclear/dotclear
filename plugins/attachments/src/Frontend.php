<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\attachments;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief   The module frontend process.
 * @ingroup attachments
 */
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

        App::frontend()->template()->addBlock('Attachments', FrontendTemplate::Attachments(...));
        App::frontend()->template()->addBlock('AttachmentsHeader', FrontendTemplate::AttachmentsHeader(...));
        App::frontend()->template()->addBlock('AttachmentsFooter', FrontendTemplate::AttachmentsFooter(...));
        App::frontend()->template()->addValue('AttachmentMimeType', FrontendTemplate::AttachmentMimeType(...));
        App::frontend()->template()->addValue('AttachmentType', FrontendTemplate::AttachmentType(...));
        App::frontend()->template()->addValue('AttachmentFileName', FrontendTemplate::AttachmentFileName(...));
        App::frontend()->template()->addValue('AttachmentSize', FrontendTemplate::AttachmentSize(...));
        App::frontend()->template()->addValue('AttachmentTitle', FrontendTemplate::AttachmentTitle(...));
        App::frontend()->template()->addValue('AttachmentThumbnailURL', FrontendTemplate::AttachmentThumbnailURL(...));
        App::frontend()->template()->addValue('AttachmentURL', FrontendTemplate::AttachmentURL(...));
        App::frontend()->template()->addValue('MediaURL', FrontendTemplate::MediaURL(...));
        App::frontend()->template()->addBlock('AttachmentIf', FrontendTemplate::AttachmentIf(...));

        App::frontend()->template()->addValue('EntryAttachmentCount', FrontendTemplate::EntryAttachmentCount(...));

        App::behavior()->addBehavior('tplIfConditions', FrontendBehaviors::tplIfConditions(...));

        return true;
    }
}
