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
 * @copyright   GPL-2.0-only
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
