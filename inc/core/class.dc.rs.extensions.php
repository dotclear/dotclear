<?php
/**
 * @brief Dotclear post record helpers
 *
 * This class adds new methods to database post results.
 * You can call them on every record comming from Blog::getPosts and similar
 * methods.
 *
 * @warning You should not give the first argument (usualy $rs) of every described function.
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Html;

class rsExtPost
{
    /**
     * Determines whether the specified post is editable.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     bool    True if the specified rs is editable, False otherwise.
     */
    public static function isEditable(MetaRecord $rs): bool
    {
        # If user is admin or contentadmin, true
        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            return true;
        }

        # No user id in result ? false
        if (!$rs->exists('user_id')) {
            return false;
        }

        # If user is usage and owner of the entry
        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
        ]), App::blog()->id())
            && $rs->user_id == App::auth()->userID()) {
            return true;
        }

        return false;
    }

    /**
     * Determines whether the specified post is deletable.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     bool    True if the specified rs is deletable, False otherwise.
     */
    public static function isDeletable(MetaRecord $rs): bool
    {
        # If user is admin, or contentadmin, true
        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            return true;
        }

        # No user id in result ? false
        if (!$rs->exists('user_id')) {
            return false;
        }

        # If user has delete rights and is owner of the entrie
        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_DELETE,
        ]), App::blog()->id())
            && $rs->user_id == App::auth()->userID()) {
            return true;
        }

        return false;
    }

    /**
     * Returns whether post is the first one of its day.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     bool
     */
    public static function firstPostOfDay(MetaRecord $rs): bool
    {
        if ($rs->isStart()) {
            return true;
        }

        $cdate = date('Ymd', strtotime((string) $rs->post_dt));
        $rs->movePrev();
        $ndate = date('Ymd', strtotime((string) $rs->post_dt));
        $rs->moveNext();

        return $ndate !== $cdate;
    }

    /**
     * Returns whether post is the last one of its day.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     bool
     */
    public static function lastPostOfDay(MetaRecord $rs): bool
    {
        if ($rs->isEnd()) {
            return true;
        }

        $cdate = date('Ymd', strtotime((string) $rs->post_dt));
        $rs->moveNext();
        $ndate = date('Ymd', strtotime((string) $rs->post_dt));
        $rs->movePrev();

        return $ndate !== $cdate;
    }

    /**
     * Returns whether comments are enabled on post.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     bool
     */
    public static function commentsActive(MetaRecord $rs): bool
    {
        return
        App::blog()->settings()->system->allow_comments
            && $rs->post_open_comment
            && (App::blog()->settings()->system->comments_ttl == 0 || time() - (App::blog()->settings()->system->comments_ttl * 86400) < $rs->getTS());
    }

    /**
     * Returns whether trackbacks are enabled on post.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     bool
     */
    public static function trackbacksActive(MetaRecord $rs): bool
    {
        return
        App::blog()->settings()->system->allow_trackbacks
            && $rs->post_open_tb
            && (App::blog()->settings()->system->trackbacks_ttl == 0 || time() - (App::blog()->settings()->system->trackbacks_ttl * 86400) < $rs->getTS());
    }

    /**
     * Returns whether post has at least one comment.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     bool
     */
    public static function hasComments(MetaRecord $rs): bool
    {
        return $rs->nb_comment > 0;
    }

    /**
     * Returns whether post has at least one trackbacks.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     bool
     */
    public static function hasTrackbacks(MetaRecord $rs): bool
    {
        return $rs->nb_trackback > 0;
    }

    /**
     * Returns whether post has been updated since publication.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     bool
     */
    public static function isRepublished(MetaRecord $rs): bool
    {
        // Take care of post_dt does not store seconds
        return (($rs->getTS('upddt') + Date::getTimeOffset($rs->post_tz, $rs->getTS('upddt'))) > ($rs->getTS() + 60));
    }

    /**
     * Gets the full post url.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     string  The url.
     */
    public static function getURL(MetaRecord $rs): string
    {
        return App::blog()->url() . App::postTypes()->get((string) $rs->post_type)->publicUrl(
            Html::sanitizeURL($rs->post_url)
        );
    }

    /**
     * Returns full post category URL.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     string  The category url.
     */
    public static function getCategoryURL(MetaRecord $rs): string
    {
        return App::blog()->url() . App::url()->getURLFor('category', Html::sanitizeURL($rs->cat_url));
    }

    /**
     * Returns whether post has an excerpt.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     bool
     */
    public static function isExtended(MetaRecord $rs): bool
    {
        return (string) $rs->post_excerpt_xhtml !== '';
    }

    /**
     * Gets the post timestamp.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     * @param      string    $type   The type, (dt|upddt|creadt) defaults to post_dt
     *
     * @return     integer  The ts.
     */
    public static function getTS(MetaRecord $rs, string $type = ''): int
    {
        if ($type === 'upddt') {
            return strtotime((string) $rs->post_upddt);
        } elseif ($type === 'creadt') {
            return strtotime((string) $rs->post_creadt);
        }

        return strtotime($rs->post_dt);
    }

    /**
     * Returns post date formating according to the ISO 8601 standard.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     * @param      string    $type   The type, (dt|upddt|creadt) defaults to post_dt
     *
     * @return     string  The iso 8601 date.
     */
    public static function getISO8601Date(MetaRecord $rs, string $type = ''): string
    {
        if ($type === 'upddt' || $type === 'creadt') {
            return Date::iso8601($rs->getTS($type) + Date::getTimeOffset($rs->post_tz), $rs->post_tz);
        }

        return Date::iso8601($rs->getTS(), $rs->post_tz);
    }

    /**
     * Returns post date formating according to RFC 822.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     * @param      string    $type   The type, (dt|upddt|creadt) defaults to post_dt
     *
     * @return     string  The rfc 822 date.
     */
    public static function getRFC822Date(MetaRecord $rs, string $type = ''): string
    {
        if ($type === 'upddt' || $type === 'creadt') {
            return Date::rfc822($rs->getTS($type) + Date::getTimeOffset($rs->post_tz), $rs->post_tz);
        }

        return Date::rfc822($rs->getTS($type), $rs->post_tz);
    }

    /**
     * Returns post date with <var>$format</var> as formatting pattern. If format
     * is empty, uses <var>date_format</var> blog setting.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     * @param      string    $format  The date format pattern
     * @param      string    $type    The type, (dt|upddt|creadt) defaults to post_dt
     *
     * @return     string  The date.
     */
    public static function getDate(MetaRecord $rs, ?string $format, string $type = ''): string
    {
        if (!$format) {
            $format = App::blog()->settings()->system->date_format;
        }

        if ($type == 'upddt') {
            return Date::dt2str($format, (string) $rs->post_upddt, (string) $rs->post_tz);
        } elseif ($type == 'creadt') {
            return Date::dt2str($format, (string) $rs->post_creadt, (string) $rs->post_tz);
        }

        return Date::dt2str($format, (string) $rs->post_dt);
    }

    /**
     * Returns post time with <var>$format</var> as formatting pattern. If format
     * is empty, uses <var>time_format</var> blog setting.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     * @param      string    $format  The time format pattern
     * @param      string    $type    The type, (dt|upddt|creadt) defaults to post_dt
     *
     * @return     string  The time.
     */
    public static function getTime(MetaRecord $rs, ?string $format, string $type = ''): string
    {
        if (!$format) {
            $format = App::blog()->settings()->system->time_format;
        }

        if ($type == 'upddt') {
            return Date::dt2str($format, (string) $rs->post_upddt, (string) $rs->post_tz);
        } elseif ($type == 'creadt') {
            return Date::dt2str($format, (string) $rs->post_creadt, (string) $rs->post_tz);
        }

        return Date::dt2str($format, (string) $rs->post_dt);
    }

    /**
     * Returns author common name using user_id, user_name, user_firstname and
     * user_displayname fields.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     string  The author common name.
     */
    public static function getAuthorCN(MetaRecord $rs): string
    {
        return dcUtils::getUserCN(
            $rs->user_id,
            $rs->user_name,
            $rs->user_firstname,
            $rs->user_displayname
        );
    }

    /**
     * Returns author common name with a link if he specified one in its preferences.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     string
     */
    public static function getAuthorLink(MetaRecord $rs): string
    {
        $res = '%1$s';
        $url = $rs->user_url;
        if ($url) {
            $res = '<a href="%2$s">%1$s</a>';
        }

        return sprintf($res, Html::escapeHTML($rs->getAuthorCN()), Html::escapeHTML($url));
    }

    /**
     * Returns author e-mail address. If <var>$encoded</var> is true, "@" sign is
     * replaced by "%40" and "." by "%2e".
     *
     * @param      MetaRecord  $rs     Invisible parameter
     * @param      bool      $encoded  Encode address
     *
     * @return     string  The author email.
     */
    public static function getAuthorEmail(MetaRecord $rs, bool $encoded = true): string
    {
        if ($encoded) {
            return strtr((string) $rs->user_email, ['@' => '%40', '.' => '%2e']);
        }

        return (string) $rs->user_email;
    }

    /**
     * Gets the post feed unique id.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     string  The feed id.
     */
    public static function getFeedID(MetaRecord $rs): string
    {
        return 'urn:md5:' . md5(App::blog()->uid() . $rs->post_id);
    }

    /**
     * Returns trackback RDF information block in HTML comment.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     * @param      string    $format   The format (html|xml)
     *
     * @return     string
     */
    public static function getTrackbackData(MetaRecord $rs, string $format = 'html'): string
    {
        return
        ($format === 'xml' ? "<![CDATA[>\n" : '') .
        "<!--\n" .
        '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"' . "\n" .
        '  xmlns:dc="http://purl.org/dc/elements/1.1/"' . "\n" .
        '  xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">' . "\n" .
        "<rdf:Description\n" .
        '  rdf:about="' . $rs->getURL() . '"' . "\n" .
        '  dc:identifier="' . $rs->getURL() . '"' . "\n" .
        '  dc:title="' . htmlspecialchars((string) $rs->post_title, ENT_COMPAT, 'UTF-8') . '"' . "\n" .
        '  trackback:ping="' . $rs->getTrackbackLink() . '" />' . "\n" .
            "</rdf:RDF>\n" .
            ($format == 'xml' ? '<!]]><!--' : '') .
            "-->\n";
    }

    /**
     * Gets the post trackback full URL.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     string  The trackback link.
     */
    public static function getTrackbackLink(MetaRecord $rs): string
    {
        return App::blog()->url() . App::url()->getURLFor('trackback', (string) $rs->post_id);
    }

    /**
     * Returns post content. If <var>$absolute_urls</var> is true, appends full
     * blog URL to each relative post URLs.
     *
     * @param      MetaRecord   $rs             Invisible parameter
     * @param      bool|int     $absolute_urls  With absolute URLs
     *
     * @return     string  The content.
     */
    public static function getContent(MetaRecord $rs, $absolute_urls = false): string
    {
        if ($absolute_urls) {
            return Html::absoluteURLs((string) $rs->post_content_xhtml, $rs->getURL());
        }

        return (string) $rs->post_content_xhtml;
    }

    /**
     * Returns post excerpt. If <var>$absolute_urls</var> is true, appends full
     * blog URL to each relative post URLs.
     *
     * @param      MetaRecord   $rs             Invisible parameter
     * @param      bool|int     $absolute_urls  With absolute URLs
     *
     * @return     string  The excerpt.
     */
    public static function getExcerpt(MetaRecord $rs, $absolute_urls = false): string
    {
        if ($absolute_urls) {
            return Html::absoluteURLs((string) $rs->post_excerpt_xhtml, $rs->getURL());
        }

        return (string) $rs->post_excerpt_xhtml;
    }

    /**
     * Returns post media count using a subquery.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     * @param      string    $link_type  The link type
     *
     * @return     integer Number of media.
     */
    public static function countMedia(MetaRecord $rs, ?string $link_type = null): int
    {
        if (isset($rs->_nb_media[$rs->index()])) {
            return (int) $rs->_nb_media[$rs->index()];
        }
        $strReq = 'SELECT count(media_id) ' .
            'FROM ' . App::con()->prefix() . App::postMedia()::POST_MEDIA_TABLE_NAME . ' ' .
            'WHERE post_id = ' . (int) $rs->post_id . ' ';
        if ($link_type) {
            $strReq .= "AND link_type = '" . App::con()->escape($link_type) . "'";
        }

        $res = (int) (new MetaRecord(App::con()->select($strReq)))->f(0);

        $rs->_nb_media[$rs->index()] = $res;

        return $res;
    }

    /**
     * Returns true if current category if in given cat_url subtree
     *
     * @param      MetaRecord  $rs     Invisible parameter
     * @param      string    $cat_url  The cat url
     *
     * @return     bool     true if current cat is in given cat subtree
     */
    public static function underCat(MetaRecord $rs, string $cat_url): bool
    {
        return App::blog()->IsInCatSubtree((string) $rs->cat_url, $cat_url);
    }
}

