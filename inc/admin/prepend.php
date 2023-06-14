<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
define('DC_CONTEXT_ADMIN', true);
define('DC_ADMIN_CONTEXT', true); // For dyslexic devs ;-)

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'prepend.php';

// New admin instance
dcCore::app()->admin = new dcAdmin();
dcCore::app()->admin->init();
