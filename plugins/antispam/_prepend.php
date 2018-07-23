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

$__autoload['dcSpamFilter']  = dirname(__FILE__) . '/inc/class.dc.spamfilter.php';
$__autoload['dcSpamFilters'] = dirname(__FILE__) . '/inc/class.dc.spamfilters.php';
$__autoload['dcAntispam']    = dirname(__FILE__) . '/inc/lib.dc.antispam.php';
$__autoload['dcAntispamURL'] = dirname(__FILE__) . '/inc/lib.dc.antispam.url.php';

$__autoload['dcFilterIP']          = dirname(__FILE__) . '/filters/class.dc.filter.ip.php';
$__autoload['dcFilterIpLookup']    = dirname(__FILE__) . '/filters/class.dc.filter.iplookup.php';
$__autoload['dcFilterLinksLookup'] = dirname(__FILE__) . '/filters/class.dc.filter.linkslookup.php';
$__autoload['dcFilterWords']       = dirname(__FILE__) . '/filters/class.dc.filter.words.php';

$core->spamfilters = array('dcFilterIP', 'dcFilterIpLookup', 'dcFilterWords', 'dcFilterLinksLookup');

$core->url->register('spamfeed', 'spamfeed', '^spamfeed/(.+)$', array('dcAntispamURL', 'spamFeed'));
$core->url->register('hamfeed', 'hamfeed', '^hamfeed/(.+)$', array('dcAntispamURL', 'hamFeed'));

if (!defined('DC_CONTEXT_ADMIN')) {return false;}

// Admin mode

$__autoload['dcAntispamRest'] = dirname(__FILE__) . '/_services.php';

// Register REST methods
$core->rest->addFunction('getSpamsCount', array('dcAntispamRest', 'getSpamsCount'));
