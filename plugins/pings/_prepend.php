<?php
/**
 * @brief pings, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

$__autoload['pingsAPI']           = dirname(__FILE__) . '/lib.pings.php';
$__autoload['pingsCoreBehaviour'] = dirname(__FILE__) . '/lib.pings.php';

$core->addBehavior('coreFirstPublicationEntries', array('pingsCoreBehaviour', 'doPings'));
