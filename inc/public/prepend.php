<?php
/**
 * @package Dotclear
 * @subpackage Public
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
define('DC_CONTEXT_PUBLIC', true);
define('DC_PUBLIC_CONTEXT', true); // For dyslexic devs ;-)

if (!empty($_GET['pf'])) {
    // A plugin file (resource) is requested
    require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'load_plugin_file.php';
    exit;
}

if (!empty($_GET['vf'])) {
    // A var file (resource) is requested
    require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'load_var_file.php';
    exit;
}

if (!isset($_SERVER['PATH_INFO'])) {
    $_SERVER['PATH_INFO'] = '';
}

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'prepend.php';

// New public instance
dcCore::app()->public = new dcPublic();
dcCore::app()->public->init();
