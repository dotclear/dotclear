<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\tags;

use Dotclear\App;
use Dotclear\Core\Frontend\Url;

/**
 * @brief   The module frontend URL.
 * @ingroup tags
 */
class FrontendUrl extends Url
{
    /**
     * Output the Tag page.
     *
     * @param   null|string     $args   The arguments
     */
    public static function tag(?string $args): void
    {
        $n = self::getPageNumber($args);

        if ($args == '' && !$n) {
            self::p404();
        } elseif (preg_match('%(.*?)/feed/(rss2|atom)?$%u', (string) $args, $m)) {
            $type = ($m[2] ?? '') === 'atom' ? 'atom' : 'rss2';
            $mime = 'application/xml';

            App::frontend()->context()->meta = App::meta()->computeMetaStats(
                App::meta()->getMetadata([
                    'meta_type' => 'tag',
                    'meta_id'   => $m[1], ])
            );

            if (App::frontend()->context()->meta->isEmpty()) {
                self::p404();
            } else {
                $tpl = $type;

                if ($type === 'atom') {
                    $mime = 'application/atom+xml';
                }

                self::serveDocument($tpl . '.xml', $mime);
            }
        } else {
            if ($n) {
                App::frontend()->setPageNumber($n);
            }

            App::frontend()->context()->meta = App::meta()->computeMetaStats(
                App::meta()->getMetadata([
                    'meta_type' => 'tag',
                    'meta_id'   => $args, ])
            );

            if (App::frontend()->context()->meta->isEmpty()) {
                self::p404();
            } else {
                self::serveDocument('tag.html');
            }
        }
    }

    /**
     * Output the Tags page.
     */
    public static function tags(): void
    {
        self::serveDocument('tags.html');
    }

    /**
     * Output the Tag feed page.
     *
     * @param   null|string     $args   The arguments
     */
    public static function tagFeed(?string $args): void
    {
        if (!preg_match('#^(.+)/(atom|rss2)(/comments)?$#', (string) $args, $m)) {
            self::p404();
        } else {
            $tag      = $m[1];
            $type     = (string) $m[2];
            $comments = isset($m[3]);

            App::frontend()->context()->meta = App::meta()->computeMetaStats(
                App::meta()->getMetadata([
                    'meta_type' => 'tag',
                    'meta_id'   => $tag, ])
            );

            if (App::frontend()->context()->meta->isEmpty()) {
                # The specified tag does not exist.
                self::p404();
            } else {
                App::frontend()->context()->feed_subtitle = ' - ' . __('Tag') . ' - ' . App::frontend()->context()->meta->meta_id;

                $mime = $type === 'atom' ? 'application/atom+xml' : 'application/xml';

                $tpl = $type;
                if ($comments) {
                    $tpl .= '-comments';
                    App::frontend()->context()->nb_comment_per_page = App::blog()->settings()->system->nb_comment_per_feed;
                } else {
                    App::frontend()->context()->nb_entry_per_page = App::blog()->settings()->system->nb_post_per_feed;
                    App::frontend()->context()->short_feed_items  = App::blog()->settings()->system->short_feed_items;
                }
                $tpl .= '.xml';

                self::serveDocument($tpl, $mime);
            }
        }
    }
}
