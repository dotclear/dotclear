<?php
/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
/* ------------------------------------------------------------------------------------------- */
#  ClearBricks, DotClear classes auto-loader
if (@is_dir(implode(DIRECTORY_SEPARATOR, ['usr', 'lib', 'clearbricks']))) {
    define('CLEARBRICKS_PATH', implode(DIRECTORY_SEPARATOR, ['usr', 'lib', 'clearbricks']));
} elseif (is_dir(implode(DIRECTORY_SEPARATOR, [__DIR__, 'helper']))) {
    define('CLEARBRICKS_PATH', implode(DIRECTORY_SEPARATOR, [__DIR__, 'helper']));
} elseif (isset($_SERVER['CLEARBRICKS_PATH']) && is_dir($_SERVER['CLEARBRICKS_PATH'])) {
    define('CLEARBRICKS_PATH', $_SERVER['CLEARBRICKS_PATH']);
}

if (!defined('CLEARBRICKS_PATH') || !is_dir(CLEARBRICKS_PATH)) {
    exit('No clearbricks path defined');
}

require implode(DIRECTORY_SEPARATOR, [CLEARBRICKS_PATH, '_common.php']);

if (isset($_SERVER['DC_RC_PATH'])) {
    define('DC_RC_PATH', $_SERVER['DC_RC_PATH']);
} elseif (isset($_SERVER['REDIRECT_DC_RC_PATH'])) {
    define('DC_RC_PATH', $_SERVER['REDIRECT_DC_RC_PATH']);
} else {
    define('DC_RC_PATH', __DIR__ . '/config.php');
}

if (!is_file(DC_RC_PATH)) {
    trigger_error('Unable to open config file', E_USER_ERROR);
}

require DC_RC_PATH;

if (empty($_GET['vf'])) {
    header('Content-Type: text/plain');
    http::head(404, 'Not Found');
    exit;
}

// $_GET['v'] : version in url to bypass cache in case of dotclear upgrade or in dev mode
// but don't care of value
if (isset($_GET['v'])) {
    unset($_GET['v']);
}

// Only $_GET['vf'] is allowed in URL
if (count($_GET) > 1) {
    header('Content-Type: text/plain');
    http::head(403, 'Forbidden');
    exit;
}

if (!defined('DC_VAR')) {
    define('DC_VAR', path::real(__DIR__ . '/..') . '/var');
}

$var_file = path::real(DC_VAR . '/' . path::clean($_GET['vf']));

if ($var_file === false || !is_file($var_file) || !is_readable($var_file)) {
    unset($var_file);
    header('Content-Type: text/plain');
    http::head(404, 'Not Found');
    exit;
}

$extension = files::getExtension($var_file);
if (!in_array(
    $extension,
    [
        'css',
        'eot',
        'gif',
        'html',
        'jpeg',
        'jpg',
        'js',
        'json',
        'otf',
        'png',
        'svg',
        'swf',
        'ttf',
        'txt',
        'webp',
        'woff',
        'woff2',
        'xml',
    ]
)) {
    unset($var_file);
    header('Content-Type: text/plain');
    http::head(404, 'Not Found');
    exit;
}

// For JS and CSS, look if a minified version exists
if ((!defined('DC_DEV') || !DC_DEV) && (!defined('DC_DEBUG') || !DC_DEBUG)) {
    if (in_array(
        $extension,
        [
            'css',
            'js',
        ]
    )) {
        $base_file = substr($var_file, 0, strlen($var_file) - strlen($extension) - 1);
        if (files::getExtension($base_file) !== 'min') {
            $minified_file = $base_file . '.min.' . $extension;
            if (is_file($minified_file) && is_readable($minified_file)) {
                $var_file = $minified_file;
            }
        }
    }
}

http::$cache_max_age = 7 * 24 * 60 * 60; // One week cache for var files served by ?vf=…
http::cache(array_merge([$var_file], get_included_files()));

header('Content-Type: ' . files::getMimeType($var_file));
readfile($var_file);
unset($var_file);
exit;
