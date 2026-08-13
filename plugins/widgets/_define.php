<?php

/**
 * @file
 * @brief       The plugin widgets definition
 * @ingroup     widgets
 *
 * @defgroup    widgets Plugin widgets.
 *
 * widgets, widgets for your blog sidebars.
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
if (isset($this) && is_object($this) && method_exists($this, 'registerModule') && isset($this->id) && is_string($this->id)) {
    $this->registerModule(
        'Widgets',                         // Name
        'Widgets for your blog sidebars',  // Description
        'Olivier Meunier & Dotclear Team', // Author
        '4.0',                             // Version
        [
            'permissions' => 'My',
            'priority'    => 1_000_000_000,
            'type'        => 'plugin',
        ]
    );
}
