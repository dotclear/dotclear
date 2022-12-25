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
class dcAntispam
{
    // Constants

    /**
     * Spam rules table name
     *
     * @var        string
     */
    public const SPAMRULE_TABLE_NAME = 'spamrule';

    // Properties

    /**
     * Antispam Filters
     *
     * @var dcSpamFilters
     */
    public static $filters;

    /**
     * Initializes the filters.
     */
    public static function initFilters()
    {
        if (!isset(dcCore::app()->spamfilters)) {
            return;
        }

        self::$filters = new dcSpamFilters();
        self::$filters->init(dcCore::app()->spamfilters);
    }

    /**
     * Determines whether the specified cursor content is spam.
     *
     * The cursor may be modified (or deleted) according to the result
     *
     * @param      cursor  $cur    The current
     */
    public static function isSpam(cursor $cur)
    {
        self::initFilters();
        self::$filters->isSpam($cur);
    }

    /**
     * Train the filters with current record
     *
     * @param      dcBlog        $blog   The blog
     * @param      cursor        $cur    The cursor
     * @param      dcRecord      $rs     The comment record
     */
    public static function trainFilters(dcBlog $blog, cursor $cur, dcRecord $rs): void
    {
        $status = null;
        // From ham to spam
        if ($rs->comment_status != dcBlog::COMMENT_JUNK && $cur->comment_status == dcBlog::COMMENT_JUNK) {
            $status = 'spam';
        }

        // From spam to ham
        if ($rs->comment_status == dcBlog::COMMENT_JUNK && $cur->comment_status == dcBlog::COMMENT_PUBLISHED) {
            $status = 'ham';
        }

        // the status of this comment has changed
        if ($status) {
            $filter_name = $rs->exists('comment_spam_filter') ? $rs->comment_spam_filter : null;

            self::initFilters();
            self::$filters->trainFilters($rs, $status, $filter_name);
        }
    }

    /**
     * Get filter status message
     *
     * @param      dcRecord      $rs     The comment record
     *
     * @return     string
     */
    public static function statusMessage(dcRecord $rs): string
    {
        if ($rs->exists('comment_status') && $rs->comment_status == dcBlog::COMMENT_JUNK) {
            $filter_name = $rs->exists('comment_spam_filter') ? $rs->comment_spam_filter : null;

            self::initFilters();

            return
            '<p><strong>' . __('This comment is a spam:') . '</strong> ' .
            self::$filters->statusMessage($rs, $filter_name) . '</p>';
        }

        return '';
    }

    /**
     * Return additional information about existing spams
     *
     * @return     string
     */
    public static function dashboardIconTitle(): string
    {
        if (($count = self::countSpam()) > 0) {
            $str = ($count > 1) ? __('(including %d spam comments)') : __('(including %d spam comment)');

            return '</span></a> <a href="' . dcCore::app()->adminurl->get('admin.comments', ['status' => '-2']) . '"><span class="db-icon-title-spam">' .
            sprintf($str, $count);
        }

        return '';
    }

    /**
     * Load antispam dashboard script
     *
     * @return     string
     */
    public static function dashboardHeaders(): string
    {
        return dcPage::jsModuleLoad('antispam/js/dashboard.js');
    }

    /**
     * Counts the number of spam.
     *
     * @return     int   Number of spam.
     */
    public static function countSpam(): int
    {
        return dcCore::app()->blog->getComments(['comment_status' => dcBlog::COMMENT_JUNK], true)->f(0);
    }

    /**
     * Counts the number of published comments.
     *
     * @return     int   Number of published comments.
     */
    public static function countPublishedComments(): int
    {
        return dcCore::app()->blog->getComments(['comment_status' => dcBlog::COMMENT_PUBLISHED], true)->f(0);
    }

