<?php

/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
\Dotclear\App::backend()->resources()
    ->set('rss_news', 'Dotclear', 'https://dotclear.org/feed/atom')
    ->set('doc', __('Dotclear documentation'), 'https://dotclear.org/category/Documentation')
    ->set('doc', __('Dotclear presentation'), 'https://dotclear.org/category/Documentation/D%C3%A9couvrir')
    ->set('doc', __('User manual'), 'https://dotclear.org/category/Documentation/Utiliser')
    ->set('doc', __('Installation and administration guides'), 'https://dotclear.org/category/Documentation/Installer-et-g%C3%A9rer')
    ->set('doc', __('Dotclear support forum'), 'https://dotclear.org/forum');
