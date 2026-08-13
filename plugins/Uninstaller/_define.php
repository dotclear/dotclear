<?php

/**
 * @file
 * @brief       The plugin Uninstaller definition
 * @ingroup     Uninstaller
 *
 * @defgroup    Uninstaller Plugin Uninstaller.
 *
 * Uninstaller, uninstall cleanly plugins and themes.
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
if (isset($this) && is_object($this) && method_exists($this, 'registerModule') && isset($this->id) && is_string($this->id)) {
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
}
