<?php
/**
 * @file
 * @brief       The module backend helper resource
 * @ingroup     pages
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
\Dotclear\App::backend()->resources()
    ->set('help', 'pages', __DIR__ . '/help/pages.html')
    ->set('help', 'page', __DIR__ . '/help/page.html');
