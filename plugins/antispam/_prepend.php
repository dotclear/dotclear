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
Clearbricks::lib()->autoload([
    'dcSpamFilter'        => __DIR__ . '/inc/spamfilter.php',
    'dcSpamFilters'       => __DIR__ . '/inc/spamfilters.php',
    'dcAntispam'          => __DIR__ . '/inc/antispam.php',
    'dcAntispamURL'       => __DIR__ . '/inc/public.url.php',
    'antispamBehaviors'   => __DIR__ . '/inc/admin.behaviors.php',
    'dcFilterIP'          => __DIR__ . '/filters/filter.ip.php',
    'dcFilterIPv6'        => __DIR__ . '/filters/filter.ipv6.php',
    'dcFilterIpLookup'    => __DIR__ . '/filters/filter.iplookup.php',
    'dcFilterLinksLookup' => __DIR__ . '/filters/filter.linkslookup.php',
    'dcFilterWords'       => __DIR__ . '/filters/filter.words.php',
    'dcAntispamRest'      => __DIR__ . '/_services.php',
]);

dcCore::app()->spamfilters = [
    'dcFilterIP',
    'dcFilterIpLookup',
    'dcFilterWords',
    'dcFilterLinksLookup',
];

// IP v6 filter depends on some math libraries, so enable it only if one of them is available
if (function_exists('gmp_init') || function_exists('bcadd')) {
    dcCore::app()->spamfilters[] = 'dcFilterIPv6';
}

dcCore::app()->url->register('spamfeed', 'spamfeed', '^spamfeed/(.+)$', [dcAntispamURL::class, 'spamFeed']);
dcCore::app()->url->register('hamfeed', 'hamfeed', '^hamfeed/(.+)$', [dcAntispamURL::class, 'hamFeed']);

if (!defined('DC_CONTEXT_ADMIN')) {
    return false;
}

// Admin mode

Clearbricks::lib()->autoload(['dcAntispamRest' => __DIR__ . '/_services.php']);

// Register REST methods
dcCore::app()->rest->addFunction('getSpamsCount', [dcAntispamRest::class, 'getSpamsCount']);
