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

        Core::frontend()->tpl->addBlock('Tags', FrontendTemplate::Tags(...));
        Core::frontend()->tpl->addBlock('TagsHeader', FrontendTemplate::TagsHeader(...));
        Core::frontend()->tpl->addBlock('TagsFooter', FrontendTemplate::TagsFooter(...));
        Core::frontend()->tpl->addBlock('EntryTags', FrontendTemplate::EntryTags(...));
        Core::frontend()->tpl->addBlock('TagIf', FrontendTemplate::TagIf(...));
        Core::frontend()->tpl->addValue('TagID', FrontendTemplate::TagID(...));
        Core::frontend()->tpl->addValue('TagCount', FrontendTemplate::TagCount(...));
        Core::frontend()->tpl->addValue('TagPercent', FrontendTemplate::TagPercent(...));
        Core::frontend()->tpl->addValue('TagRoundPercent', FrontendTemplate::TagRoundPercent(...));
        Core::frontend()->tpl->addValue('TagURL', FrontendTemplate::TagURL(...));
        Core::frontend()->tpl->addValue('TagCloudURL', FrontendTemplate::TagCloudURL(...));
        Core::frontend()->tpl->addValue('TagFeedURL', FrontendTemplate::TagFeedURL(...));

        /*
        # Kept for backward compatibility (for now)
        Core::frontend()->tpl->addBlock('MetaData', FrontendTemplate::Tags(...));
        Core::frontend()->tpl->addBlock('MetaDataHeader', FrontendTemplate::TagsHeader(...));
        Core::frontend()->tpl->addBlock('MetaDataFooter', FrontendTemplate::TagsFooter(...));
        Core::frontend()->tpl->addValue('MetaID', FrontendTemplate::TagID(...));
        Core::frontend()->tpl->addValue('MetaPercent', FrontendTemplate::TagPercent(...));
        Core::frontend()->tpl->addValue('MetaRoundPercent', FrontendTemplate::TagRoundPercent(...));
        Core::frontend()->tpl->addValue('MetaURL', FrontendTemplate::TagURL(...));
        Core::frontend()->tpl->addValue('MetaAllURL', FrontendTemplate::TagCloudURL(...));
        Core::frontend()->tpl->addBlock('EntryMetaData', FrontendTemplate::EntryTags(...));
        */

        Core::behavior()->addBehaviors([
            'publicPrependV2'        => FrontendBehaviors::publicPrepend(...),
            'templateBeforeBlockV2'  => FrontendBehaviors::templateBeforeBlock(...),
            'publicBeforeDocumentV2' => FrontendBehaviors::addTplPath(...),

            'initWidgets' => Widgets::initWidgets(...),
        ]);

        return true;
    }
}
