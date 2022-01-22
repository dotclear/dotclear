<?php
/**
 * @brief buildtools, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

$this->registerModule(
    'buildtools',                             // Name
    'Internal build tools for dotclear team', // Description
    'dcTeam',                                 // Author
    '1.0',                                    // Version
    [
        'permissions' => 'admin',
    ]
);
