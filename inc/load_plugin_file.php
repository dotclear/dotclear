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

// 3. Add root folder for namespaced and autoloaded classes
App::autoload()->addNamespace('Dotclear', implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src']));

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

if (empty($_GET['pf'])) {
    header('Content-Type: text/plain');
    Http::head(404, 'Not Found');
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
    Http::head(403, 'Forbidden');
    exit;
}

$requested_file = Path::clean($_GET['pf']);

$paths = array_reverse(explode(PATH_SEPARATOR, DC_PLUGINS_ROOT));

# Adding some folders here to load some stuff
$paths[] = __DIR__ . '/js';
$paths[] = __DIR__ . '/css';
$paths[] = __DIR__ . '/smilies';

foreach ($paths as $m) {
    $plugin_file = Path::real($m . '/' . $requested_file);

    if ($plugin_file !== false) {
        break;
    }
}
unset($paths, $requested_file);

if ($plugin_file === false || !is_file($plugin_file) || !is_readable($plugin_file)) {
    unset($plugin_file);
    header('Content-Type: text/plain');
    Http::head(404, 'Not Found');
    exit;
}

$extension = Files::getExtension($plugin_file);
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
    unset($plugin_file);
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
        $base_file = substr($plugin_file, 0, strlen($plugin_file) - strlen($extension) - 1);
        if (Files::getExtension($base_file) !== 'min') {
            $minified_file = $base_file . '.min.' . $extension;
            if (is_file($minified_file) && is_readable($minified_file)) {
                $plugin_file = $minified_file;
            }
        }
    }
}

Http::$cache_max_age = 7 * 24 * 60 * 60; // One week cache for plugin's files served by ?pf=…
Http::cache([...[$plugin_file], ...get_included_files()]);

header('Content-Type: ' . Files::getMimeType($plugin_file));
readfile($plugin_file);
unset($plugin_file);
exit;
