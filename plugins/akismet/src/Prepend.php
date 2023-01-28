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
Clearbricks::lib()->autoload([
    'dcFilterAkismet' => __DIR__ . '/filters/filter.akismet.php',
]);

dcCore::app()->spamfilters[] = 'dcFilterAkismet';
