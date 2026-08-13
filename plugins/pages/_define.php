<?php

/**
 * @file
 * @brief       The plugin pages definition
 * @ingroup     pages
 *
 * @defgroup    pages Plugin pages.
 *
 * pages, Serve entries as simple web pages.
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
if (isset($this) && is_object($this) && method_exists($this, 'registerModule') && isset($this->id) && is_string($this->id)) {
    $this->registerModule(
        'Pages',                             // Name
        'Serve entries as simple web pages', // Description
        'Olivier Meunier',                   // Author
        '2.0',                               // Version
        [
            'permissions' => 'My',
            'priority'    => 999,
            'type'        => 'plugin',
        ]
    );
}
