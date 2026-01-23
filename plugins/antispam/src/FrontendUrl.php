<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\antispam;

use Dotclear\App;
use Dotclear\Core\Url;
use Dotclear\Helper\Html\Html;

/**
 * @brief   The module frontend URL handler.
 * @ingroup antispam
 */
class FrontendUrl extends Url
{
    /**
     * Generate a ham feed.
     *
     * @param   string   $code   Code
     */
    public static function hamFeed(string $code): void
    {
        self::genFeed('ham', $code);
    }

    /**
     * Generate a spam feed.
     *
     * @param   string   $code   Code
     */
    public static function spamFeed(string $code): void
    {
        self::genFeed('spam', $code);
    }

    /**
     * Generate an antispam feed (ham/spam).
     *
     * @param   string  $type   The type
     * @param   string  $code   The arguments
     */
    private static function genFeed(string $type, string $code): void
    {
        $user_id = Antispam::checkUserCode($code);

        if ($user_id === false) {
            self::p404();
        }

        App::auth()->checkUser($user_id);

        header('Content-Type: application/xml; charset=UTF-8');

        $title     = App::blog()->name() . ' - ' . __('Spam moderation') . ' - ';
        $end_url   = $type === 'spam' ? '&status=' . App::status()->comment()::JUNK : '';
        $admin_url = App::config()->adminUrl() !== '' ? App::config()->adminUrl() . 'index.php?process=Comments' : '';
        $params    = [
            'limit' => 20,  // Last 20 comments/spams
        ];

        if ($type === 'spam') {
            $title .= __('Spam');
            $params['comment_status'] = App::status()->comment()::JUNK;
        } else {
            $title .= __('Ham');
            $params['sql'] = ' AND comment_status IN (' . App::status()->comment()::PUBLISHED . ',' . App::status()->comment()::PENDING . ') ';
        }

        $link_uri = $admin_url !== '' ? $admin_url . $end_url : 'about:blank';

        echo
        '<?xml version="1.0" encoding="utf-8"?>' . "\n" .
        '<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:content="http://purl.org/rss/1.0/modules/content/">' . "\n" .
        '<channel>' . "\n" .
        '  <title>' . Html::escapeHTML($title) . '</title>' . "\n" .
        '  <link>' . $link_uri . '</link>' . "\n" .
        '  <description></description>' . "\n";

        // We will get all comments in the RSS feed
        App::frontend()->context()->preview = true;

        $rs = App::blog()->getComments($params);

        $sanitizeToString = fn (mixed $value): string => is_string($value) ? trim($value) : '';
        $sanitizeToInt    = fn (mixed $value): int => is_numeric($value) ? (int) $value : 0;

        $admin_url = App::config()->adminUrl() !== '' ? App::config()->adminUrl() . 'index.php?process=Comment&id=' : '';

        while ($rs->fetch()) {
            $comment_id = $sanitizeToInt($rs->comment_id);
            $uri        = $admin_url !== '' ? $admin_url . $comment_id : 'about:blank';
            $author     = $sanitizeToString($rs->comment_author);
            $title      = $sanitizeToString($rs->post_title);
            $title      = $title . ' - ' . $author;
            if ($type === 'spam') {
                $title .= ' (' . $sanitizeToString($rs->comment_spam_filter) . ')';
            }
            $id = $sanitizeToString($rs->getFeedID());

            $content      = '';
            $comment_site = $sanitizeToString($rs->comment_site);
            if ($comment_site !== '') {
                $content .= '<p>URL: <a href="' . $comment_site . '">' . $comment_site . '</a></p><hr>' . "\n";
            }
            $content .= $sanitizeToString($rs->comment_content);
            $date = $sanitizeToString($rs->getRFC822Date());

            echo
            '  <item>' . "\n" .
            '    <title>' . Html::escapeHTML($title) . '</title>' . "\n" .
            '    <link>' . $uri . '</link>' . "\n" .
            '    <guid>' . $id . '</guid>' . "\n" .
            '    <pubDate>' . $date . '</pubDate>' . "\n" .
            '    <dc:creator>' . Html::escapeHTML($author) . '</dc:creator>' . "\n" .
            '    <description>' . Html::escapeHTML($content) . '</description>' . "\n" .
            '  </item>' . "\n";
        }

        echo "</channel>\n</rss>\n";
    }
}
