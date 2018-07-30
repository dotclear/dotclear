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

class dcAntispam
{
    public static $filters;

    public static function initFilters()
    {
        global $core;

        if (!isset($core->spamfilters)) {
            return;
        }

        self::$filters = new dcSpamFilters($core);
        self::$filters->init($core->spamfilters);
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

    public static function dashboardIcon($core, $icons)
    {
        if (($count = self::countSpam($core)) > 0) {
            $str = ($count > 1) ? __('(including %d spam comments)') : __('(including %d spam comment)');
            $icons['comments'][0] .= '</span></a> <a href="' . $core->adminurl->get('admin.comments', array('status' => '-2')) . '"><span class="db-icon-title-spam">' .
            sprintf($str, $count);
        }
    }

    public static function dashboardIconTitle($core)
    {
        if (($count = self::countSpam($core)) > 0) {
            $str = ($count > 1) ? __('(including %d spam comments)') : __('(including %d spam comment)');
            return '</span></a> <a href="' . $core->adminurl->get('admin.comments', array('status' => '-2')) . '"><span class="db-icon-title-spam">' .
            sprintf($str, $count);
        } else {
            return '';
        }
    }

    public static function dashboardHeaders()
    {
        return dcPage::jsLoad(urldecode(dcPage::getPF('antispam/js/dashboard.js')));
    }

    public static function countSpam($core)
    {
        return $core->blog->getComments(array('comment_status' => -2), true)->f(0);
    }

    public static function countPublishedComments($core)
    {
        return $core->blog->getComments(array('comment_status' => 1), true)->f(0);
    }

    public static function delAllSpam($core, $beforeDate = null)
    {
        $strReq =
        'SELECT comment_id ' .
        'FROM ' . $core->prefix . 'comment C ' .
        'JOIN ' . $core->prefix . 'post P ON P.post_id = C.post_id ' .
        "WHERE blog_id = '" . $core->con->escape($core->blog->id) . "' " .
            'AND comment_status = -2 ';
        if ($beforeDate) {
            $strReq .= 'AND comment_dt < \'' . $beforeDate . '\' ';
        }

        $rs = $core->con->select($strReq);
        $r  = array();
        while ($rs->fetch()) {
            $r[] = (integer) $rs->comment_id;
        }

        if (empty($r)) {
            return;
        }

        $strReq =
        'DELETE FROM ' . $core->prefix . 'comment ' .
        'WHERE comment_id ' . $core->con->in($r) . ' ';

        $core->con->execute($strReq);
    }

    public static function getUserCode($core)
    {
        $code =
        pack('a32', $core->auth->userID()) .
        pack('H*', $core->auth->cryptLegacy($core->auth->getInfo('user_pwd')));
        return bin2hex($code);
    }

    public static function checkUserCode($core, $code)
    {
        $code = pack('H*', $code);

        $user_id = trim(@pack('a32', substr($code, 0, 32)));
        $pwd     = @unpack('H*hex', substr($code, 32));

        if ($user_id === false || $pwd === false) {
            return false;
        }

        $pwd = $pwd['hex'];

        $strReq = 'SELECT user_id, user_pwd ' .
        'FROM ' . $core->prefix . 'user ' .
        "WHERE user_id = '" . $core->con->escape($user_id) . "' ";

        $rs = $core->con->select($strReq);

        if ($rs->isEmpty()) {
            return false;
        }

        if ($core->auth->cryptLegacy($rs->user_pwd) != $pwd) {
            return false;
        }

        $permissions = $core->getBlogPermissions($core->blog->id);

        if (empty($permissions[$rs->user_id])) {
            return false;
        }

        return $rs->user_id;
    }

    public static function purgeOldSpam($core)
    {
        $defaultDateLastPurge = time();
        $defaultModerationTTL = '7';
        $init                 = false;

        // settings
        $core->blog->settings->addNamespace('antispam');

        $dateLastPurge = $core->blog->settings->antispam->antispam_date_last_purge;
        if ($dateLastPurge === null) {
            $init = true;
            $core->blog->settings->antispam->put('antispam_date_last_purge', $defaultDateLastPurge, 'integer', 'Antispam Date Last Purge (unix timestamp)', true, false);
            $dateLastPurge = $defaultDateLastPurge;
        }
        $moderationTTL = $core->blog->settings->antispam->antispam_moderation_ttl;
        if ($moderationTTL === null) {
            $core->blog->settings->antispam->put('antispam_moderation_ttl', $defaultModerationTTL, 'integer', 'Antispam Moderation TTL (days)', true, false);
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
                $core->blog->settings->antispam->put('antispam_date_last_purge', time(), null, null, true, false);
            }
            $date = date('Y-m-d H:i:s', time() - $moderationTTL * 86400);
            dcAntispam::delAllSpam($core, $date);
        }
    }
}
