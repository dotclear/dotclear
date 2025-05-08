<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
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

        App::frontend()->template()->addBlocks([
            'Attachments'       => FrontendTemplate::Attachments(...),
            'AttachmentsHeader' => FrontendTemplate::AttachmentsHeader(...),
            'AttachmentsFooter' => FrontendTemplate::AttachmentsFooter(...),
            'AttachmentIf'      => FrontendTemplate::AttachmentIf(...),
        ]);
        App::frontend()->template()->addValues([
            'AttachmentMimeType'     => FrontendTemplate::AttachmentMimeType(...),
            'AttachmentType'         => FrontendTemplate::AttachmentType(...),
            'AttachmentFileName'     => FrontendTemplate::AttachmentFileName(...),
            'AttachmentSize'         => FrontendTemplate::AttachmentSize(...),
            'AttachmentTitle'        => FrontendTemplate::AttachmentTitle(...),
            'AttachmentThumbnailURL' => FrontendTemplate::AttachmentThumbnailURL(...),
            'AttachmentURL'          => FrontendTemplate::AttachmentURL(...),
            'MediaURL'               => FrontendTemplate::MediaURL(...),
            'EntryAttachmentCount'   => FrontendTemplate::EntryAttachmentCount(...),
        ]);
        App::behavior()->addBehaviors([
            'tplIfConditions' => FrontendBehaviors::tplIfConditions(...),
        ]);

        return true;
    }
}
