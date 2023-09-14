<?php
/**
 * @brief Uninstaller, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
$this->registerModule(
    'Uninstaller',
    'Uninstall cleanly plugins and themes',
    'Jean-Christian Denis and Contributors',
    '1.0',
    [
        'permissions' => null,
        'type'        => 'plugin',
        'settings'    => [
            'self' => false,
        ],
    ]
);
