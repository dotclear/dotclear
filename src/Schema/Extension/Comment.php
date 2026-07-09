<?php

/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Schema\Extension;

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Exception\AppException;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Html;
use Dotclear\Schema\Status\User;

/**
 * @brief Dotclear comment record helpers
 *
 * This class adds new methods to database comment results.
 * You can call them on every record comming from Blog::getComments and similar
 * methods.
 *
 * @warning You should not give the first argument (usualy $rs) of every described function.
 */
class Comment
{
    /**
     * Returns comment date with <var>$format</var> as formatting pattern. If
     * format is empty, uses <var>date_format</var> blog setting.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     * @param      string    $format  The date format pattern
     * @param      string    $type    The type, (dt|upddt) defaults to comment_dt
     *
     * @return     string  The date.
     */
    public static function getDate(MetaRecord $rs, ?string $format, string $type = ''): string
    {
        if (is_null($format) || $format === '') {
            $format = App::blog()->settings()->get('system')->getStr('date_format', false);
        }

        $comment_tz = $rs->strField('comment_tz') ?: 'UTC';

        if ($type === 'upddt') {
            $comment_upddt = $rs->strField('comment_upddt');

            return Date::dt2str($format, $comment_upddt, $comment_tz);
        }

        $comment_dt = $rs->strField('comment_dt');

        return Date::dt2str($format, $comment_dt);
    }

    /**
     * Returns comment time with <var>$format</var> as formatting pattern. If
     * format is empty, uses <var>time_format</var> blog setting.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     * @param      string    $format  The date format pattern
     * @param      string    $type    The type, (dt|upddt) defaults to comment_dt
     *
     * @return     string  The time.
     */
    public static function getTime(MetaRecord $rs, ?string $format, string $type = ''): string
    {
        if (is_null($format) || $format === '') {
            $format = App::blog()->settings()->get('system')->getStr('time_format', false);
        }

        $comment_tz = $rs->strField('comment_tz') ?: 'UTC';

        if ($type === 'upddt') {
            $comment_upddt = $rs->strField('comment_upddt');

            return Date::dt2str($format, $comment_upddt, $comment_tz);
        }

        $comment_dt = $rs->strField('comment_dt');

        return Date::dt2str($format, $comment_dt);
    }

    /**
     * Returns comment timestamp.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     * @param      string    $type    The type, (dt|upddt) defaults to comment_dt
     *
     * @return     int  The timestamp.
     */
    public static function getTS(MetaRecord $rs, string $type = ''): int
    {
        if ($type === 'upddt') {
            $comment_upddt = $rs->strField('comment_upddt');

            return (int) strtotime($comment_upddt);
        }

        $comment_dt = $rs->strField('comment_dt');

        return (int) strtotime($comment_dt);
    }

    /**
     * Returns comment date formating according to the ISO 8601 standard.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     * @param      string    $type    The type, (dt|upddt) defaults to comment_dt
     *
     * @return     string  The iso 8601 date.
     */
    public static function getISO8601Date(MetaRecord $rs, string $type = ''): string
    {
        $comment_tz = $rs->strField('comment_tz') ?: 'UTC';
        $comment_ts = $rs->getTS($type);

        if ($type === 'upddt') {
            return Date::iso8601($comment_ts + Date::getTimeOffset($comment_tz), $comment_tz);
        }

        return Date::iso8601($comment_ts, $comment_tz);
    }

    /**
     * Returns comment date formating according to RFC 822.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     * @param      string    $type    The type, (dt|upddt) defaults to comment_dt
     *
     * @return     string  The rfc 822 date.
     */
    public static function getRFC822Date(MetaRecord $rs, string $type = ''): string
    {
        $comment_tz = $rs->strField('comment_tz') ?: 'UTC';
        $comment_ts = $rs->getTS($type);

        if ($type === 'upddt') {
            return Date::rfc822($comment_ts + Date::getTimeOffset($comment_tz), $comment_tz);
        }

        return Date::rfc822($comment_ts, $comment_tz);
    }

    /**
     * Returns comment content. If <var>$absolute_urls</var> is true, appends full
     * blog URL to each relative post URLs.
     *
     * @param      MetaRecord   $rs             Invisible parameter
     * @param      bool|int     $absolute_urls  With absolute URLs
     *
     * @return     string  The content.
     */
    public static function getContent(MetaRecord $rs, $absolute_urls = false): string
    {
        $comment_content = $rs->strField('comment_content');
        $post_url        = $rs->getPostURL();

        if (App::blog()->settings()->get('system')->getBool('comments_nofollow')) {
            $comment_content = (string) preg_replace_callback(
                '#<a(.*?href=".*?".*?)>#ms',
                function (array $m): string {
                    if (preg_match('/rel="ugc nofollow"/', $m[1])) {
                        return $m[0];
                    }

                    return '<a' . $m[1] . ' rel="ugc nofollow">';
                },
                $comment_content
            );
        } else {
            $comment_content = (string) preg_replace_callback(
                '#<a(.*?href=".*?".*?)>#ms',
                function (array $m): string {
                    if (preg_match('/rel="ugc"/', $m[1])) {
                        return $m[0];
                    }

                    return '<a' . $m[1] . ' rel="ugc">';
                },
                $comment_content
            );
        }

        if ($absolute_urls) {
            return Html::absoluteURLs($comment_content, $post_url);
        }

        return $comment_content;
    }

