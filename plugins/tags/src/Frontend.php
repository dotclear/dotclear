<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\tags;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief   The module frontend process.
 * @ingroup tags
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

        App::frontend()->template()->addBlock('Tags', FrontendTemplate::Tags(...));
        App::frontend()->template()->addBlock('TagsHeader', FrontendTemplate::TagsHeader(...));
        App::frontend()->template()->addBlock('TagsFooter', FrontendTemplate::TagsFooter(...));
        App::frontend()->template()->addBlock('EntryTags', FrontendTemplate::EntryTags(...));
        App::frontend()->template()->addBlock('TagIf', FrontendTemplate::TagIf(...));
        App::frontend()->template()->addValue('TagID', FrontendTemplate::TagID(...));
        App::frontend()->template()->addValue('TagCount', FrontendTemplate::TagCount(...));
        App::frontend()->template()->addValue('TagPercent', FrontendTemplate::TagPercent(...));
        App::frontend()->template()->addValue('TagRoundPercent', FrontendTemplate::TagRoundPercent(...));
        App::frontend()->template()->addValue('TagURL', FrontendTemplate::TagURL(...));
        App::frontend()->template()->addValue('TagCloudURL', FrontendTemplate::TagCloudURL(...));
        App::frontend()->template()->addValue('TagFeedURL', FrontendTemplate::TagFeedURL(...));

        App::behavior()->addBehaviors([
            'publicPrependV2'        => FrontendBehaviors::publicPrepend(...),
            'templateBeforeBlockV2'  => FrontendBehaviors::templateBeforeBlock(...),
            'publicBeforeDocumentV2' => FrontendBehaviors::addTplPath(...),

            'initWidgets' => Widgets::initWidgets(...),
        ]);

        return true;
    }
}
