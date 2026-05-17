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
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Html;

/**
 * @brief Dotclear post record helpers
 *
 * This class adds new methods to database post results.
 * You can call them on every record comming from Blog::getPosts and similar
 * methods.
 *
 * @warning You should not give the first argument (usualy $rs) of every described function.
 */
class Post
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

        # Ckeck if user is usage and owner of the entry
        $user_id = is_string($user_id = $rs->user_id) ? $user_id : '';
        if ($user_id === '') {
            return false;
        }

        return App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
        ]), App::blog()->id()) && $user_id === App::auth()->userID();
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

        $user_id = is_string($user_id = $rs->user_id) ? $user_id : '';
        if ($user_id === '') {
            return false;
        }

        # Check if user has delete rights and is owner of the entry
        return App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_DELETE,
        ]), App::blog()->id()) && $user_id === App::auth()->userID();
    }

    /**
     * Returns whether post is the first one of its day.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function firstPostOfDay(MetaRecord $rs): bool
    {
        if ($rs->isStart()) {
            return true;
        }

        $post_dt = is_string($post_dt = $rs->post_dt) ? $post_dt : 'now';
        $cdate   = date('Ymd', (int) strtotime($post_dt));

        $rs->movePrev();

        $post_dt = is_string($post_dt = $rs->post_dt) ? $post_dt : 'now';
        $ndate   = date('Ymd', (int) strtotime($post_dt));
        $rs->moveNext();

        return $ndate !== $cdate;
    }

    /**
     * Returns whether post is the last one of its day.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function lastPostOfDay(MetaRecord $rs): bool
    {
        if ($rs->isEnd()) {
            return true;
        }

        $post_dt = is_string($post_dt = $rs->post_dt) ? $post_dt : 'now';
        $cdate   = date('Ymd', (int) strtotime($post_dt));

        $rs->moveNext();

        $post_dt = is_string($post_dt = $rs->post_dt) ? $post_dt : 'now';
        $ndate   = date('Ymd', (int) strtotime($post_dt));

        $rs->movePrev();

        return $ndate !== $cdate;
    }

    /**
     * Returns whether comments are enabled on post.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function commentsActive(MetaRecord $rs): bool
    {
        // Check if feedback is open/close for the blog
        if (!App::blog()->settings()->system->allow_comments) {
            return false;
        }

        // Check if feedback is open/close for the category
        $cat_id = is_numeric($cat_id = $rs->cat_id) ? (int) $cat_id : 0;
        if ($cat_id !== 0) {
            $cats_no_feedback = app::blog()->settings()->system->cats_no_feedback;
            if ($cats_no_feedback && is_array($cats_no_feedback) && in_array($cat_id, $cats_no_feedback, true)) {
                return false;
            }
        }

        $comments_ttl = is_numeric($comments_ttl = App::blog()->settings()->system->comments_ttl) ? (int) $comments_ttl : 0;

        return $rs->post_open_comment && ($comments_ttl === 0 || time() - ($comments_ttl * 86400) < $rs->getTS());
    }

    /**
     * Returns whether trackbacks are enabled on post.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function trackbacksActive(MetaRecord $rs): bool
    {
        // Check if feedback is open/close for the blog
        if (!App::blog()->settings()->system->allow_comments) {
            return false;
        }

        // Check if feedback is open/close for the category
        $cat_id = is_numeric($cat_id = $rs->cat_id) ? (int) $cat_id : 0;
        if ($cat_id !== 0) {
            $cats_no_feedback = app::blog()->settings()->system->cats_no_feedback;
            if ($cats_no_feedback && is_array($cats_no_feedback) && in_array($cat_id, $cats_no_feedback, true)) {
                return false;
            }
        }

        $trackbacks_ttl = is_numeric($trackbacks_ttl = App::blog()->settings()->system->trackbacks_ttl) ? (int) $trackbacks_ttl : 0;

        return $rs->post_open_tb && ($trackbacks_ttl === 0 || time() - ($trackbacks_ttl * 86400) < $rs->getTS());
    }

    /**
     * Returns whether post has at least one comment.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function hasComments(MetaRecord $rs): bool
    {
        return $rs->nb_comment > 0;
    }

    /**
     * Returns whether post has at least one trackbacks.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function hasTrackbacks(MetaRecord $rs): bool
    {
        return $rs->nb_trackback > 0;
    }

    /**
     * Returns whether post has been updated since publication.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function isRepublished(MetaRecord $rs): bool
    {
        // Take care of post_dt which does not store seconds
        $post_dt    = is_numeric($post_dt = $rs->getTS()) ? (int) $post_dt : 0;
        $post_upddt = is_numeric($post_upddt = $rs->getTS('upddt')) ? (int) $post_upddt : 0;
        $post_tz    = is_string($post_tz = $rs->post_tz) ? $post_tz : 'UTC';

        return ($post_upddt + Date::getTimeOffset($post_tz, $post_upddt)) > ($post_dt + 60);
    }

    /**
     * Gets the full post url.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function getURL(MetaRecord $rs): string
    {
        $post_type = is_string($post_type = $rs->post_type) ? $post_type : '';
        $post_url  = is_string($post_url = $rs->post_url) ? $post_url : '';

        return App::blog()->url() . App::postTypes()->get($post_type)->publicUrl(
            Html::sanitizeURL($post_url)
        );
    }

    /**
     * Returns full post category URL.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function getCategoryURL(MetaRecord $rs): string
    {
        $cat_url = is_string($cat_url = $rs->cat_url) ? $cat_url : '';

        return App::blog()->url() . App::url()->getURLFor('category', Html::sanitizeURL($cat_url));
    }

    /**
     * Returns whether post has an excerpt.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function isExtended(MetaRecord $rs): bool
    {
        $post_excerpt_xhtml = is_string($post_excerpt_xhtml = $rs->post_excerpt_xhtml) ? $post_excerpt_xhtml : '';

        return $post_excerpt_xhtml !== '';
    }

    /**
     * Gets the post timestamp.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     * @param      string    $type   The type, (dt|upddt|creadt) defaults to post_dt
     */
    public static function getTS(MetaRecord $rs, string $type = ''): int
    {
        if ($type === 'upddt') {
            $post_upddt = is_string($post_upddt = $rs->post_upddt) ? $post_upddt : '';

            return (int) strtotime($post_upddt);
        }

        if ($type === 'creadt') {
            $post_creadt = is_string($post_creadt = $rs->post_creadt) ? $post_creadt : '';

            return (int) strtotime($post_creadt);
        }

        $post_dt = is_string($post_dt = $rs->post_dt) ? $post_dt : '';

        return (int) strtotime($post_dt);
    }

    /**
     * Returns post date formating according to the ISO 8601 standard.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     * @param      string    $type   The type, (dt|upddt|creadt) defaults to post_dt
     */
    public static function getISO8601Date(MetaRecord $rs, string $type = ''): string
    {
        $post_tz = is_string($post_tz = $rs->post_tz) ? $post_tz : 'UTC';
        $post_ts = is_numeric($post_ts = $rs->getTS($type)) ? (int) $post_ts : 0;

        if ($type === 'upddt' || $type === 'creadt') {
            return Date::iso8601($post_ts + Date::getTimeOffset($post_tz), $post_tz);
        }

        return Date::iso8601($post_ts, $post_tz);
    }

    /**
     * Returns post date formating according to RFC 822.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     * @param      string    $type   The type, (dt|upddt|creadt) defaults to post_dt
     */
    public static function getRFC822Date(MetaRecord $rs, string $type = ''): string
    {
        $post_tz = is_string($post_tz = $rs->post_tz) ? $post_tz : 'UTC';
        $post_ts = is_numeric($post_ts = $rs->getTS($type)) ? (int) $post_ts : 0;

        if ($type === 'upddt' || $type === 'creadt') {
            return Date::rfc822($post_ts + Date::getTimeOffset($post_tz), $post_tz);
        }

        return Date::rfc822($post_ts, $post_tz);
    }

    /**
     * Returns post date with <var>$format</var> as formatting pattern. If format
     * is empty, uses <var>date_format</var> blog setting.
     *
     * @param      MetaRecord   $rs         Invisible parameter
     * @param      string       $format     The date format pattern
     * @param      string       $type       The type, (dt|upddt|creadt) defaults to post_dt
     */
    public static function getDate(MetaRecord $rs, ?string $format, string $type = ''): string
    {
        if (is_null($format) || $format === '') {
            $format = is_string($format = App::blog()->settings()->system->date_format) ? $format : '';
        }

        $post_tz = is_string($post_tz = $rs->post_tz) ? $post_tz : 'UTC';

        if ($type === 'upddt') {
            $post_upddt = is_string($post_upddt = $rs->post_upddt) ? $post_upddt : '';

            return Date::dt2str($format, $post_upddt, $post_tz);
        }

        if ($type === 'creadt') {
            $post_creadt = is_string($post_creadt = $rs->post_creadt) ? $post_creadt : '';

            return Date::dt2str($format, $post_creadt, $post_tz);
        }

        $post_dt = is_string($post_dt = $rs->post_dt) ? $post_dt : '';

        return Date::dt2str($format, $post_dt);
    }

    /**
     * Returns post time with <var>$format</var> as formatting pattern. If format
     * is empty, uses <var>time_format</var> blog setting.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     * @param      string    $format  The time format pattern
     * @param      string    $type    The type, (dt|upddt|creadt) defaults to post_dt
     */
    public static function getTime(MetaRecord $rs, ?string $format, string $type = ''): string
    {
        if (is_null($format) || $format === '') {
            $format = is_string($format = App::blog()->settings()->system->time_format) ? $format : '';
        }

        $post_tz = is_string($post_tz = $rs->post_tz) ? $post_tz : 'UTC';

        if ($type === 'upddt') {
            $post_upddt = is_string($post_upddt = $rs->post_upddt) ? $post_upddt : '';

            return Date::dt2str($format, $post_upddt, $post_tz);
        }

        if ($type === 'creadt') {
            $post_creadt = is_string($post_creadt = $rs->post_creadt) ? $post_creadt : '';

            return Date::dt2str($format, $post_creadt, $post_tz);
        }

        $post_dt = is_string($post_dt = $rs->post_dt) ? $post_dt : '';

        return Date::dt2str($format, $post_dt);
    }

    /**
     * Returns author common name using user_id, user_name, user_firstname and
     * user_displayname fields.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function getAuthorCN(MetaRecord $rs): string
    {
        $user_id          = is_string($user_id = $rs->user_id) ? $user_id : '';
        $user_name        = is_string($user_name = $rs->user_name) ? $user_name : null;
        $user_firstname   = is_string($user_firstname = $rs->user_firstname) ? $user_firstname : null;
        $user_displayname = is_string($user_displayname = $rs->user_displayname) ? $user_displayname : null;

        return App::users()->getUserCN(
            $user_id,
            $user_name,
            $user_firstname,
            $user_displayname
        );
    }

    /**
     * Returns author common name with a link if he specified one in its preferences.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function getAuthorLink(MetaRecord $rs): string
    {
        $url    = is_string($url = $rs->user_url) ? $url : '';
        $author = is_string($author = $rs->getAuthorCN()) ? $author : '';

        if ($url !== '' && $author !== '') {
            return (new Link())
                ->href($url)
                ->text($author)
            ->render();
        }

        return $author;
    }

    /**
     * Returns author e-mail address. If <var>$encoded</var> is true, "@" sign is
     * replaced by "%40" and "." by "%2e".
     *
     * @param      MetaRecord  $rs     Invisible parameter
     * @param      bool      $encoded  Encode address
     */
    public static function getAuthorEmail(MetaRecord $rs, bool $encoded = true): string
    {
        $email = is_string($email = $rs->user_email) ? $email : '';

        return $encoded ? strtr($email, ['@' => '%40', '.' => '%2e']) : $email;
    }

    /**
     * Gets the post feed unique id.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function getFeedID(MetaRecord $rs): string
    {
        $post_id = is_numeric($post_id = $rs->post_id) ? (int) $post_id : 0;

        return 'urn:md5:' . md5(App::blog()->uid() . $post_id);
    }

    /**
     * Returns trackback RDF information block in HTML comment.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     * @param      string    $format   The format (html|xml)
     */
    public static function getTrackbackData(MetaRecord $rs, string $format = 'html'): string
    {
        $post_title          = is_string($post_title = $rs->post_title) ? $post_title : '';
        $post_url            = is_string($post_url = $rs->getURL()) ? $post_url : '';
        $post_trackback_link = is_string($post_trackback_link = $rs->getTrackbackLink()) ? $post_trackback_link : '';

        return
        ($format === 'xml' ? "<![CDATA[>\n" : '') .
        "<!--\n" .
        '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"' . "\n" .
        '  xmlns:dc="http://purl.org/dc/elements/1.1/"' . "\n" .
        '  xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">' . "\n" .
        "<rdf:Description\n" .
        '  rdf:about="' . $post_url . '"' . "\n" .
        '  dc:identifier="' . $post_url . '"' . "\n" .
        '  dc:title="' . htmlspecialchars($post_title, ENT_COMPAT, 'UTF-8') . '"' . "\n" .
        '  trackback:ping="' . $post_trackback_link . '" />' . "\n" .
        "</rdf:RDF>\n" .
        ($format === 'xml' ? '<!]]><!--' : '') .
        "-->\n";
    }

    /**
     * Gets the post trackback full URL.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function getTrackbackLink(MetaRecord $rs): string
    {
        $post_id = is_numeric($post_id = $rs->post_id) ? (int) $post_id : 0;

        return App::blog()->url() . App::url()->getURLFor('trackback', (string) $post_id);
    }

    /**
     * Returns post content. If <var>$absolute_urls</var> is true, appends full
     * blog URL to each relative post URLs.
     *
     * @param      MetaRecord   $rs             Invisible parameter
     * @param      bool|int     $absolute_urls  With absolute URLs
     */
    public static function getContent(MetaRecord $rs, $absolute_urls = false): string
    {
        $post_content_xhtml = is_string($post_content_xhtml = $rs->post_content_xhtml) ? $post_content_xhtml : '';
        $post_url           = is_string($post_url = $rs->getURL()) ? $post_url : '';

        if ($absolute_urls) {
            return Html::absoluteURLs($post_content_xhtml, $post_url);
        }

        return $post_content_xhtml;
    }

    /**
     * Returns post excerpt. If <var>$absolute_urls</var> is true, appends full
     * blog URL to each relative post URLs.
     *
     * @param      MetaRecord   $rs             Invisible parameter
     * @param      bool|int     $absolute_urls  With absolute URLs
     */
    public static function getExcerpt(MetaRecord $rs, $absolute_urls = false): string
    {
        $post_excerpt_xhtml = is_string($post_excerpt_xhtml = $rs->post_excerpt_xhtml) ? $post_excerpt_xhtml : '';
        $post_url           = is_string($post_url = $rs->getURL()) ? $post_url : '';

        if ($absolute_urls) {
            return Html::absoluteURLs($post_excerpt_xhtml, $post_url);
        }

        return $post_excerpt_xhtml;
    }

    /**
     * Returns post media count using a subquery.
     *
     * @param      MetaRecord   $rs         Invisible parameter
     * @param      string       $link_type  The link type
     */
    public static function countMedia(MetaRecord $rs, ?string $link_type = null): int
    {
        $index = $rs->index();

        if (is_array($rs->_nb_media) && isset($rs->_nb_media[$index])) {
            return is_numeric($rs->_nb_media[$index]) ? (int) $rs->_nb_media[$index] : 0;
        }

        $post_id = is_numeric($post_id = $rs->post_id) ? (int) $post_id : 0;

        $res = 0;
        $sql = new SelectStatement();
        $sql
            ->column($sql->count('media_id'))
            ->from(App::db()->con()->prefix() . App::postMedia()::POST_MEDIA_TABLE_NAME)
            ->where('post_id = ' . $post_id);

        if ($link_type) {
            $sql->and('link_type = ' . $sql->quote($link_type));
        }

        if (($run = $sql->select()) instanceof MetaRecord) {
            $res = (int) $run->cardinal();
        }

        if (is_array($rs->_nb_media)) {
            $rs->_nb_media[$index] = $res;
        }

        return $res;
    }

    /**
     * Returns true if current category if in given cat_url subtree
     *
     * @param      MetaRecord  $rs     Invisible parameter
     * @param      string    $cat_url  The cat url
     */
    public static function underCat(MetaRecord $rs, string $cat_url): bool
    {
        $rs_cat_url = is_string($rs_cat_url = $rs->cat_url) ? $rs_cat_url : '';

        return App::blog()->IsInCatSubtree($rs_cat_url, $cat_url);
    }
}
