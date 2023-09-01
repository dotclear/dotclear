<?php
/**
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
\Dotclear\App::backend()->resources
    ->set('help', 'pings', __DIR__ . '/help/pings.html')
    ->set('help', 'pings_post', __DIR__ . '/help/pings_post.html');
