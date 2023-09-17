<?php
/**
 * @file
 * @brief       The module backend helper resource
 * @ingroup     pings
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
\Dotclear\App::backend()->resources
    ->set('help', 'pings', __DIR__ . '/help/pings.html')
    ->set('help', 'pings_post', __DIR__ . '/help/pings_post.html');
