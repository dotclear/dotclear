<?php

/**
 * @file
 * @brief       The theme berlin definition
 * @ingroup     berlin
 *
 * @defgroup    berlin Theme berlin.
 *
 * berlin, the default theme for Dotclear 2
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
if (isset($this) && is_object($this) && method_exists($this, 'registerModule') && isset($this->id) && is_string($this->id)) {
    $this->registerModule(
        'Berlin',                      // Name
        'Dotclear 2.7+ default theme', // Description
        'Dotclear Team',               // Author
        '2.0',                         // Version
        [                              // Properties
            'type'   => 'theme',
            'tplset' => 'dotty',
        ]
    );
}
