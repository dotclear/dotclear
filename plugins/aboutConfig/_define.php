<?php
/**
 * @file
 * @brief       The plugin aboutConfig definition
 * @ingroup     aboutConfig
 * 
 * @defgroup    aboutConfig Plugin aboutConfig.
 * 
 * aboutConfig, Manage every blog configuration directive.
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
$this->registerModule(
    'about:config',                              // Name
    'Manage every blog configuration directive', // Description
    'Olivier Meunier',                           // Author
    '1.0',                                       // Version
    [
        'type' => 'plugin',
    ]
);
