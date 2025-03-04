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
        return App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
        ]), App::blog()->id()) && $rs->user_id == App::auth()->userID();
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

        # Check if user has delete rights and is owner of the entry
        return App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_DELETE,
        ]), App::blog()->id()) && $rs->user_id == App::auth()->userID();
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

        $cdate = date('Ymd', (int) strtotime((string) $rs->post_dt));
        $rs->movePrev();
        $ndate = date('Ymd', (int) strtotime((string) $rs->post_dt));
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

        $cdate = date('Ymd', (int) strtotime((string) $rs->post_dt));
        $rs->moveNext();
        $ndate = date('Ymd', (int) strtotime((string) $rs->post_dt));
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
        return
        App::blog()->settings()->system->allow_comments
            && $rs->post_open_comment
            && (App::blog()->settings()->system->comments_ttl == 0 || time() - (App::blog()->settings()->system->comments_ttl * 86400) < $rs->getTS());
    }

    /**
     * Returns whether trackbacks are enabled on post.
     *
     * @param      MetaRecord  $rs     Invisible parameter
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
        // Take care of post_dt does not store seconds
        return ($rs->getTS('upddt') + Date::getTimeOffset($rs->post_tz, $rs->getTS('upddt'))) > ($rs->getTS() + 60);
    }

    /**
     * Gets the full post url.
     *
     * @param      MetaRecord  $rs     Invisible parameter
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
     */
    public static function getCategoryURL(MetaRecord $rs): string
    {
        return App::blog()->url() . App::url()->getURLFor('category', Html::sanitizeURL($rs->cat_url));
    }

    /**
     * Returns whether post has an excerpt.
     *
     * @param      MetaRecord  $rs     Invisible parameter
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
     */
    public static function getTS(MetaRecord $rs, string $type = ''): int
    {
        if ($type === 'upddt') {
            return (int) strtotime((string) $rs->post_upddt);
        } elseif ($type === 'creadt') {
            return (int) strtotime((string) $rs->post_creadt);
        }

        return (int) strtotime($rs->post_dt);
    }

    /**
     * Returns post date formating according to the ISO 8601 standard.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     * @param      string    $type   The type, (dt|upddt|creadt) defaults to post_dt
     */
    public static function getISO8601Date(MetaRecord $rs, string $type = ''): string
    {
        if ($type === 'upddt' || $type === 'creadt') {
            return Date::iso8601((int) $rs->getTS($type) + Date::getTimeOffset($rs->post_tz), $rs->post_tz);
        }

        return Date::iso8601($rs->getTS(), $rs->post_tz);
    }

    /**
     * Returns post date formating according to RFC 822.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     * @param      string    $type   The type, (dt|upddt|creadt) defaults to post_dt
     */
    public static function getRFC822Date(MetaRecord $rs, string $type = ''): string
    {
        if ($type === 'upddt' || $type === 'creadt') {
            return Date::rfc822((int) $rs->getTS($type) + Date::getTimeOffset($rs->post_tz), $rs->post_tz);
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
     */
    public static function getDate(MetaRecord $rs, ?string $format, string $type = ''): string
    {
        if (!$format) {
            $format = App::blog()->settings()->system->date_format;
        }

        if ($type === 'upddt') {
            return Date::dt2str($format, (string) $rs->post_upddt, (string) $rs->post_tz);
        } elseif ($type === 'creadt') {
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
     */
    public static function getTime(MetaRecord $rs, ?string $format, string $type = ''): string
    {
        if (!$format) {
            $format = App::blog()->settings()->system->time_format;
        }

        if ($type === 'upddt') {
            return Date::dt2str($format, (string) $rs->post_upddt, (string) $rs->post_tz);
        } elseif ($type === 'creadt') {
            return Date::dt2str($format, (string) $rs->post_creadt, (string) $rs->post_tz);
        }

        return Date::dt2str($format, (string) $rs->post_dt);
    }

    /**
     * Returns author common name using user_id, user_name, user_firstname and
     * user_displayname fields.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function getAuthorCN(MetaRecord $rs): string
    {
        return App::users()->getUserCN(
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
        return App::blog()->url() . App::url()->getURLFor('trackback', (string) $rs->post_id);
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
     */
    public static function countMedia(MetaRecord $rs, ?string $link_type = null): int
    {
        if (isset($rs->_nb_media[$rs->index()])) {
            return (int) $rs->_nb_media[$rs->index()];
        }

        $res = 0;
        $sql = new SelectStatement();
        $sql
            ->column($sql->count('media_id'))
            ->from(App::con()->prefix() . App::postMedia()::POST_MEDIA_TABLE_NAME)
            ->where('post_id = ' . $rs->post_id);

        if ($link_type) {
            $sql->and('link_type = ' . $sql->quote($link_type));
        }

        if (($run = $sql->select()) instanceof MetaRecord) {
            $value = $run->f(0);
            if (is_string($value) || is_numeric($value)) {
                $res = (int) $value;
            }
        }

        $rs->_nb_media[$rs->index()] = $res;

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
        return App::blog()->IsInCatSubtree((string) $rs->cat_url, $cat_url);
    }
}