/**
@ingroup DC_CORE
@brief Dotclear comment Record helpers.

This class adds new methods to database comment results.
You can call them on every record comming from Blog::getComments and similar
methods.

@warning You should not give the first argument (usualy $rs) of every described
function.
 */
class rsExtComment
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
        if (!$format) {
            $format = App::blog()->settings()->system->date_format;
        }

        if ($type === 'upddt') {
            return Date::dt2str($format, (string) $rs->comment_upddt, (string) $rs->comment_tz);
        }

        return Date::dt2str($format, (string) $rs->comment_dt);
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
        if (!$format) {
            $format = App::blog()->settings()->system->time_format;
        }

        if ($type === 'upddt') {
            return Date::dt2str($format, (string) $rs->comment_updt, (string) $rs->comment_tz);
        }

        return Date::dt2str($format, (string) $rs->comment_dt);
    }

    /**
     * Returns comment timestamp.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     * @param      string    $type    The type, (dt|upddt) defaults to comment_dt
     *
     * @return     integer The timestamp.
     */
    public static function getTS(MetaRecord $rs, string $type = ''): int
    {
        if ($type === 'upddt') {
            return strtotime((string) $rs->comment_upddt);
        }

        return strtotime((string) $rs->comment_dt);
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
        if ($type === 'upddt') {
            return Date::iso8601($rs->getTS($type) + Date::getTimeOffset((string) $rs->comment_tz), (string) $rs->comment_tz);
        }

        return Date::iso8601($rs->getTS(), (string) $rs->comment_tz);
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
        if ($type === 'upddt') {
            return Date::rfc822($rs->getTS($type) + Date::getTimeOffset((string) $rs->comment_tz), (string) $rs->comment_tz);
        }

        return Date::rfc822($rs->getTS(), (string) $rs->comment_tz);
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
        $res = (string) $rs->comment_content;

        if (App::blog()->settings()->system->comments_nofollow) {
            $res = preg_replace_callback(
                '#<a(.*?href=".*?".*?)>#ms',
                function ($m) {
                    if (preg_match('/rel="ugc nofollow"/', $m[1])) {
                        return $m[0];
                    }

                    return '<a' . $m[1] . ' rel="ugc nofollow">';
                },
                $res
            );
        } else {
            $res = preg_replace_callback(
                '#<a(.*?href=".*?".*?)>#ms',
                function ($m) {
                    if (preg_match('/rel="ugc"/', $m[1])) {
                        return $m[0];
                    }

                    return '<a' . $m[1] . ' rel="ugc">';
                },
                $res
            );
        }

        if ($absolute_urls) {
            $res = Html::absoluteURLs($res, $rs->getPostURL());
        }

        return $res;
    }

    /**
     * Returns comment author link to his website if he specified one.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     mixed  The author url.
     */
    public static function getAuthorURL(MetaRecord $rs)
    {
        if (trim((string) $rs->comment_site)) {
            return trim((string) $rs->comment_site);
        }
    }

    /**
     * Returns comment post full URL.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     string  The comment post url.
     */
    public static function getPostURL(MetaRecord $rs): string
    {
        return App::blog()->url() . App::postTypes()->get($rs->post_type)->publicUrl(
            Html::sanitizeURL($rs->post_url)
        );
    }

    /**
     * Returns comment author name in a link to his website if he specified one.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     string  The author link.
     */
    public static function getAuthorLink(MetaRecord $rs): string
    {
        $res = '%1$s';
        $url = $rs->getAuthorURL();
        if ($url) {
            $res = '<a href="%2$s" rel="%3$s">%1$s</a>';
        }

        $rel = 'ugc';
        if (App::blog()->settings()->system->comments_nofollow) {
            $rel .= ' nofollow';
        }

        return sprintf($res, Html::escapeHTML($rs->comment_author), Html::escapeHTML($url), $rel);
    }

    /**
     * Returns comment author e-mail address. If <var>$encoded</var> is true,
     * "@" sign is replaced by "%40" and "." by "%2e".
     *
     * @param      MetaRecord  $rs     Invisible parameter
     * @param      bool      $encoded  Encode address
     *
     * @return     string  The email.
     */
    public static function getEmail(MetaRecord $rs, bool $encoded = true): string
    {
        return $encoded ? strtr((string) $rs->comment_email, ['@' => '%40', '.' => '%2e']) : $rs->comment_email;
    }

    /**
     * Returns trackback site title if comment is a trackback.
     *
     * @param      MetaRecord  $rs       Invisible parameter
     *
     * @return     string  The trackback title.
     */
    public static function getTrackbackTitle(MetaRecord $rs): string
    {
        if ($rs->comment_trackback == 1 && preg_match(
            '|<p><strong>(.*?)</strong></p>|msU',
            (string) $rs->comment_content,
            $match
        )) {
            return Html::decodeEntities($match[1]);
        }

        return '';
    }

    /**
     * Returns trackback content if comment is a trackback.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     string  The trackback content.
     */
    public static function getTrackbackContent(MetaRecord $rs): string
    {
        if ($rs->comment_trackback == 1) {
            return preg_replace(
                '|<p><strong>.*?</strong></p>|msU',
                '',
                (string) $rs->comment_content
            );
        }

        return '';
    }

    /**
     * Returns comment feed unique ID.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     string  The feed id.
     */
    public static function getFeedID(MetaRecord $rs): string
    {
        return 'urn:md5:' . md5(App::blog()->uid() . $rs->comment_id);
    }

    /**
     * Determines whether the specified comment is from the post author.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     bool    True if the specified comment is from the post author, False otherwise.
     */
    public static function isMe(MetaRecord $rs): bool
    {
        $user_prefs         = new dcPrefs((string) $rs->user_id, 'profile');
        $user_profile_mails = $user_prefs->profile->mails ?
            array_map('trim', explode(',', $user_prefs->profile->mails)) :
            [];
        $user_profile_urls = $user_prefs->profile->urls ?
            array_map('trim', explode(',', $user_prefs->profile->urls)) :
            [];

        return
            ($rs->comment_email && $rs->comment_site) && ($rs->comment_email == $rs->user_email || in_array($rs->comment_email, $user_profile_mails)) && ($rs->comment_site == $rs->user_url || in_array($rs->comment_site, $user_profile_urls));
    }
}

