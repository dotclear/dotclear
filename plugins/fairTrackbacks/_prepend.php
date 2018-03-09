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

if (!defined('DC_RC_PATH')) {return;}

if (!defined('DC_FAIRTRACKBACKS_FORCE')) {
    define('DC_FAIRTRACKBACKS_FORCE', false);
}

if (!DC_FAIRTRACKBACKS_FORCE) {
    $__autoload['dcFilterFairTrackbacks'] = dirname(__FILE__) . '/class.dc.filter.fairtrackbacks.php';
    $core->spamfilters[]                  = 'dcFilterFairTrackbacks';
}
