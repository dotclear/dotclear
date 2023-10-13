<?php
/**
 * @file
 * @brief       The module backend helper resource
 * @ingroup     tags
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
\Dotclear\App::backend()->resources()
    ->set('help', 'tags', __DIR__ . '/help/tags.html')
    ->set('help', 'tag_posts', __DIR__ . '/help/tag_posts.html')
    ->set('help', 'tag_post', __DIR__ . '/help/tag_post.html');
