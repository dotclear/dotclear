<?php

/**
 * @file
 * @brief       The plugin userPref definition
 * @ingroup     userPref
 *
 * @defgroup    userPref Plugin userPref.
 *
 * userPref, manage every user preference directive.
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
if (isset($this) && is_object($this) && method_exists($this, 'registerModule') && isset($this->id) && is_string($this->id)) {
    $this->registerModule(
        'user:preferences',                       // Name
        'Manage every user preference directive', // Description
        'Franck Paul',                            // Author
        '1.0',                                    // Version
        [
            'type' => 'plugin',
        ]
    );
}
