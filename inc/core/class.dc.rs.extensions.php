<?php
/**
 * @brief Dotclear post record helpers
 *
 * This class adds new methods to database post results.
 * You can call them on every record comming from dcBlog::getPosts and similar
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
if (!defined('DC_RC_PATH')) {
    return;
}

class rsExtPost
{
    /**
     * Determines whether the specified post is editable.
     *
     * @param      record  $rs     Invisible parameter
     *
     * @return     bool    True if the specified rs is editable, False otherwise.
     */
    public static function isEditable($rs)
    {
        # If user is admin or contentadmin, true
        if ($rs->core->auth->check('contentadmin', $rs->core->blog->id)) {
            return true;
        }

        # No user id in result ? false
        if (!$rs->exists('user_id')) {
            return false;
        }

        # If user is usage and owner of the entrie
        if ($rs->core->auth->check('usage', $rs->core->blog->id)
            && $rs->user_id == $rs->core->auth->userID()) {
            return true;
        }

        return false;
    }

    /**
     * Determines whether the specified post is deletable.
     *
     * @param      record  $rs     Invisible parameter
     *
     * @return     bool    True if the specified rs is deletable, False otherwise.
     */
    public static function isDeletable($rs)
    {
        # If user is admin, or contentadmin, true
        if ($rs->core->auth->check('contentadmin', $rs->core->blog->id)) {
            return true;
        }

        # No user id in result ? false
        if (!$rs->exists('user_id')) {
            return false;
        }

        # If user has delete rights and is owner of the entrie
        if ($rs->core->auth->check('delete', $rs->core->blog->id)
            && $rs->user_id == $rs->core->auth->userID()) {
            return true;
        }

        return false;
    }

    /**
     * Returns whether post is the first one of its day.
     *
     * @param      record  $rs     Invisible parameter
     *
     * @return     bool
     */
    public static function firstPostOfDay($rs)
    {
        if ($rs->isStart()) {
            return true;
        }

        $cdate = date('Ymd', strtotime($rs->post_dt));
        $rs->movePrev();
        $ndate = date('Ymd', strtotime($rs->post_dt));
        $rs->moveNext();

        return $ndate != $cdate;
    }

    /**
     * Returns whether post is the last one of its day.
     *
     * @param      record  $rs     Invisible parameter
     *
     * @return     bool
     */
    public static function lastPostOfDay($rs)
    {
        if ($rs->isEnd()) {
            return true;
        }

        $cdate = date('Ymd', strtotime($rs->post_dt));
        $rs->moveNext();
        $ndate = date('Ymd', strtotime($rs->post_dt));
        $rs->movePrev();

        return $ndate != $cdate;
    }

    /**
     * Returns whether comments are enabled on post.
     *
     * @param      record  $rs     Invisible parameter
     *
     * @return     bool
     */
    public static function commentsActive($rs)
    {
        return
        $rs->core->blog->settings->system->allow_comments
            && $rs->post_open_comment
            && ($rs->core->blog->settings->system->comments_ttl == 0 || time() - ($rs->core->blog->settings->system->comments_ttl * 86400) < $rs->getTS());
    }

    /**
     * Returns whether trackbacks are enabled on post.
     *
     * @param      record  $rs     Invisible parameter
     *
     * @return     bool
     */
    public static function trackbacksActive($rs)
    {
        return
        $rs->core->blog->settings->system->allow_trackbacks
            && $rs->post_open_tb
            && ($rs->core->blog->settings->system->trackbacks_ttl == 0 || time() - ($rs->core->blog->settings->system->trackbacks_ttl * 86400) < $rs->getTS());
    }

    /**
     * Returns whether post has at least one comment.
     *
     * @param      record  $rs     Invisible parameter
     *
     * @return     bool
     */
    public static function hasComments($rs)
    {
        return $rs->nb_comment > 0;
    }

    /**
     * Returns whether post has at least one trackbacks.
     *
     * @param      record  $rs     Invisible parameter
     *
     * @return     bool
     */
    public static function hasTrackbacks($rs)
    {
        return $rs->nb_trackback > 0;
    }

    /**
     * Returns whether post has been updated since publication.
     *
     * @param      record  $rs     Invisible parameter
     *
     * @return     bool
     */
    public static function isRepublished($rs)
    {
        // Take care of post_dt does not store seconds
        return (($rs->getTS('upddt') + dt::getTimeOffset($rs->post_tz, $rs->getTS('upddt'))) > ($rs->getTS() + 60));
    }

    /**
     * Gets the full post url.
     *
     * @param      record  $rs     Invisible parameter
     *
     * @return     string  The url.
     */
    public static function getURL($rs)
    {
        return $rs->core->blog->url . $rs->core->getPostPublicURL(
            $rs->post_type, html::sanitizeURL($rs->post_url)
        );
    }

    /**
     * Returns full post category URL.
     *
     * @param      record  $rs     Invisible parameter
     *
     * @return     string  The category url.
     */
    public static function getCategoryURL($rs)
    {
        return $rs->core->blog->url . $rs->core->url->getURLFor('category', html::sanitizeURL($rs->cat_url));
    }

    /**
     * Returns whether post has an excerpt.
     *
     * @param      record  $rs     Invisible parameter
     *
     * @return     bool
     */
    public static function isExtended($rs)
    {
        return $rs->post_excerpt_xhtml != '';
    }

    /**
     * Gets the post timestamp.
     *
     * @param      record  $rs     Invisible parameter
     * @param      string  $type   The type, (dt|upddt|creadt) defaults to post_dt
     *
     * @return     integer  The ts.
     */
    public static function getTS($rs, $type = '')
    {
        if ($type == 'upddt') {
            return strtotime($rs->post_upddt);
        } elseif ($type == 'creadt') {
            return strtotime($rs->post_creadt);
        }

        return strtotime($rs->post_dt);
    }

    /**
     * Returns post date formating according to the ISO 8601 standard.
     *
     * @param      record  $rs     Invisible parameter
     * @param      string  $type   The type, (dt|upddt|creadt) defaults to post_dt
     *
     * @return     string  The iso 8601 date.
     */
    public static function getISO8601Date($rs, $type = '')
    {
        if ($type == 'upddt' || $type == 'creadt') {
            return dt::iso8601($rs->getTS($type) + dt::getTimeOffset($rs->post_tz), $rs->post_tz);
        }

        return dt::iso8601($rs->getTS(), $rs->post_tz);
    }

    /**
     * Returns post date formating according to RFC 822.
     *
     * @param      record  $rs     Invisible parameter
     * @param      string  $type   The type, (dt|upddt|creadt) defaults to post_dt
     *
     * @return     string  The rfc 822 date.
     */
    public static function getRFC822Date($rs, $type = '')
    {
        if ($type == 'upddt' || $type == 'creadt') {
            return dt::rfc822($rs->getTS($type) + dt::getTimeOffset($rs->post_tz), $rs->post_tz);
        }

        return dt::rfc822($rs->getTS($type), $rs->post_tz);
    }

    /**
     * Returns post date with <var>$format</var> as formatting pattern. If format
     * is empty, uses <var>date_format</var> blog setting.
     *
     * @param      record  $rs      Invisible parameter
     * @param      string  $format  The date format pattern
     * @param      string  $type    The type, (dt|upddt|creadt) defaults to post_dt
     *
     * @return     string  The date.
     */
    public static function getDate($rs, $format, $type = '')
    {
        if (!$format) {
            $format = $rs->core->blog->settings->system->date_format;
        }

        if ($type == 'upddt') {
            return dt::dt2str($format, $rs->post_upddt, $rs->post_tz);
        } elseif ($type == 'creadt') {
            return dt::dt2str($format, $rs->post_creadt, $rs->post_tz);
        }

        return dt::dt2str($format, $rs->post_dt);
    }

    /**
     * Returns post time with <var>$format</var> as formatting pattern. If format
     * is empty, uses <var>time_format</var> blog setting.
     *
     * @param      record  $rs      Invisible parameter
     * @param      string  $format  The time format pattern
     * @param      string  $type    The type, (dt|upddt|creadt) defaults to post_dt
     *
     * @return     string  The time.
     */
    public static function getTime($rs, $format, $type = '')
    {
        if (!$format) {
            $format = $rs->core->blog->settings->system->time_format;
        }

        if ($type == 'upddt') {
            return dt::dt2str($format, $rs->post_upddt, $rs->post_tz);
        } elseif ($type == 'creadt') {
            return dt::dt2str($format, $rs->post_creadt, $rs->post_tz);
        }

        return dt::dt2str($format, $rs->post_dt);
    }

    /**
     * Returns author common name using user_id, user_name, user_firstname and
     * user_displayname fields.
     *
     * @param      record  $rs      Invisible parameter
     *
     * @return     string  The author common name.
     */
    public static function getAuthorCN($rs)
    {
        return dcUtils::getUserCN($rs->user_id, $rs->user_name,
            $rs->user_firstname, $rs->user_displayname);
    }

    /**
     * Returns author common name with a link if he specified one in its preferences.
     *
     * @param      record  $rs      Invisible parameter
     *
     * @return     string
     */
    public static function getAuthorLink($rs)
    {
        $res = '%1$s';
        $url = $rs->user_url;
        if ($url) {
            $res = '<a href="%2$s">%1$s</a>';
        }

        return sprintf($res, html::escapeHTML($rs->getAuthorCN()), html::escapeHTML($url));
    }

    /**
     * Returns author e-mail address. If <var>$encoded</var> is true, "@" sign is
     * replaced by "%40" and "." by "%2e".
     *
     * @param      record  $rs       Invisible parameter
     * @param      bool    $encoded  Encode address
     *
     * @return     string  The author email.
     */
    public static function getAuthorEmail($rs, $encoded = true)
    {
        if ($encoded) {
            return strtr($rs->user_email, ['@' => '%40', '.' => '%2e']);
        }

        return $rs->user_email;
    }

    /**
     * Gets the post feed unique id.
     *
     * @param      record  $rs       Invisible parameter
     *
     * @return     string  The feed id.
     */
    public static function getFeedID($rs)
    {
        return 'urn:md5:' . md5($rs->core->blog->uid . $rs->post_id);
    }

    /**
     * Returns trackback RDF information block in HTML comment.
     *
     * @param      record  $rs       Invisible parameter
     * @param      string  $format   The format (html|xml)
     *
     * @return     string
     */
    public static function getTrackbackData($rs, $format = 'html')
    {
        return
        ($format == 'xml' ? "<![CDATA[>\n" : '') .
        "<!--\n" .
        '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"' . "\n" .
        '  xmlns:dc="http://purl.org/dc/elements/1.1/"' . "\n" .
        '  xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">' . "\n" .
        "<rdf:Description\n" .
        '  rdf:about="' . $rs->getURL() . '"' . "\n" .
        '  dc:identifier="' . $rs->getURL() . '"' . "\n" .
        '  dc:title="' . htmlspecialchars($rs->post_title, ENT_COMPAT, 'UTF-8') . '"' . "\n" .
        '  trackback:ping="' . $rs->getTrackbackLink() . '" />' . "\n" .
            "</rdf:RDF>\n" .
            ($format == 'xml' ? '<!]]><!--' : '') .
            "-->\n";
    }

    /**
     * Gets the post trackback full URL.
     *
     * @param      record  $rs       Invisible parameter
     *
     * @return     string  The trackback link.
     */
    public static function getTrackbackLink($rs)
    {
        return $rs->core->blog->url . $rs->core->url->getURLFor('trackback', $rs->post_id);
    }

    /**
     * Returns post content. If <var>$absolute_urls</var> is true, appends full
     * blog URL to each relative post URLs.
     *
     * @param      record  $rs              Invisible parameter
     * @param      bool    $absolute_urls   With absolute URLs
     *
     * @return     string  The content.
     */
    public static function getContent($rs, $absolute_urls = false)
    {
        if ($absolute_urls) {
            return html::absoluteURLs($rs->post_content_xhtml, $rs->getURL());
        }

        return $rs->post_content_xhtml;
    }

    /**
     * Returns post excerpt. If <var>$absolute_urls</var> is true, appends full
     * blog URL to each relative post URLs.
     *
     * @param      record  $rs              Invisible parameter
     * @param      bool    $absolute_urls   With absolute URLs
     *
     * @return     string  The excerpt.
     */
    public static function getExcerpt($rs, $absolute_urls = false)
    {
        if ($absolute_urls) {
            return html::absoluteURLs($rs->post_excerpt_xhtml, $rs->getURL());
        }

        return $rs->post_excerpt_xhtml;
    }

    /**
     * Returns post media count using a subquery.
     *
     * @param      record  $rs              Invisible parameter
     * @param      mixed   $link_type  The link type
     *
     * @return     integer Number of media.
     */
    public static function countMedia($rs, $link_type = null)
    {
        if (isset($rs->_nb_media[$rs->index()])) {
            return $rs->_nb_media[$rs->index()];
        }
        $strReq = 'SELECT count(media_id) ' .
            'FROM ' . $rs->core->prefix . 'post_media ' .
            'WHERE post_id = ' . (integer) $rs->post_id . ' ';
        if ($link_type != null) {
            $strReq .= "AND link_type = '" . $rs->core->con->escape($link_type) . "'";
        }

        $res                         = (integer) $rs->core->con->select($strReq)->f(0);
        $rs->_nb_media[$rs->index()] = $res;

        return $res;
    }

    /**
     * Returns true if current category if in given cat_url subtree
     *
     * @param      record   $rs       Invisible parameter
     * @param      string   $cat_url  The cat url
     *
     * @return     boolean  true if current cat is in given cat subtree
     */
    public static function underCat($rs, $cat_url)
    {
        return $rs->core->blog->IsInCatSubtree($rs->cat_url, $cat_url);
    }
}

