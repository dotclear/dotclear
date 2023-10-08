<?php
/**
 * @file
 * @brief       The plugin dcProxyV1 definition
 * @ingroup     dcProxyV1
 * 
 * @defgroup    dcProxyV1 Plugin dcProxyV1.
 * 
 * dcProxyV1, Cope with old core classes (< 2.26).
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
$this->registerModule(
    'dcProxyV1',
    'Cope with old core classes (< 2.26)',
    'Franck Paul',
    '1.0',
    [
        'permissions' => 'My', // bypass permissions
        'type'        => 'plugin',
        'priority'    => 0,    // No plugins/themes loaded before this one
    ]
);
