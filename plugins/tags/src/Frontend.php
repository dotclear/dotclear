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

        Core::frontend()->tpl->addBlock('Tags', [FrontendTemplate::class, 'Tags']);
        Core::frontend()->tpl->addBlock('TagsHeader', [FrontendTemplate::class, 'TagsHeader']);
        Core::frontend()->tpl->addBlock('TagsFooter', [FrontendTemplate::class, 'TagsFooter']);
        Core::frontend()->tpl->addBlock('EntryTags', [FrontendTemplate::class, 'EntryTags']);
        Core::frontend()->tpl->addBlock('TagIf', [FrontendTemplate::class, 'TagIf']);
        Core::frontend()->tpl->addValue('TagID', [FrontendTemplate::class, 'TagID']);
        Core::frontend()->tpl->addValue('TagCount', [FrontendTemplate::class, 'TagCount']);
        Core::frontend()->tpl->addValue('TagPercent', [FrontendTemplate::class, 'TagPercent']);
        Core::frontend()->tpl->addValue('TagRoundPercent', [FrontendTemplate::class, 'TagRoundPercent']);
        Core::frontend()->tpl->addValue('TagURL', [FrontendTemplate::class, 'TagURL']);
        Core::frontend()->tpl->addValue('TagCloudURL', [FrontendTemplate::class, 'TagCloudURL']);
        Core::frontend()->tpl->addValue('TagFeedURL', [FrontendTemplate::class, 'TagFeedURL']);

        /*
        # Kept for backward compatibility (for now)
        Core::frontend()->tpl->addBlock('MetaData', [FrontendTemplate::class, 'Tags']);
        Core::frontend()->tpl->addBlock('MetaDataHeader', [FrontendTemplate::class, 'TagsHeader']);
        Core::frontend()->tpl->addBlock('MetaDataFooter', [FrontendTemplate::class, 'TagsFooter']);
        Core::frontend()->tpl->addValue('MetaID', [FrontendTemplate::class, 'TagID']);
        Core::frontend()->tpl->addValue('MetaPercent', [FrontendTemplate::class, 'TagPercent']);
        Core::frontend()->tpl->addValue('MetaRoundPercent', [FrontendTemplate::class, 'TagRoundPercent']);
        Core::frontend()->tpl->addValue('MetaURL', [FrontendTemplate::class, 'TagURL']);
        Core::frontend()->tpl->addValue('MetaAllURL', [FrontendTemplate::class, 'TagCloudURL']);
        Core::frontend()->tpl->addBlock('EntryMetaData', [FrontendTemplate::class, 'EntryTags']);
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
