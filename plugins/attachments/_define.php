<?php
/**
 * @file
 * @brief       The plugin attachments definition
 * @ingroup     attachments
 *
 * @defgroup    attachments Plugin attachments.
 *
 * attachments, manage post attachments plugin for Dotclear.
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
$this->registerModule(
    'attachments',             // Name
    'Manage post attachments', // Description
    'Dotclear Team',           // Author
    '2.0',                     // Version
    [
        'requires'    => [['pages']],
        'permissions' => 'My',
        'priority'    => 999,
        'type'        => 'plugin',
    ]
);
