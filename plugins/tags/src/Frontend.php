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

        dcCore::app()->public->tpl->addBlock('Tags', FrontendTemplate::Tags(...));
        dcCore::app()->public->tpl->addBlock('TagsHeader', FrontendTemplate::TagsHeader(...));
        dcCore::app()->public->tpl->addBlock('TagsFooter', FrontendTemplate::TagsFooter(...));
        dcCore::app()->public->tpl->addBlock('EntryTags', FrontendTemplate::EntryTags(...));
        dcCore::app()->public->tpl->addBlock('TagIf', FrontendTemplate::TagIf(...));
        dcCore::app()->public->tpl->addValue('TagID', FrontendTemplate::TagID(...));
        dcCore::app()->public->tpl->addValue('TagCount', FrontendTemplate::TagCount(...));
        dcCore::app()->public->tpl->addValue('TagPercent', FrontendTemplate::TagPercent(...));
        dcCore::app()->public->tpl->addValue('TagRoundPercent', FrontendTemplate::TagRoundPercent(...));
        dcCore::app()->public->tpl->addValue('TagURL', FrontendTemplate::TagURL(...));
        dcCore::app()->public->tpl->addValue('TagCloudURL', FrontendTemplate::TagCloudURL(...));
        dcCore::app()->public->tpl->addValue('TagFeedURL', FrontendTemplate::TagFeedURL(...));

        /*
        # Kept for backward compatibility (for now)
        dcCore::app()->public->tpl->addBlock('MetaData', FrontendTemplate::Tags(...));
        dcCore::app()->public->tpl->addBlock('MetaDataHeader', FrontendTemplate::TagsHeader(...));
        dcCore::app()->public->tpl->addBlock('MetaDataFooter', FrontendTemplate::TagsFooter(...));
        dcCore::app()->public->tpl->addValue('MetaID', FrontendTemplate::TagID(...));
        dcCore::app()->public->tpl->addValue('MetaPercent', FrontendTemplate::TagPercent(...));
        dcCore::app()->public->tpl->addValue('MetaRoundPercent', FrontendTemplate::TagRoundPercent(...));
        dcCore::app()->public->tpl->addValue('MetaURL', FrontendTemplate::TagURL(...));
        dcCore::app()->public->tpl->addValue('MetaAllURL', FrontendTemplate::TagCloudURL(...));
        dcCore::app()->public->tpl->addBlock('EntryMetaData', FrontendTemplate::EntryTags(...));
        */

        dcCore::app()->behavior->addBehaviors([
            'publicPrependV2'        => FrontendBehaviors::publicPrepend(...),
            'templateBeforeBlockV2'  => FrontendBehaviors::templateBeforeBlock(...),
            'publicBeforeDocumentV2' => FrontendBehaviors::addTplPath(...),

            'initWidgets' => Widgets::initWidgets(...),
        ]);

        return true;
    }
}
