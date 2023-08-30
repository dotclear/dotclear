<?php
/**
 * @brief tags, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\tags;

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

        App::frontend()->tpl->addBlock('Tags', FrontendTemplate::Tags(...));
        App::frontend()->tpl->addBlock('TagsHeader', FrontendTemplate::TagsHeader(...));
        App::frontend()->tpl->addBlock('TagsFooter', FrontendTemplate::TagsFooter(...));
        App::frontend()->tpl->addBlock('EntryTags', FrontendTemplate::EntryTags(...));
        App::frontend()->tpl->addBlock('TagIf', FrontendTemplate::TagIf(...));
        App::frontend()->tpl->addValue('TagID', FrontendTemplate::TagID(...));
        App::frontend()->tpl->addValue('TagCount', FrontendTemplate::TagCount(...));
        App::frontend()->tpl->addValue('TagPercent', FrontendTemplate::TagPercent(...));
        App::frontend()->tpl->addValue('TagRoundPercent', FrontendTemplate::TagRoundPercent(...));
        App::frontend()->tpl->addValue('TagURL', FrontendTemplate::TagURL(...));
        App::frontend()->tpl->addValue('TagCloudURL', FrontendTemplate::TagCloudURL(...));
        App::frontend()->tpl->addValue('TagFeedURL', FrontendTemplate::TagFeedURL(...));

        /*
        # Kept for backward compatibility (for now)
        App::frontend()->tpl->addBlock('MetaData', FrontendTemplate::Tags(...));
        App::frontend()->tpl->addBlock('MetaDataHeader', FrontendTemplate::TagsHeader(...));
        App::frontend()->tpl->addBlock('MetaDataFooter', FrontendTemplate::TagsFooter(...));
        App::frontend()->tpl->addValue('MetaID', FrontendTemplate::TagID(...));
        App::frontend()->tpl->addValue('MetaPercent', FrontendTemplate::TagPercent(...));
        App::frontend()->tpl->addValue('MetaRoundPercent', FrontendTemplate::TagRoundPercent(...));
        App::frontend()->tpl->addValue('MetaURL', FrontendTemplate::TagURL(...));
        App::frontend()->tpl->addValue('MetaAllURL', FrontendTemplate::TagCloudURL(...));
        App::frontend()->tpl->addBlock('EntryMetaData', FrontendTemplate::EntryTags(...));
        */

        App::behavior()->addBehaviors([
            'publicPrependV2'        => FrontendBehaviors::publicPrepend(...),
            'templateBeforeBlockV2'  => FrontendBehaviors::templateBeforeBlock(...),
            'publicBeforeDocumentV2' => FrontendBehaviors::addTplPath(...),

            'initWidgets' => Widgets::initWidgets(...),
        ]);

        return true;
    }
}