/**
@ingroup DC_CORE
@brief Dotclear comment record helpers.

This class adds new methods to database comment results.
You can call them on every record comming from dcBlog::getComments and similar
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
     * @param      record  $rs      Invisible parameter
     * @param      string  $format  The date format pattern
     * @param      string  $type    The type, (dt|upddt) defaults to comment_dt
     *
     * @return     string  The date.
     */
    public static function getDate($rs, $format, $type = '')
    {
        if (!$format) {
            $format = $rs->core->blog->settings->system->date_format;
        }

        if ($type == 'upddt') {
            return dt::dt2str($format, $rs->comment_upddt, $rs->comment_tz);
        }

        return dt::dt2str($format, $rs->comment_dt);
    }

    /**
     * Returns comment time with <var>$format</var> as formatting pattern. If
     * format is empty, uses <var>time_format</var> blog setting.
     *
     * @param      record  $rs      Invisible parameter
     * @param      string  $format  The date format pattern
     * @param      string  $type    The type, (dt|upddt) defaults to comment_dt
     *
     * @return     string  The time.
     */
    public static function getTime($rs, $format, $type = '')
    {
        if (!$format) {
            $format = $rs->core->blog->settings->system->time_format;
        }

        if ($type == 'upddt') {
            return dt::dt2str($format, $rs->comment_updt, $rs->comment_tz);
        }

        return dt::dt2str($format, $rs->comment_dt);
    }

    /**
     * Returns comment timestamp.
     *
     * @param      record  $rs      Invisible parameter
     * @param      string  $type    The type, (dt|upddt) defaults to comment_dt
     *
     * @return     integer The timestamp.
     */
    public static function getTS($rs, $type = '')
    {
        if ($type == 'upddt') {
            return strtotime($rs->comment_upddt);
        }

        return strtotime($rs->comment_dt);
    }

    /**
     * Returns comment date formating according to the ISO 8601 standard.
     *
     * @param      record  $rs      Invisible parameter
     * @param      string  $type    The type, (dt|upddt) defaults to comment_dt
     *
     * @return     string  The iso 8601 date.
     */
    public static function getISO8601Date($rs, $type = '')
    {
        if ($type == 'upddt') {
            return dt::iso8601($rs->getTS($type) + dt::getTimeOffset($rs->comment_tz), $rs->comment_tz);
        }

        return dt::iso8601($rs->getTS(), $rs->comment_tz);
    }

    /**
     * Returns comment date formating according to RFC 822.
     *
     * @param      record  $rs      Invisible parameter
     * @param      string  $type    The type, (dt|upddt) defaults to comment_dt
     *
     * @return     string  The rfc 822 date.
     */
    public static function getRFC822Date($rs, $type = '')
    {
        if ($type == 'upddt') {
            return dt::rfc822($rs->getTS($type) + dt::getTimeOffset($rs->comment_tz), $rs->comment_tz);
        }

        return dt::rfc822($rs->getTS(), $rs->comment_tz);
    }

    /**
     * Returns comment content. If <var>$absolute_urls</var> is true, appends full
     * blog URL to each relative post URLs.
     *
     * @param      record  $rs              Invisible parameter
     * @param      bool    $absolute_urls   With absolute URLs
     *
     * @return     string  The content.
     */
    public static function getContent($rs, $absolute_urls = false)
    {
        $res = $rs->comment_content;

        if ($rs->core->blog->settings->system->comments_nofollow) {
            $res = preg_replace_callback('#<a(.*?href=".*?".*?)>#ms', ['self', 'noFollowURL'], $res);
        } else {
            $res = preg_replace_callback('#<a(.*?href=".*?".*?)>#ms', ['self', 'UgcURL'], $res);
        }

        if ($absolute_urls) {
            $res = html::absoluteURLs($res, $rs->getPostURL());
        }

        return $res;
    }

    private static function noFollowURL($m)
    {
        if (preg_match('/rel="ugc nofollow"/', $m[1])) {
            return $m[0];
        }

        return '<a' . $m[1] . ' rel="ugc nofollow">';
    }

    private static function UgcURL($m)
    {
        if (preg_match('/rel="ugc"/', $m[1])) {
            return $m[0];
        }

        return '<a' . $m[1] . ' rel="ugc">';
    }

    /**
     * Returns comment author link to his website if he specified one.
     *
     * @param      record  $rs     Invisible parameter
     *
     * @return     mixed  The author url.
     */
    public static function getAuthorURL($rs)
    {
        if (trim($rs->comment_site)) {
            return trim($rs->comment_site);
        }
    }

    /**
     * Returns comment post full URL.
     *
     * @param      record  $rs     Invisible parameter
     *
     * @return     string  The comment post url.
     */
    public static function getPostURL($rs)
    {
        return $rs->core->blog->url . $rs->core->getPostPublicURL(
            $rs->post_type, html::sanitizeURL($rs->post_url)
        );
    }

    /**
     * Returns comment author name in a link to his website if he specified one.
     *
     * @param      record  $rs     Invisible parameter
     *
     * @return     string  The author link.
     */
    public static function getAuthorLink($rs)
    {
        $res = '%1$s';
        $url = $rs->getAuthorURL();
        if ($url) {
            $res = '<a href="%2$s" rel="%3$s">%1$s</a>';
        }

        $rel = 'ugc';
        if ($rs->core->blog->settings->system->comments_nofollow) {
            $rel .= ' nofollow';
        }

        return sprintf($res, html::escapeHTML($rs->comment_author), html::escapeHTML($url), $rel);
    }

    /**
     * Returns comment author e-mail address. If <var>$encoded</var> is true,
     * "@" sign is replaced by "%40" and "." by "%2e".
     *
     * @param      record  $rs       Invisible parameter
     * @param      bool    $encoded  Encode address
     *
     * @return     string  The email.
     */
    public static function getEmail($rs, $encoded = true)
    {
        return $encoded ? strtr($rs->comment_email, ['@' => '%40', '.' => '%2e']) : $rs->comment_email;
    }

    /**
     * Returns trackback site title if comment is a trackback.
     *
     * @param      record  $rs       Invisible parameter
     *
     * @return     mixed  The trackback title.
     */
    public static function getTrackbackTitle($rs)
    {
        if ($rs->comment_trackback == 1 && preg_match('|<p><strong>(.*?)</strong></p>|msU', $rs->comment_content,
                $match)) {
            return html::decodeEntities($match[1]);
        }
    }

    /**
     * Returns trackback content if comment is a trackback.
     *
     * @param      record  $rs       Invisible parameter
     *
     * @return     mixed  The trackback content.
     */
    public static function getTrackbackContent($rs)
    {
        if ($rs->comment_trackback == 1) {
            return preg_replace('|<p><strong>.*?</strong></p>|msU', '',
                $rs->comment_content);
        }
    }

    /**
     * Returns comment feed unique ID.
     *
     * @param      record  $rs       Invisible parameter
     *
     * @return     string  The feed id.
     */
    public static function getFeedID($rs)
    {
        return 'urn:md5:' . md5($rs->core->blog->uid . $rs->comment_id);
    }

    /**
     * Determines whether the specified comment is from the post author.
     *
     * @param      record  $rs       Invisible parameter
     *
     * @return     bool    True if the specified comment is from the post author, False otherwise.
     */
    public static function isMe($rs)
    {
        $user_prefs = new dcPrefs($rs->core, $rs->user_id, 'profile');
        $user_prefs->addWorkspace('profile');
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
@brief Dotclear dates record helpers.

This class adds new methods to database dates results.
You can call them on every record comming from dcBlog::getDates.

@warning You should not give the first argument (usualy $rs) of every described
function.
 */
class rsExtDates
{
    /**
     * Convert date to timestamp
     *
     * @param      record  $rs       Invisible parameter
     *
     * @return     integer
     */
    public static function ts($rs)
    {
        return strtotime($rs->dt);
    }

    /**
     * Get date year
     *
     * @param      record  $rs       Invisible parameter
     *
     * @return     string
     */
    public static function year($rs)
    {
        return date('Y', strtotime($rs->dt));
    }

    /**
     * Get date month
     *
     * @param      record  $rs       Invisible parameter
     *
     * @return     string
     */
    public static function month($rs)
    {
        return date('m', strtotime($rs->dt));
    }

    /**
     * Get date day
     *
     * @param      record  $rs       Invisible parameter
     *
     * @return     string
     */
    public static function day($rs)
    {
        return date('d', strtotime($rs->dt));
    }

    /**
     * Returns date month archive full URL.
     *
     * @param      record  $rs       Invisible parameter
     * @param      dcCore  $core     The core
     *
     * @return     string
     */
    public static function url($rs, dcCore $core)
    {
        $url = date('Y/m', strtotime($rs->dt));

        return $core->blog->url . $core->url->getURLFor('archive', $url);
    }

    /**
     * Returns whether date is the first of year.
     *
     * @param      record  $rs       Invisible parameter
     *
     * @return     bool
     */
    public static function yearHeader($rs)
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
     * @param      record  $rs       Invisible parameter
     *
     * @return     bool
     */
    public static function yearFooter($rs)
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
@brief Dotclear dates record helpers.

This class adds new methods to database dates results.
You can call them on every record comming from dcAuth::checkUser and
dcCore::getUsers.

@warning You should not give the first argument (usualy $rs) of every described
function.
 */
class rsExtUser
{
    private static $sortfield;
    private static $sortsign;

    /**
     * Returns a user option.
     *
     * @param      record  $rs       Invisible parameter
     * @param      string  $name     The name of option
     *
     * @return     mixed
     */
    public static function option($rs, $name)
    {
        $options = self::options($rs);

        if (isset($options[$name])) {
            return $options[$name];
        }
    }

    /**
     * Returns all user options.
     *
     * @param      record  $rs       Invisible parameter
     *
     * @return     array
     */
    public static function options($rs)
    {
        $options = @unserialize($rs->user_options);
        if (is_array($options)) {
            return $options;
        }

        return [];
    }

    /**
     * Converts this record to a {@link extStaticRecord} instance.
     *
     * @param      record  $rs       Invisible parameter
     *
     * @return     extStaticRecord  The extent static record.
     */
    public static function toExtStatic($rs)
    {
        if ($rs instanceof extStaticRecord) {
            return $rs;
        }

        return new extStaticRecord($rs);
    }
}

class rsExtBlog
{
    private static $sortfield;
    private static $sortsign;

    /**
     * Converts this record to a {@link extStaticRecord} instance.
     *
     * @param      record  $rs       Invisible parameter
     *
     * @return     extStaticRecord  The extent static record.
     */
    public static function toExtStatic($rs)
    {
        if ($rs instanceof extStaticRecord) {
            return $rs;
        }

        return new extStaticRecord($rs);
    }
}

class extStaticRecord extends staticRecord
{
    private $sortfield;
    private $sortsign;

    public function __construct($rs)
    {
        parent::__construct($rs->__data, $rs->__info);
    }

    /**
     * Lexically sort.
     *
     * @param      string  $field  The field
     * @param      string  $order  The order
     */
    public function lexicalSort($field, $order = 'asc')
    {
        $this->sortfield = $field;
        $this->sortsign  = strtolower($order) == 'asc' ? 1 : -1;

        usort($this->__data, [$this, 'lexicalSortCallback']);

        $this->sortfield = null;
        $this->sortsign  = null;
    }
    private function lexicalSortCallback($a, $b)
    {
        if (!isset($a[$this->sortfield]) || !isset($b[$this->sortfield])) {
            return 0;
        }

        $a = $a[$this->sortfield];
        $b = $b[$this->sortfield];

        # Integer values
        if ($a == (string) (integer) $a && $b == (string) (integer) $b) {
            $a = (integer) $a;
            $b = (integer) $b;

            return ($a - $b) * $this->sortsign;
        }

        return strcoll(strtolower(dcUtils::removeDiacritics($a)), strtolower(dcUtils::removeDiacritics($b))) * $this->sortsign;
    }
}
