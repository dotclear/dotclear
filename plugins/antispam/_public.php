<?php
/**
 * @brief antispam, a plugin for Dotclear 2
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

dcCore::app()->addBehavior('publicBeforeCommentCreate', [dcAntispam::class, 'isSpam']);
dcCore::app()->addBehavior('publicBeforeTrackbackCreate', [dcAntispam::class, 'isSpam']);
dcCore::app()->addBehavior('publicBeforeDocumentV2', [dcAntispam::class, 'purgeOldSpam']);
