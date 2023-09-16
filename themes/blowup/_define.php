<?php
/**
 * @file
 * @brief       The theme blowup definition
 * @ingroup     blowup
 * 
 * @defgroup    blowup Theme blowup.
 * 
 * blowup, a fully customizable theme for Dotclear 2.
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
$this->registerModule(
    'Blowup',                                     // Name
    'Default Dotclear theme, fully customizable', // Description
    'Marco & Olivier',                            // Author
    '2.0',                                        // Version
    [
        'standalone_config' => true,
        'type'              => 'theme',
    ]
);
