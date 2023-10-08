<?php
/**
 * @file
 * @brief       The plugin importExport definition
 * @ingroup     importExport
 * 
 * @defgroup    importExport Plugin importExport.
 * 
 * importExport, Import and Export your blog.
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
$this->registerModule(
    'Import / Export',                // Name
    'Import and Export your blog',    // Description
    'Olivier Meunier & Contributors', // Author
    '4.0',                            // Version
    [
        'permissions' => 'My',
        'type'        => 'plugin',
    ]
);
