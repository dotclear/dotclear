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

if (!isset($_SERVER['PATH_INFO'])) {
    $_SERVER['PATH_INFO'] = '';
}

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'prepend.php';

// New public instance
dcCore::app()->public = new dcPublic();
dcCore::app()->public->init();