    /**
     * Returns comment author link to his website if he specified one.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function getAuthorURL(MetaRecord $rs): ?string
    {
        $comment_site = $rs->strField('comment_site');

        if (trim($comment_site) !== '') {
            return trim($comment_site);
        }

        return null;
    }

    /**
     * Returns comment post full URL.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function getPostURL(MetaRecord $rs): string
    {
        $post_type = $rs->strField('post_type');
        $post_url  = $rs->strField('post_url');

        return App::blog()->url() . App::postTypes()->get($post_type)->publicUrl(
            Html::sanitizeURL($post_url)
        );
    }

    /**
     * Returns comment author name in a link to his website if he specified one.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function getAuthorLink(MetaRecord $rs): string
    {
        $url    = is_string($url = $rs->getAuthorURL()) ? $url : '';
        $author = $rs->strField('comment_author');

        $rel = 'ugc';
        if (App::blog()->settings()->get('system')->getBool('comments_nofollow')) {
            $rel .= ' nofollow';
        }

        if ($url !== '' && $author !== '') {
            return (new Link())
                ->href(Html::escapeHTML($url))
                ->text($author)
                ->extra('rel="' . $rel . '"')
            ->render();
        }

        return $author;
    }

    /**
     * Returns comment author e-mail address. If <var>$encoded</var> is true,
     * "@" sign is replaced by "%40" and "." by "%2e".
     *
     * @param      MetaRecord  $rs     Invisible parameter
     * @param      bool      $encoded  Encode address
     */
    public static function getEmail(MetaRecord $rs, bool $encoded = true): string
    {
        $email = $rs->strField('comment_email');

        return $encoded ? strtr($email, ['@' => '%40', '.' => '%2e']) : $email;
    }

    /**
     * Returns trackback site title if comment is a trackback.
     *
     * @param      MetaRecord  $rs       Invisible parameter
     */
    public static function getTrackbackTitle(MetaRecord $rs): string
    {
        $comment_content = $rs->strField('comment_content');

        if ($rs->boolField('comment_trackback')
            && preg_match('|<p><strong>(.*?)</strong></p>|msU', $comment_content, $match)
        ) {
            return Html::decodeEntities($match[1]);
        }

        return '';
    }

    /**
     * Returns trackback content if comment is a trackback.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function getTrackbackContent(MetaRecord $rs): string
    {
        if ($rs->boolField('comment_trackback')) {
            $comment_content = $rs->strField('comment_content');

            return (string) preg_replace(
                '|<p><strong>.*?</strong></p>|msU',
                '',
                $comment_content
            );
        }

        return '';
    }

    /**
     * Returns comment feed unique ID.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function getFeedID(MetaRecord $rs): string
    {
        $comment_id = $rs->intField('comment_id');

        return 'urn:md5:' . md5(App::blog()->uid() . $comment_id);
    }

    /**
     * Determines whether the specified comment is from the post author.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function isMe(MetaRecord $rs): bool
    {
        $user_id = $rs->strField('user_id');
        if ($user_id === '') {
            return false;
        }

        $user_prefs = App::userPreferences()->createFromUser($user_id, 'profile');

        $mails = $user_prefs->get('profile')->getStr('mails', false);
        $urls  = $user_prefs->get('profile')->getStr('urls', false);

        $user_mails = $mails !== '' ? array_map(trim(...), explode(',', $mails)) : [];
        $user_urls  = $urls  !== '' ? array_map(trim(...), explode(',', $urls)) : [];

        $comment_email = $rs->strField('comment_email');
        $comment_site  = $rs->strField('comment_site');

        $user_email = $rs->strField('user_email');
        $user_url   = $rs->strField('user_url');

        return ($comment_email !== '' && $comment_site !== '')
            && ($comment_email === $user_email || in_array($comment_email, $user_mails, true))
            && ($comment_site === $user_url || in_array($comment_site, $user_urls, true));
    }

    /**
     * Determines whether the specified comment is from one of the blog authors.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function isUs(MetaRecord $rs): bool
    {
        try {
            // Get users with permissions on current blog
            $blogUsers = array_keys(App::blogs()->getBlogPermissions(App::blog()->id()));

            $sql = new SelectStatement();
            $sql
                ->columns([
                    'user_id',
                    'user_email',
                    'user_url',
                ])
                ->from(App::db()->con()->prefix() . App::auth()::USER_TABLE_NAME)
                ->where('user_status = ' . User::ENABLED)
                ->and('user_id ' . $sql->in($blogUsers));

            $rs_users = $sql->select() ?? MetaRecord::newFromArray([]);

            $comment_email = $rs->strField('comment_email');
            $comment_site  = $rs->strField('comment_site');

            if ($comment_email !== '' && $comment_site !== '') {
                while ($rs_users->fetch()) {
                    // 1st check on main email/site
                    $user_email = $rs_users->strField('user_email');
                    $user_url   = $rs_users->strField('user_url');

                    if ($comment_email === $user_email && $comment_site === $user_url) {
                        return true;
                    }

                    // 2nd check on secondary emails/sites
                    $user_id = $rs_users->strField('user_id');
                    if ($user_id === '') {
                        continue;
                    }

                    $user_prefs = App::userPreferences()->createFromUser($user_id, 'profile');

                    $mails = $user_prefs->get('profile')->getStr('mails', false);
                    $urls  = $user_prefs->get('profile')->getStr('urls', false);

                    $user_mails = $mails !== '' ? array_map(trim(...), explode(',', $mails)) : [];
                    $user_urls  = $urls  !== '' ? array_map(trim(...), explode(',', $urls)) : [];

                    if (($comment_email === $user_email || in_array($comment_email, $user_mails))
                        && ($comment_site === $user_url || in_array($comment_site, $user_urls))) {
                        return true;
                    }
                }
            }
        } catch (AppException) {
            return false;
        }

        return false;
    }
}
