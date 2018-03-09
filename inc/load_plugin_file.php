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
} elseif (is_dir(dirname(__FILE__) . '/libs/clearbricks')) {
    define('CLEARBRICKS_PATH', dirname(__FILE__) . '/libs/clearbricks');
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
    define('DC_RC_PATH', dirname(__FILE__) . '/config.php');
}

if (!is_file(DC_RC_PATH)) {
    trigger_error('Unable to open config file', E_USER_ERROR);
    exit;
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

// Only $_GET['pf'] is allowed in URL
if (count($_GET) > 1) {
    header('Content-Type: text/plain');
    http::head(403, 'Forbidden');
    exit;
}

$allow_types = array('png', 'jpg', 'jpeg', 'gif', 'css', 'js', 'swf', 'svg');

$pf = path::clean($_GET['pf']);

$paths = array_reverse(explode(PATH_SEPARATOR, DC_PLUGINS_ROOT));

# Adding some folders here to load some stuff
$paths[] = dirname(__FILE__) . '/swf';
$paths[] = dirname(__FILE__) . '/js';
$paths[] = dirname(__FILE__) . '/css';

foreach ($paths as $m) {
    $PF = path::real($m . '/' . $pf);

    if ($PF !== false) {
        break;
    }
}
unset($paths);

if ($PF === false || !is_file($PF) || !is_readable($PF)) {
    header('Content-Type: text/plain');
    http::head(404, 'Not Found');
    exit;
}

if (!in_array(files::getExtension($PF), $allow_types)) {
    header('Content-Type: text/plain');
    http::head(404, 'Not Found');
    exit;
}

http::$cache_max_age = 7 * 24 * 60 * 60; // One week cache for plugin's files served by ?pf=… is better than old 2 hours
http::cache(array_merge(array($PF), get_included_files()));

header('Content-Type: ' . files::getMimeType($PF));
// Content-length is not mandatory and must be the exact size of content transfered AFTER possible compression (gzip, deflate, …)
//header('Content-Length: '.filesize($PF));
readfile($PF);
exit;
