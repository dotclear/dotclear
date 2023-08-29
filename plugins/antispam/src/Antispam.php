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

use ArrayObject;
use dcAuth;
use dcBlog;
use dcCore;
use Dotclear\Core\Core;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use initAntispam;

class Antispam extends initAntispam
{
    private static array $spamfilters = [];

    // Properties

    /**
     * Antispam Filters
     *
     * @var SpamFilters
     */
    public static $filters;

    /**
     * Initializes the filters.
     */
    public static function initFilters()
    {
        if (!empty(self::$spamfilters)) {
            return;
        }

        // deprecated since 2.28
        if (!empty(dcCore::app()->spamfilters)) {
            foreach (dcCore::app()->spamfilters as $spamfilter) {
                if (is_subclass_of($spamfilter, SpamFilter::class)) {
                    self::$spamfilters[] = $spamfilter;
                }
            }
        }

        $spamfilters = new ArrayObject();
        # --BEHAVIOR-- AntispamInitFilters -- ArrayObject
        Core::behavior()->callBehavior('AntispamInitFilters', $spamfilters);

        foreach ($spamfilters as $spamfilter) {
            if (is_subclass_of($spamfilter, SpamFilter::class)) {
                self::$spamfilters[] = $spamfilter;
            }
        }

        self::$filters = new SpamFilters();
        self::$filters->init(self::$spamfilters);
    }

    /**
     * Determines whether the specified Cursor content is spam.
     *
     * The Cursor may be modified (or deleted) according to the result
     *
     * @param      Cursor  $cur    The current
     */
    public static function isSpam(Cursor $cur)
    {
        self::initFilters();
        self::$filters->isSpam($cur);
    }

    /**
     * Train the filters with current record
     *
     * @param      dcBlog        $blog   The blog
     * @param      Cursor        $cur    The Cursor
     * @param      MetaRecord      $rs     The comment record
     */
    public static function trainFilters(dcBlog $blog, Cursor $cur, MetaRecord $rs): void
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
            $filter_name = $rs->exists('comment_spam_filter') ? $rs->comment_spam_filter : '';

            self::initFilters();
            self::$filters->trainFilters($rs, $status, $filter_name);
        }
    }

    /**
     * Get filter status message
     *
     * @param      MetaRecord      $rs     The comment record
     *
     * @return     string
     */
    public static function statusMessage(MetaRecord $rs): string
    {
        if ($rs->exists('comment_status') && $rs->comment_status == dcBlog::COMMENT_JUNK) {
            $filter_name = $rs->exists('comment_spam_filter') ? $rs->comment_spam_filter : '';

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

            return '</span></a> <a href="' . Core::backend()->url->get('admin.comments', ['status' => '-2']) . '"><span class="db-icon-title-spam">' .
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
        return My::jsLoad('dashboard');
    }

    /**
     * Counts the number of spam.
     *
     * @return     int   Number of spam.
     */
    public static function countSpam(): int
    {
        return (int) Core::blog()->getComments(['comment_status' => dcBlog::COMMENT_JUNK], true)->f(0);
    }

    /**
     * Counts the number of published comments.
     *
     * @return     int   Number of published comments.
     */
    public static function countPublishedComments(): int
    {
        return (int) Core::blog()->getComments(['comment_status' => dcBlog::COMMENT_PUBLISHED], true)->f(0);
    }

    /**
     * Delete all spam older than a given date, else every
     *
     * @param      null|string  $beforeDate  The before date
     */
    public static function delAllSpam(?string $beforeDate = null): void
    {
        $strReq = 'SELECT comment_id ' .
        'FROM ' . Core::con()->prefix() . dcBlog::COMMENT_TABLE_NAME . ' C ' .
        'JOIN ' . Core::con()->prefix() . dcBlog::POST_TABLE_NAME . ' P ON P.post_id = C.post_id ' .
        "WHERE blog_id = '" . Core::con()->escape(Core::blog()->id) . "' " .
            'AND comment_status = ' . (string) dcBlog::COMMENT_JUNK . ' ';
        if ($beforeDate) {
            $strReq .= 'AND comment_dt < \'' . $beforeDate . '\' ';
        }

        $rs = new MetaRecord(Core::con()->select($strReq));
        $r  = [];
        while ($rs->fetch()) {
            $r[] = (int) $rs->comment_id;
        }

        if (empty($r)) {
            return;
        }

        $strReq = 'DELETE FROM ' . Core::con()->prefix() . dcBlog::COMMENT_TABLE_NAME . ' ' .
        'WHERE comment_id ' . Core::con()->in($r) . ' ';

        Core::con()->execute($strReq);
    }

    /**
     * Gets the user code (used for antispam feeds URL).
     *
     * @return     string  The user code.
     */
    public static function getUserCode(): string
    {
        $code = pack('a32', Core::auth()->userID()) .
        hash(DC_CRYPT_ALGO, Core::auth()->cryptLegacy(Core::auth()->getInfo('user_pwd')));

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
        'FROM ' . Core::con()->prefix() . dcAuth::USER_TABLE_NAME . ' ' .
        "WHERE user_id = '" . Core::con()->escape($user_id) . "' ";

        $rs = new MetaRecord(Core::con()->select($strReq));

        if ($rs->isEmpty()) {
            return false;
        }

        if (hash(DC_CRYPT_ALGO, Core::auth()->cryptLegacy($rs->user_pwd)) != $pwd) {
            return false;
        }

        $permissions = Core::blogs()->getBlogPermissions(Core::blog()->id);

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
        $dateLastPurge = My::settings()->antispam_date_last_purge;
        if ($dateLastPurge === null) {
            $init = true;
            My::settings()->put('antispam_date_last_purge', $defaultDateLastPurge, 'integer', 'Antispam Date Last Purge (unix timestamp)', true, false);
            $dateLastPurge = $defaultDateLastPurge;
        }
        $moderationTTL = My::settings()->antispam_moderation_ttl;
        if ($moderationTTL === null) {
            My::settings()->put('antispam_moderation_ttl', $defaultModerationTTL, 'integer', 'Antispam Moderation TTL (days)', true, false);
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
                My::settings()->put('antispam_date_last_purge', time(), null, null, true, false);
            }
            $date = date('Y-m-d H:i:s', time() - $moderationTTL * 86400);
            Antispam::delAllSpam($date);
        }
    }
}