    /**
     * Delete all spam older than a given date, else every
     *
     * @param      null|string  $beforeDate  The before date
     */
    public static function delAllSpam(?string $beforeDate = null): void
    {
        $strReq = 'SELECT comment_id ' .
        'FROM ' . dcCore::app()->prefix . dcBlog::COMMENT_TABLE_NAME . ' C ' .
        'JOIN ' . dcCore::app()->prefix . dcBlog::POST_TABLE_NAME . ' P ON P.post_id = C.post_id ' .
        "WHERE blog_id = '" . dcCore::app()->con->escape(dcCore::app()->blog->id) . "' " .
            'AND comment_status = ' . (string) dcBlog::COMMENT_JUNK . ' ';
        if ($beforeDate) {
            $strReq .= 'AND comment_dt < \'' . $beforeDate . '\' ';
        }

        $rs = new dcRecord(dcCore::app()->con->select($strReq));
        $r  = [];
        while ($rs->fetch()) {
            $r[] = (int) $rs->comment_id;
        }

        if (empty($r)) {
            return;
        }

        $strReq = 'DELETE FROM ' . dcCore::app()->prefix . dcBlog::COMMENT_TABLE_NAME . ' ' .
        'WHERE comment_id ' . dcCore::app()->con->in($r) . ' ';

        dcCore::app()->con->execute($strReq);
    }

    /**
     * Gets the user code (used for antispam feeds URL).
     *
     * @return     string  The user code.
     */
    public static function getUserCode(): string
    {
        $code = pack('a32', dcCore::app()->auth->userID()) .
        hash(DC_CRYPT_ALGO, dcCore::app()->auth->cryptLegacy(dcCore::app()->auth->getInfo('user_pwd')));

        return bin2hex($code);
    }

    /**
     * Check if a user code is valid and if so return the user ID
     *
     * @param      string  $code   The code
     *
     * @return     bool|string
     */
    public static function checkUserCode(string $code)
    {
        $code = pack('H*', $code);

        $user_id = trim((string) @pack('a32', substr($code, 0, 32)));
        $pwd     = substr($code, 32);

        if ($user_id === '' || $pwd === '') {
            return false;
        }

        $strReq = 'SELECT user_id, user_pwd ' .
        'FROM ' . dcCore::app()->prefix . dcAuth::USER_TABLE_NAME . ' ' .
        "WHERE user_id = '" . dcCore::app()->con->escape($user_id) . "' ";

        $rs = new dcRecord(dcCore::app()->con->select($strReq));

        if ($rs->isEmpty()) {
            return false;
        }

        if (hash(DC_CRYPT_ALGO, dcCore::app()->auth->cryptLegacy($rs->user_pwd)) != $pwd) {
            return false;
        }

        $permissions = dcCore::app()->getBlogPermissions(dcCore::app()->blog->id);

        if (empty($permissions[$rs->user_id])) {
            return false;
        }

        return $rs->user_id;
    }

    /**
     * Purge old spam
     */
    public static function purgeOldSpam(): void
    {
        $defaultDateLastPurge = time();
        $defaultModerationTTL = '7';
        $init                 = false;

        // settings
        $dateLastPurge = dcCore::app()->blog->settings->antispam->antispam_date_last_purge;
        if ($dateLastPurge === null) {
            $init = true;
            dcCore::app()->blog->settings->antispam->put('antispam_date_last_purge', $defaultDateLastPurge, 'integer', 'Antispam Date Last Purge (unix timestamp)', true, false);
            $dateLastPurge = $defaultDateLastPurge;
        }
        $moderationTTL = dcCore::app()->blog->settings->antispam->antispam_moderation_ttl;
        if ($moderationTTL === null) {
            dcCore::app()->blog->settings->antispam->put('antispam_moderation_ttl', $defaultModerationTTL, 'integer', 'Antispam Moderation TTL (days)', true, false);
            $moderationTTL = $defaultModerationTTL;
        }

        if ($moderationTTL < 0) {
            // disabled
            return;
        }

        // we call the purge every day
        if ((time() - $dateLastPurge) > (86400)) {
            // update dateLastPurge
            if (!$init) {
                dcCore::app()->blog->settings->antispam->put('antispam_date_last_purge', time(), null, null, true, false);
            }
            $date = date('Y-m-d H:i:s', time() - $moderationTTL * 86400);
            dcAntispam::delAllSpam($date);
        }
    }
}
