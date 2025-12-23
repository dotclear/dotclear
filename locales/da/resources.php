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
    ->set('doc', 'Dotclear documentation', 'https://dotclear.org/category/Documentation')
    ->set('doc', 'Dotclear presentation', 'https://dotclear.org/category/Documentation/D%C3%A9couvrir')
    ->set('doc', 'User manual', 'https://dotclear.org/category/Documentation/Utiliser')
    ->set('doc', 'Installation and administration guides', 'https://dotclear.org/category/Documentation/Installer-et-g%C3%A9rer')
    ->set('doc', 'Dotclear support forum', 'https://dotclear.org/forum');
