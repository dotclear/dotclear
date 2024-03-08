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
