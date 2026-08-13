<?php

/**
 * @file
 * @brief       The plugin buildtools definition
 * @ingroup     buildtools
 *
 * @defgroup    buildtools Plugin buildtools.
 *
 * buildtools, Internal build tools for dotclear team.
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
if (isset($this) && is_object($this) && method_exists($this, 'registerModule') && isset($this->id) && is_string($this->id)) {
    $this->registerModule(
        'buildtools',                             // Name
        'Internal build tools for dotclear team', // Description
        'dcTeam',                                 // Author
        '2.0',                                    // Version
        [
            'type'        => 'plugin',
            'permissions' => 'My',
        ]
    );
}
