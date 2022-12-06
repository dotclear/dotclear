<?php
/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (@is_dir('/usr/lib/clearbricks')) {
    define('CLEARBRICKS_PATH', '/usr/lib/clearbricks');
} elseif (is_dir(__DIR__ . '/libs/clearbricks')) {
    define('CLEARBRICKS_PATH', __DIR__ . '/libs/clearbricks');
} elseif (isset($_SERVER['CLEARBRICKS_PATH']) && is_dir($_SERVER['CLEARBRICKS_PATH'])) {
    define('CLEARBRICKS_PATH', $_SERVER['CLEARBRICKS_PATH']);
}

if (!defined('CLEARBRICKS_PATH') || !is_dir(CLEARBRICKS_PATH)) {
    exit('No clearbricks path defined');
}

require CLEARBRICKS_PATH . '/_common.php';

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

if (empty($_GET['pf'])) {
    header('Content-Type: text/plain');
    http::head(404, 'Not Found');
    exit;
}

// $_GET['v'] : version in url to bypass cache in case of dotclear upgrade or in dev mode
// but don't care of value
if (isset($_GET['v'])) {
    unset($_GET['v']);
}

// $_GET['t'] : parameter given by CKEditor, but don't care of value
if (isset($_GET['t'])) {
    unset($_GET['t']);
}

// Only $_GET['pf'] is allowed in URL
if (count($_GET) > 1) {
    header('Content-Type: text/plain');
    http::head(403, 'Forbidden');
    exit;
}

$requested_file = path::clean($_GET['pf']);

$paths = array_reverse(explode(PATH_SEPARATOR, DC_PLUGINS_ROOT));

# Adding some folders here to load some stuff
$paths[] = __DIR__ . '/js';
$paths[] = __DIR__ . '/css';
$paths[] = __DIR__ . '/smilies';

foreach ($paths as $m) {
    $plugin_file = path::real($m . '/' . $requested_file);

    if ($plugin_file !== false) {
        break;
    }
}
unset($paths, $requested_file);

if ($plugin_file === false || !is_file($plugin_file) || !is_readable($plugin_file)) {
    unset($plugin_file);
    header('Content-Type: text/plain');
    http::head(404, 'Not Found');
    exit;
}

$extension = files::getExtension($plugin_file);
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
    unset($plugin_file);
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
        $base_file = substr($plugin_file, 0, strlen($plugin_file) - strlen($extension) - 1);
        if (files::getExtension($base_file) !== 'min') {
            $minified_file = $base_file . '.min.' . $extension;
            if (is_file($minified_file) && is_readable($minified_file)) {
                $plugin_file = $minified_file;
            }
        }
    }
}

http::$cache_max_age = 7 * 24 * 60 * 60; // One week cache for plugin's files served by ?pf=…
http::cache(array_merge([$plugin_file], get_included_files()));

header('Content-Type: ' . files::getMimeType($plugin_file));
readfile($plugin_file);
unset($plugin_file);
exit;
