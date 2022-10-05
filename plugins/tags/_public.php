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
if (!defined('DC_RC_PATH')) {
    return;
}

dcCore::app()->tpl->addBlock('Tags', [tplTags::class, 'Tags']);
dcCore::app()->tpl->addBlock('TagsHeader', [tplTags::class, 'TagsHeader']);
dcCore::app()->tpl->addBlock('TagsFooter', [tplTags::class, 'TagsFooter']);
dcCore::app()->tpl->addBlock('EntryTags', [tplTags::class, 'EntryTags']);
dcCore::app()->tpl->addBlock('TagIf', [tplTags::class, 'TagIf']);
dcCore::app()->tpl->addValue('TagID', [tplTags::class, 'TagID']);
dcCore::app()->tpl->addValue('TagCount', [tplTags::class, 'TagCount']);
dcCore::app()->tpl->addValue('TagPercent', [tplTags::class, 'TagPercent']);
dcCore::app()->tpl->addValue('TagRoundPercent', [tplTags::class, 'TagRoundPercent']);
dcCore::app()->tpl->addValue('TagURL', [tplTags::class, 'TagURL']);
dcCore::app()->tpl->addValue('TagCloudURL', [tplTags::class, 'TagCloudURL']);
dcCore::app()->tpl->addValue('TagFeedURL', [tplTags::class, 'TagFeedURL']);

# Kept for backward compatibility (for now)
dcCore::app()->tpl->addBlock('MetaData', [tplTags::class, 'Tags']);
dcCore::app()->tpl->addBlock('MetaDataHeader', [tplTags::class, 'TagsHeader']);
dcCore::app()->tpl->addBlock('MetaDataFooter', [tplTags::class, 'TagsFooter']);
dcCore::app()->tpl->addValue('MetaID', [tplTags::class, 'TagID']);
dcCore::app()->tpl->addValue('MetaPercent', [tplTags::class, 'TagPercent']);
dcCore::app()->tpl->addValue('MetaRoundPercent', [tplTags::class, 'TagRoundPercent']);
dcCore::app()->tpl->addValue('MetaURL', [tplTags::class, 'TagURL']);
dcCore::app()->tpl->addValue('MetaAllURL', [tplTags::class, 'TagCloudURL']);
dcCore::app()->tpl->addBlock('EntryMetaData', [tplTags::class, 'EntryTags']);

dcCore::app()->addBehavior('publicPrependV2', [publicBehaviorsTags::class, 'publicPrepend']);
dcCore::app()->addBehavior('templateBeforeBlockV2', [publicBehaviorsTags::class, 'templateBeforeBlock']);
dcCore::app()->addBehavior('publicBeforeDocumentV2', [publicBehaviorsTags::class, 'addTplPath']);

require __DIR__ . '/_widgets.php';
