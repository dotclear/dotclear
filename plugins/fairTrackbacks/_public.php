<?php
/**
 * @brief fairTrackbacks, an antispam filter plugin for Dotclear 2
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

if (DC_FAIRTRACKBACKS_FORCE) {
    $__autoload['dcFilterFairTrackbacks'] = __DIR__ . '/class.dc.filter.fairtrackbacks.php';
    dcCore::app()->spamfilters[]          = 'dcFilterFairTrackbacks';
}
