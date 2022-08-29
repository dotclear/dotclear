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
if (!defined('DC_RC_PATH')) {
    return;
}

Clearbricks::lib()->autoload(['dcFilterAkismet' => __DIR__ . '/class.dc.filter.akismet.php']);
dcCore::app()->spamfilters[] = 'dcFilterAkismet';
