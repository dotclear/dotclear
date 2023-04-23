<?php
/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

use Dotclear\App;
use Dotclear\Helper\Clearbricks;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Network\Http;

// Prepare namespaced src
// ----------------------

// 1. Load Application boostrap file
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'App.php']);

// 2. Instanciante the Application (singleton)
new App();

// 3. Add root folder for namespaced and autoloaded classes and do some init
App::autoload()->addNamespace('Dotclear', implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src']));
App::init();

// 4. Force CB bootstrap
new Clearbricks();

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

// path::real() may be used in inc/config.php
if (!class_exists('path')) {
    class_alias('Dotclear\Helper\File\Path', 'path');
}

require DC_RC_PATH;

if (empty($_GET['vf'])) {
    header('Content-Type: text/plain');
    Http::head(404, 'Not Found');
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
    Http::head(403, 'Forbidden');
    exit;
}

if (!defined('DC_VAR')) {
    define('DC_VAR', Path::real(__DIR__ . '/..') . '/var');
}

$var_file = Path::real(DC_VAR . '/' . Path::clean($_GET['vf']));

if ($var_file === false || !is_file($var_file) || !is_readable($var_file)) {
    unset($var_file);
    header('Content-Type: text/plain');
    Http::head(404, 'Not Found');
    exit;
}

$extension = Files::getExtension($var_file);
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
        'mjs',
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
    Http::head(404, 'Not Found');
    exit;
}

// For JS and CSS, look if a minified version exists
if ((!defined('DC_DEV') || !DC_DEV) && (!defined('DC_DEBUG') || !DC_DEBUG)) {
    if (in_array(
        $extension,
        [
            'css',
            'js',
            'mjs',
        ]
    )) {
        $base_file = substr($var_file, 0, strlen($var_file) - strlen($extension) - 1);
        if (Files::getExtension($base_file) !== 'min') {
            $minified_file = $base_file . '.min.' . $extension;
            if (is_file($minified_file) && is_readable($minified_file)) {
                $var_file = $minified_file;
            }
        }
    }
}

Http::$cache_max_age = 7 * 24 * 60 * 60; // One week cache for var files served by ?vf=…
Http::cache([...[$var_file], ...get_included_files()]);

header('Content-Type: ' . Files::getMimeType($var_file));
readfile($var_file);
unset($var_file);
exit;
