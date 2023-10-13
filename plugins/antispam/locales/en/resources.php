<?php
/**
 * @file
 * @brief       The module backend helper resource
 * @ingroup     antispam
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
\Dotclear\App::backend()->resources()
    ->set('help', 'antispam', __DIR__ . '/help/help.html')
    ->set('help', 'antispam-filters', __DIR__ . '/help/help.html')
    ->set('help', 'ip-filter', __DIR__ . '/help/filters.html')
    ->set('help', 'iplookup-filter', __DIR__ . '/help/iplookup.html')
    ->set('help', 'words-filter', __DIR__ . '/help/words.html')
    ->set('help', 'antispam_comments', __DIR__ . '/help/comments.html');
