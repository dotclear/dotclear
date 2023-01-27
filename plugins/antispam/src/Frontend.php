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

dcCore::app()->addBehaviors([
    'publicBeforeCommentCreate'   => [dcAntispam::class, 'isSpam'],
    'publicBeforeTrackbackCreate' => [dcAntispam::class, 'isSpam'],
    'publicBeforeDocumentV2'      => [dcAntispam::class, 'purgeOldSpam'],
]);
