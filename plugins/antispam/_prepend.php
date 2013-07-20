<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Antispam, a plugin for Dotclear 2.
#
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_RC_PATH')) { return; }

global $__autoload, $core;

$__autoload['dcSpamFilter'] = dirname(__FILE__).'/inc/class.dc.spamfilter.php';
$__autoload['dcSpamFilters'] = dirname(__FILE__).'/inc/class.dc.spamfilters.php';
$__autoload['dcAntispam'] = dirname(__FILE__).'/inc/lib.dc.antispam.php';
$__autoload['dcAntispamURL'] = dirname(__FILE__).'/inc/lib.dc.antispam.url.php';

$__autoload['dcFilterIP'] = dirname(__FILE__).'/filters/class.dc.filter.ip.php';
$__autoload['dcFilterIpLookup'] = dirname(__FILE__).'/filters/class.dc.filter.iplookup.php';
$__autoload['dcFilterLinksLookup'] = dirname(__FILE__).'/filters/class.dc.filter.linkslookup.php';
$__autoload['dcFilterWords'] = dirname(__FILE__).'/filters/class.dc.filter.words.php';

$core->spamfilters = array('dcFilterIP','dcFilterIpLookup','dcFilterWords','dcFilterLinksLookup');

$core->url->register('spamfeed','spamfeed','^spamfeed/(.+)$',array('dcAntispamURL','spamFeed'));
$core->url->register('hamfeed','hamfeed','^hamfeed/(.+)$',array('dcAntispamURL','hamFeed'));
?>