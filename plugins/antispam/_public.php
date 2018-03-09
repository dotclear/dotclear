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

if (!defined('DC_RC_PATH')) {return;}

$core->addBehavior('publicBeforeCommentCreate', array('dcAntispam', 'isSpam'));
$core->addBehavior('publicBeforeTrackbackCreate', array('dcAntispam', 'isSpam'));
$core->addBehavior('publicBeforeDocument', array('dcAntispam', 'purgeOldSpam'));
