<?php
/**
 * @brief dcProxyV2, a plugin for Dotclear 2
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
    'dcProxyV2',
    'Cope with function/method footprint V1 (< 2.24)',
    'Franck Paul',
    '1.0',
    [
        'type'     => 'plugin',
        'priority' => 99_999_999_998,
    ]
);