/**
@ingroup DC_CORE
@brief Dotclear dates Record helpers.

This class adds new methods to database dates results.
You can call them on every record comming from Blog::getDates.

@warning You should not give the first argument (usualy $rs) of every described
function.
 */
class rsExtDates
{
    /**
     * Convert date to timestamp
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     integer
     */
    public static function ts(MetaRecord $rs): int
    {
        return strtotime((string) $rs->dt);
    }

    /**
     * Get date year
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     string
     */
    public static function year(MetaRecord $rs): string
    {
        return date('Y', strtotime((string) $rs->dt));
    }

    /**
     * Get date month
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     string
     */
    public static function month(MetaRecord $rs): string
    {
        return date('m', strtotime((string) $rs->dt));
    }

    /**
     * Get date day
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     string
     */
    public static function day(MetaRecord $rs): string
    {
        return date('d', strtotime((string) $rs->dt));
    }

    /**
     * Returns date month archive full URL.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     string
     */
    public static function url(MetaRecord $rs): string
    {
        $url = date('Y/m', strtotime((string) $rs->dt));

        return App::blog()->url() . App::url()->getURLFor('archive', $url);
    }

    /**
     * Returns whether date is the first of year.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     bool
     */
    public static function yearHeader(MetaRecord $rs): bool
    {
        if ($rs->isStart()) {
            return true;
        }

        $y = $rs->year();
        $rs->movePrev();
        $py = $rs->year();
        $rs->moveNext();

        return $y != $py;
    }

