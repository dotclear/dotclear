<?php

/**
 * @file
 * @brief       The plugin akismet definition
 * @ingroup     akismet
 *
 * @defgroup    akismet Plugin akismet.
 *
 * akismet, aksimet antispam filter plugin for Dotclear 2
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

if (isset($this) && is_object($this) && method_exists($this, 'registerModule') && isset($this->id) && is_string($this->id)) {
    $this->registerModule(
        'Akismet',                        // Name
        'Akismet interface for Dotclear', // Description
        'Olivier Meunier',                // Author
        '2.0',                            // Version
        [
            'permissions' => 'My',
            'priority'    => 200,
            'type'        => 'plugin',
        ]
    );
}
