<?php
/**
 * @brief tags, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\tags;

use dcCore;
use Dotclear\Core\Frontend\Url;

class FrontendUrl extends Url
{
    /**
     * Output the Tag page
     *
     * @param      null|string  $args   The arguments
     */
    public static function tag(?string $args): void
    {
        $n = self::getPageNumber($args);

        if ($args == '' && !$n) {
            self::p404();
        } elseif (preg_match('%(.*?)/feed/(rss2|atom)?$%u', (string) $args, $m)) {
            $type = $m[2] == 'atom' ? 'atom' : 'rss2';
            $mime = 'application/xml';

            dcCore::app()->ctx->meta = dcCore::app()->meta->computeMetaStats(
                dcCore::app()->meta->getMetadata([
                    'meta_type' => 'tag',
                    'meta_id'   => $m[1], ])
            );

            if (dcCore::app()->ctx->meta->isEmpty()) {
                self::p404();
            } else {
                $tpl = $type;

                if ($type == 'atom') {
                    $mime = 'application/atom+xml';
                }

                self::serveDocument($tpl . '.xml', $mime);
            }
        } else {
            if ($n) {
                dcCore::app()->public->setPageNumber($n);
            }

            dcCore::app()->ctx->meta = dcCore::app()->meta->computeMetaStats(
                dcCore::app()->meta->getMetadata([
                    'meta_type' => 'tag',
                    'meta_id'   => $args, ])
            );

            if (dcCore::app()->ctx->meta->isEmpty()) {
                self::p404();
            } else {
                self::serveDocument('tag.html');
            }
        }
    }

    /**
     * Output the Tags page
     */
    public static function tags(): void
    {
        self::serveDocument('tags.html');
    }

    /**
     * Output the Tag feed page
     *
     * @param      null|string  $args   The arguments
     */
    public static function tagFeed(?string $args): void
    {
        if (!preg_match('#^(.+)/(atom|rss2)(/comments)?$#', (string) $args, $m)) {
            self::p404();
        } else {
            $tag      = (string) $m[1];
            $type     = (string) $m[2];
            $comments = !empty($m[3]);

            dcCore::app()->ctx->meta = dcCore::app()->meta->computeMetaStats(
                dcCore::app()->meta->getMetadata([
                    'meta_type' => 'tag',
                    'meta_id'   => $tag, ])
            );

            if (dcCore::app()->ctx->meta->isEmpty()) {
                # The specified tag does not exist.
                self::p404();
            } else {
                dcCore::app()->ctx->feed_subtitle = ' - ' . __('Tag') . ' - ' . dcCore::app()->ctx->meta->meta_id;

                if ($type === 'atom') {
                    $mime = 'application/atom+xml';
                } else {
                    $mime = 'application/xml';
                }

                $tpl = $type;
                if ($comments) {
                    $tpl .= '-comments';
                    dcCore::app()->ctx->nb_comment_per_page = dcCore::app()->blog->settings->system->nb_comment_per_feed;
                } else {
                    dcCore::app()->ctx->nb_entry_per_page = dcCore::app()->blog->settings->system->nb_post_per_feed;
                    dcCore::app()->ctx->short_feed_items  = dcCore::app()->blog->settings->system->short_feed_items;
                }
                $tpl .= '.xml';

                self::serveDocument($tpl, $mime);
            }
        }
    }
}