    /**
     * Returns whether date is the last of year.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     bool
     */
    public static function yearFooter(MetaRecord $rs): bool
    {
        if ($rs->isEnd()) {
            return true;
        }

        $y = $rs->year();
        if ($rs->moveNext()) {
            $ny = $rs->year();
            $rs->movePrev();

            return $y != $ny;
        }

        return false;
    }
}

/**
@ingroup DC_CORE
@brief Dotclear dates Record helpers.

This class adds new methods to database dates results.
You can call them on every record comming from Auth::checkUser and
Users::getUsers.

@warning You should not give the first argument (usualy $rs) of every described
function.
 */
class rsExtUser
{
    /**
     * Returns a user option.
     *
     * @param      MetaRecord   $rs       Invisible parameter
     * @param      string     $name     The name of option
     *
     * @return     mixed
     */
    public static function option(MetaRecord $rs, string $name)
    {
        $options = self::options($rs);

        if (isset($options[$name])) {
            return $options[$name];
        }
    }

    /**
     * Returns all user options.
     *
     * @param      MetaRecord   $rs       Invisible parameter
     *
     * @return     array
     */
    public static function options(MetaRecord $rs): array
    {
        $options = @unserialize((string) $rs->user_options);
        if (is_array($options)) {
            return $options;
        }

        return [];
    }

    /**
     * Converts this Record to a {@link StaticRecord} instance.
     *
     * @param      MetaRecord   $rs       Invisible parameter
     *
     * @return     MetaRecord  The extent static record.
     */
    public static function toExtStatic(MetaRecord $rs): MetaRecord
    {
        $rs->toExtStatic();

        return $rs;
    }
}

class rsExtBlog
{
    /**
     * Converts this Record to a {@link StaticRecord} instance.
     *
     * @param      MetaRecord  $rs       Invisible parameter
     *
     * @return     MetaRecord  The extent static record.
     */
    public static function toExtStatic(MetaRecord $rs): MetaRecord
    {
        $rs->toExtStatic();

        return $rs;
    }
}
