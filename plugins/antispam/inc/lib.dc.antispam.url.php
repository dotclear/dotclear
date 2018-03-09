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

if (!defined('DC_RC_PATH')) {return;}

class dcAntispamURL extends dcUrlHandlers
{
    public static function hamFeed($args)
    {
        self::genFeed('ham', $args);
    }

    public static function spamFeed($args)
    {
        self::genFeed('spam', $args);
    }

    private static function genFeed($type, $args)
    {
        global $core;
        $user_id = dcAntispam::checkUserCode($core, $args);

        if ($user_id === false) {
            self::p404();
            return;
        }

        $core->auth->checkUser($user_id, null, null);

        header('Content-Type: application/xml; charset=UTF-8');

        $title   = $core->blog->name . ' - ' . __('Spam moderation') . ' - ';
        $params  = array();
        $end_url = '';
        if ($type == 'spam') {
            $title .= __('Spam');
            $params['comment_status'] = -2;
            $end_url                  = '?status=-2';
        } else {
            $title .= __('Ham');
            $params['sql'] = ' AND comment_status IN (1,-1) ';
        }

        echo
        '<?xml version="1.0" encoding="utf-8"?>' . "\n" .
        '<rss version="2.0"' . "\n" .
        'xmlns:dc="http://purl.org/dc/elements/1.1/"' . "\n" .
        'xmlns:content="http://purl.org/rss/1.0/modules/content/">' . "\n" .
        '<channel>' . "\n" .
        '<title>' . html::escapeHTML($title) . '</title>' . "\n" .
            '<link>' . (DC_ADMIN_URL ? DC_ADMIN_URL . 'comments.php' . $end_url : 'about:blank') . '</link>' . "\n" .
            '<description></description>' . "\n";

        $rs       = $core->blog->getComments($params);
        $maxitems = 20;
        $nbitems  = 0;

        while ($rs->fetch() && ($nbitems < $maxitems)) {
            $nbitems++;
            $uri    = DC_ADMIN_URL ? DC_ADMIN_URL . 'comment.php?id=' . $rs->comment_id : 'about:blank';
            $author = $rs->comment_author;
            $title  = $rs->post_title . ' - ' . $author;
            if ($type == 'spam') {
                $title .= '(' . $rs->comment_spam_filter . ')';
            }
            $id = $rs->getFeedID();

            $content = '<p>IP: ' . $rs->comment_ip;

            if (trim($rs->comment_site)) {
                $content .= '<br />URL: <a href="' . $rs->comment_site . '">' . $rs->comment_site . '</a>';
            }
            $content .= "</p><hr />\n";
            $content .= $rs->comment_content;

            echo
            '<item>' . "\n" .
            '  <title>' . html::escapeHTML($title) . '</title>' . "\n" .
            '  <link>' . $uri . '</link>' . "\n" .
            '  <guid>' . $id . '</guid>' . "\n" .
            '  <pubDate>' . $rs->getRFC822Date() . '</pubDate>' . "\n" .
            '  <dc:creator>' . html::escapeHTML($author) . '</dc:creator>' . "\n" .
            '  <description>' . html::escapeHTML($content) . '</description>' . "\n" .
                '</item>';
        }

        echo "</channel>\n</rss>";
    }
}
