<?php
/**
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
\Dotclear\App::backend()->resources
    ->set('help', 'pages', __DIR__ . '/help/pages.html')
    ->set('help', 'page', __DIR__ . '/help/page.html');
