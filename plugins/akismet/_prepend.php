<?php
/**
 * @brief akismet, an antispam filter plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

$__autoload['dcFilterAkismet'] = dirname(__FILE__) . '/class.dc.filter.akismet.php';
$core->spamfilters[]           = 'dcFilterAkismet';
