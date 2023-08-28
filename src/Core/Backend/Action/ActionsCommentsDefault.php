<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend\Action;

use dcBlog;
use dcCore;
use Dotclear\Core\Core;
use Dotclear\Core\Backend\Notices;
use Dotclear\Plugin\antispam\Filters\Ip as dcFilterIP;
use Dotclear\Plugin\antispam\Filters\IpV6 as dcFilterIPv6;
use Exception;

class ActionsCommentsDefault
{
    /**
     * Set comments actions
     *
     * @param      ActionsComments  $ap
     */
    public static function adminCommentsActionsPage(ActionsComments $ap)
    {
        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_PUBLISH,
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)) {
            $ap->addAction(
                [__('Status') => [
                    __('Publish')         => 'publish',
                    __('Unpublish')       => 'unpublish',
                    __('Mark as pending') => 'pending',
                    __('Mark as junk')    => 'junk',
                ]],
                [self::class, 'doChangeCommentStatus']
            );
        }

        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_DELETE,
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)) {
            $ap->addAction(
                [__('Delete') => [
                    __('Delete') => 'delete', ]],
                [self::class, 'doDeleteComment']
            );
        }

        $ip_filter_active = false;
        if (dcCore::app()->blog->settings->antispam->antispam_filters !== null) {
            $filters_opt = dcCore::app()->blog->settings->antispam->antispam_filters;
            if (is_array($filters_opt)) {
                $filterActive     = fn ($name) => isset($filters_opt[$name]) && is_array($filters_opt[$name]) && $filters_opt[$name][0] == 1;
                $ip_filter_active = $filterActive('dcFilterIP') || $filterActive('dcFilterIPv6');
            }
        }

        if ($ip_filter_active) {
            $blocklist_actions = [__('Blocklist IP') => 'blocklist'];
            if (dcCore::app()->auth->isSuperAdmin()) {
                $blocklist_actions[__('Blocklist IP (global)')] = 'blocklist_global';
            }

            $ap->addAction(
                [__('IP address') => $blocklist_actions],
                [self::class, 'doBlocklistIP']
            );
        }
    }

    /**
     * Does a change comment status.
     *
     * @param      ActionsComments  $ap
     *
     * @throws     Exception                If no comment selected
     */
    public static function doChangeCommentStatus(ActionsComments $ap)
    {
        $ids = $ap->getIDs();
        if (empty($ids)) {
            throw new Exception(__('No comment selected'));
        }

        $status = match ($ap->getAction()) {
            'unpublish' => dcBlog::COMMENT_UNPUBLISHED,
            'pending'   => dcBlog::COMMENT_PENDING,
            'junk'      => dcBlog::COMMENT_JUNK,
            default     => dcBlog::COMMENT_PUBLISHED,
        };

        dcCore::app()->blog->updCommentsStatus($ids, $status);

        Notices::addSuccessNotice(__('Selected comments have been successfully updated.'));
        $ap->redirect(true);
    }

    /**
     * Does a delete comment.
     *
     * @param      ActionsComments  $ap
     *
     * @throws     Exception                If no comment selected
     */
    public static function doDeleteComment(ActionsComments $ap)
    {
        $ids = $ap->getIDs();
        if (empty($ids)) {
            throw new Exception(__('No comment selected'));
        }
        // Backward compatibility
        foreach ($ids as $id) {
            # --BEHAVIOR-- adminBeforeCommentDelete -- string
            Core::behavior()->callBehavior('adminBeforeCommentDelete', $id);
        }

        # --BEHAVIOR-- adminBeforeCommentsDelete -- array<int,string>
        Core::behavior()->callBehavior('adminBeforeCommentsDelete', $ids);

        dcCore::app()->blog->delComments($ids);

        Notices::addSuccessNotice(__('Selected comments have been successfully deleted.'));
        $ap->redirect(false);
    }

    /**
     * Add comments IP in an antispam blacklist.
     *
     * @param      ActionsComments  $ap
     *
     * @throws     Exception                If no comment selected
     */
    public static function doBlocklistIP(ActionsComments $ap)
    {
        $ids = $ap->getIDs();
        if (empty($ids)) {
            throw new Exception(__('No comment selected'));
        }

        $action = $ap->getAction();
        $global = !empty($action) && $action == 'blocklist_global' && dcCore::app()->auth->isSuperAdmin();

        $filters_opt  = dcCore::app()->blog->settings->antispam->antispam_filters;
        $filterActive = fn ($name) => isset($filters_opt[$name]) && is_array($filters_opt[$name]) && $filters_opt[$name][0] == 1;
        $filters      = [
            'v4' => $filterActive('dcFilterIP'),
            'v6' => $filterActive('dcFilterIPv6'),
        ];

        $count = 0;

        if (is_array($filters_opt)) {
            $rs = $ap->getRS();
            while ($rs->fetch()) {
                if (filter_var($rs->comment_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
                    // IP is an IPv6
                    if ($filters['v6']) {
                        (new dcFilterIPv6())->addIP('blackv6', $rs->comment_ip, $global);
                        $count++;
                    }
                } else {
                    // Assume that IP is IPv4
                    if ($filters['v4']) {
                        (new dcFilterIP())->addIP('black', $rs->comment_ip, $global);
                        $count++;
                    }
                }
            }

            if ($count) {
                Notices::addSuccessNotice(__('IP addresses for selected comments have been blocklisted.'));
            }
        }
        $ap->redirect(true);
    }
}
