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

        dcCore::app()->tpl->addBlock('Tags', [FrontendTemplate::class, 'Tags']);
        dcCore::app()->tpl->addBlock('TagsHeader', [FrontendTemplate::class, 'TagsHeader']);
        dcCore::app()->tpl->addBlock('TagsFooter', [FrontendTemplate::class, 'TagsFooter']);
        dcCore::app()->tpl->addBlock('EntryTags', [FrontendTemplate::class, 'EntryTags']);
        dcCore::app()->tpl->addBlock('TagIf', [FrontendTemplate::class, 'TagIf']);
        dcCore::app()->tpl->addValue('TagID', [FrontendTemplate::class, 'TagID']);
        dcCore::app()->tpl->addValue('TagCount', [FrontendTemplate::class, 'TagCount']);
        dcCore::app()->tpl->addValue('TagPercent', [FrontendTemplate::class, 'TagPercent']);
        dcCore::app()->tpl->addValue('TagRoundPercent', [FrontendTemplate::class, 'TagRoundPercent']);
        dcCore::app()->tpl->addValue('TagURL', [FrontendTemplate::class, 'TagURL']);
        dcCore::app()->tpl->addValue('TagCloudURL', [FrontendTemplate::class, 'TagCloudURL']);
        dcCore::app()->tpl->addValue('TagFeedURL', [FrontendTemplate::class, 'TagFeedURL']);

        /*
        # Kept for backward compatibility (for now)
        dcCore::app()->tpl->addBlock('MetaData', [FrontendTemplate::class, 'Tags']);
        dcCore::app()->tpl->addBlock('MetaDataHeader', [FrontendTemplate::class, 'TagsHeader']);
        dcCore::app()->tpl->addBlock('MetaDataFooter', [FrontendTemplate::class, 'TagsFooter']);
        dcCore::app()->tpl->addValue('MetaID', [FrontendTemplate::class, 'TagID']);
        dcCore::app()->tpl->addValue('MetaPercent', [FrontendTemplate::class, 'TagPercent']);
        dcCore::app()->tpl->addValue('MetaRoundPercent', [FrontendTemplate::class, 'TagRoundPercent']);
        dcCore::app()->tpl->addValue('MetaURL', [FrontendTemplate::class, 'TagURL']);
        dcCore::app()->tpl->addValue('MetaAllURL', [FrontendTemplate::class, 'TagCloudURL']);
        dcCore::app()->tpl->addBlock('EntryMetaData', [FrontendTemplate::class, 'EntryTags']);
        */

        Core::behavior()->addBehaviors([
            'publicPrependV2'        => [FrontendBehaviors::class, 'publicPrepend'],
            'templateBeforeBlockV2'  => [FrontendBehaviors::class, 'templateBeforeBlock'],
            'publicBeforeDocumentV2' => [FrontendBehaviors::class, 'addTplPath'],

            'initWidgets' => [Widgets::class, 'initWidgets'],
        ]);

        return true;
    }
}
