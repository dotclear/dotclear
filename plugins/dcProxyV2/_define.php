<?php
/**
 * @file
 * @brief       The plugin dcProxyV2 definition
 * @ingroup     dcProxyV2
 *
 * @defgroup    dcProxyV2 Plugin dcProxyV2.
 *
 * dcProxyV2, Cope with function/method footprint V1 (< 2.24, 2.25).
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
$this->registerModule(
    'dcProxyV2',
    'Cope with function/method footprint V1 (< 2.24, 2.25)',
    'Franck Paul',
    '2.0',
    [
        'permissions' => 'My', // bypass permissions
        'type'        => 'plugin',
        'priority'    => 99_999_999_998,
    ]
);
