<?php
/**
 * @brief dclegacy, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

$this->registerModule(
    'dcLegacy',
    'Legacy modules for dotclear',
    'dc Team',
    '1.0',
    [
        'priority' => 500,
        'type'     => 'plugin',
    ]
);
