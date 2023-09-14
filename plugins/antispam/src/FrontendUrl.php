<?php
/**
 * @brief antispam, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\antispam;

use Dotclear\App;
use Dotclear\Core\Frontend\Url;
use Dotclear\Helper\Html\Html;

class FrontendUrl extends Url
{
    /**
     * Generate a ham feed
     *
     * @param      mixed  $args   The arguments
     */
    public static function hamFeed($args): void
    {
        self::genFeed('ham', (string) $args);
    }

    /**
     * Generate a spam feed
     *
     * @param      mixed  $args   The arguments
     */
    public static function spamFeed($args): void
    {
        self::genFeed('spam', (string) $args);
    }

    /**
     * Generate an antispam feed (ham/spam)
     *
     * @param      string  $type   The type
     * @param      string  $args   The arguments
     */
    private static function genFeed(string $type, string $args)
    {
        $user_id = Antispam::checkUserCode($args);

        if ($user_id === false) {
            self::p404();
        }

        App::auth()->checkUser($user_id, null, null);

        header('Content-Type: application/xml; charset=UTF-8');

        $title   = App::blog()->name() . ' - ' . __('Spam moderation') . ' - ';
        $params  = [];
        $end_url = '';
        if ($type == 'spam') {
            $title .= __('Spam');
            $params['comment_status'] = App::blog()::COMMENT_JUNK;
            $end_url                  = '&status=' . (string) App::blog()::COMMENT_PUBLISHED;
        } else {
            $title .= __('Ham');
            $params['sql'] = ' AND comment_status IN (' . (string) App::blog()::COMMENT_PUBLISHED . ',' . (string) App::blog()::COMMENT_PENDING . ') ';
        }

        echo
        '<?xml version="1.0" encoding="utf-8"?>' . "\n" .
        '<rss version="2.0"' . "\n" .
        'xmlns:dc="http://purl.org/dc/elements/1.1/"' . "\n" .
        'xmlns:content="http://purl.org/rss/1.0/modules/content/">' . "\n" .
        '<channel>' . "\n" .
        '<title>' . Html::escapeHTML($title) . '</title>' . "\n" .
        '<link>' . (App::config()->adminUrl() ? App::config()->adminUrl() . 'index.php?process=Comments' . $end_url : 'about:blank') . '</link>' . "\n" .
        '<description></description>' . "\n";

        $rs       = App::blog()->getComments($params);
        $maxitems = 20;
        $nbitems  = 0;

        while ($rs->fetch() && ($nbitems < $maxitems)) {
            $nbitems++;
            $uri    = App::config()->adminUrl() ? App::config()->adminUrl() . 'index.php?process=Comment&id=' . $rs->comment_id : 'about:blank';
            $author = $rs->comment_author;
            $title  = $rs->post_title . ' - ' . $author;
            if ($type == 'spam') {
                $title .= '(' . $rs->comment_spam_filter . ')';
            }
            $id = $rs->getFeedID();

            $content = '';
            if (trim((string) $rs->comment_site)) {
                $content .= '<p>URL: <a href="' . $rs->comment_site . '">' . $rs->comment_site . '</a></p><hr />' . "\n";
            }
            $content .= $rs->comment_content;

            echo
            '<item>' . "\n" .
            '  <title>' . Html::escapeHTML($title) . '</title>' . "\n" .
            '  <link>' . $uri . '</link>' . "\n" .
            '  <guid>' . $id . '</guid>' . "\n" .
            '  <pubDate>' . $rs->getRFC822Date() . '</pubDate>' . "\n" .
            '  <dc:creator>' . Html::escapeHTML($author) . '</dc:creator>' . "\n" .
            '  <description>' . Html::escapeHTML($content) . '</description>' . "\n" .
                '</item>';
        }

        echo "</channel>\n</rss>";
    }
}
