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
 * @copyright   AGPL-3.0
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
