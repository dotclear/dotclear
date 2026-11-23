<?php

/**
 * @file
 * @brief       The plugin DotclearWatch definition
 * @ingroup     DotclearWatch
 *
 * @defgroup    DotclearWatch Plugin DotclearWatch.
 *
 * Send report about your Dotclear.
 *
 * @author      Jean-Christian Denis
 *
 * @copyright   Jean-Christian Denis
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

if (isset($this) && is_object($this) && method_exists($this, 'registerModule') && isset($this->id) && is_string($this->id)) {
    $this->registerModule(
        'Dotclear Watch',
        'Send report about your Dotclear',
        'Jean-Christian Denis and contributors',
        '1.10.1',
        [
            'requires'    => [['core', '2.39']],
            'permissions' => 'My',
            'type'        => 'plugin',
            'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
            'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
            'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
            'date'        => '2026-11-22T22:55:40+00:00',
        ]
    );
}
