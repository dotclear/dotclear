<?php
/**
 * @file
 * @brief       The plugin fairTrackbacks definition
 * @ingroup     fairTrackbacks
 *
 * @defgroup    fairTrackbacks Plugin fairTrackbacks.
 *
 * fairTrackbacks, Trackback validity check.
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
$this->registerModule(
    'Fair Trackbacks',          // Name
    'Trackback validity check', // Description
    'Olivier Meunier',          // Author
    '2.0',                    // Version
    [
        'permissions' => 'My',
        'priority'    => 200,
        'type'        => 'plugin',
    ]
);
