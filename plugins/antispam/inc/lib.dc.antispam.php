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
if (!defined('DC_RC_PATH')) {
    return;
}

class dcAntispam
{
    public static $filters;

    public static function initFilters()
    {
        if (!isset(dcCore::app()->spamfilters)) {
            return;
        }

        self::$filters = new dcSpamFilters();
        self::$filters->init(dcCore::app()->spamfilters);
    }

    public static function isSpam($cur)
    {
        self::initFilters();
        self::$filters->isSpam($cur);
    }

    public static function trainFilters($blog, $cur, $rs)
    {
        $status = null;
        # From ham to spam
        if ($rs->comment_status != -2 && $cur->comment_status == -2) {
            $status = 'spam';
        }

        # From spam to ham
        if ($rs->comment_status == -2 && $cur->comment_status == 1) {
            $status = 'ham';
        }

        # the status of this comment has changed
        if ($status) {
            $filter_name = $rs->exists('comment_spam_filter') ? $rs->comment_spam_filter : null;

            self::initFilters();
            self::$filters->trainFilters($rs, $status, $filter_name);
        }
    }

    public static function statusMessage($rs)
    {
        if ($rs->exists('comment_status') && $rs->comment_status == -2) {
            $filter_name = $rs->exists('comment_spam_filter') ? $rs->comment_spam_filter : null;

            self::initFilters();

            return
            '<p><strong>' . __('This comment is a spam:') . '</strong> ' .
            self::$filters->statusMessage($rs, $filter_name) . '</p>';
        }
    }

    public static function dashboardIconTitle(dcCore $core = null)
    {
        if (($count = self::countSpam(dcCore::app())) > 0) {
            $str = ($count > 1) ? __('(including %d spam comments)') : __('(including %d spam comment)');

            return '</span></a> <a href="' . dcCore::app()->adminurl->get('admin.comments', ['status' => '-2']) . '"><span class="db-icon-title-spam">' .
            sprintf($str, $count);
        }

        return '';
    }

    public static function dashboardHeaders()
    {
        return dcPage::jsModuleLoad('antispam/js/dashboard.js');
    }

    public static function countSpam(dcCore $core = null)
    {
        return dcCore::app()->blog->getComments(['comment_status' => -2], true)->f(0);
    }

    public static function countPublishedComments(dcCore $core = null)
    {
        return dcCore::app()->blog->getComments(['comment_status' => 1], true)->f(0);
    }

    public static function delAllSpam(dcCore $core, $beforeDate = null)
    {
        $strReq = 'SELECT comment_id ' .
        'FROM ' . dcCore::app()->prefix . 'comment C ' .
        'JOIN ' . dcCore::app()->prefix . 'post P ON P.post_id = C.post_id ' .
        "WHERE blog_id = '" . dcCore::app()->con->escape(dcCore::app()->blog->id) . "' " .
            'AND comment_status = -2 ';
        if ($beforeDate) {
            $strReq .= 'AND comment_dt < \'' . $beforeDate . '\' ';
        }

        $rs = dcCore::app()->con->select($strReq);
        $r  = [];
        while ($rs->fetch()) {
            $r[] = (int) $rs->comment_id;
        }

        if (empty($r)) {
            return;
        }

        $strReq = 'DELETE FROM ' . dcCore::app()->prefix . 'comment ' .
        'WHERE comment_id ' . dcCore::app()->con->in($r) . ' ';

        dcCore::app()->con->execute($strReq);
    }

    public static function getUserCode(dcCore $core = null)
    {
        $code = pack('a32', dcCore::app()->auth->userID()) .
        hash(DC_CRYPT_ALGO, dcCore::app()->auth->cryptLegacy(dcCore::app()->auth->getInfo('user_pwd')));

        return bin2hex($code);
    }

    public static function checkUserCode(dcCore $core, $code)
    {
        $code = pack('H*', $code);

        $user_id = trim((string) @pack('a32', substr($code, 0, 32)));
        $pwd     = substr($code, 32);

        if ($user_id === '' || $pwd === '') {
            return false;
        }

        $strReq = 'SELECT user_id, user_pwd ' .
        'FROM ' . dcCore::app()->prefix . 'user ' .
        "WHERE user_id = '" . dcCore::app()->con->escape($user_id) . "' ";

        $rs = dcCore::app()->con->select($strReq);

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

    public static function purgeOldSpam(dcCore $core = null)
    {
        $defaultDateLastPurge = time();
        $defaultModerationTTL = '7';
        $init                 = false;

        // settings
        dcCore::app()->blog->settings->addNamespace('antispam');

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
            dcAntispam::delAllSpam(dcCore::app(), $date);
        }
    }
}
