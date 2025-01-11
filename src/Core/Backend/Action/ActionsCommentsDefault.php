<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend\Action;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Plugin\antispam\Filters\Ip as dcFilterIP;
use Dotclear\Plugin\antispam\Filters\IpV6 as dcFilterIPv6;
use Exception;

/**
 * @brief   Handler for default actions on comments.
 */
class ActionsCommentsDefault
{
    /**
     * Set comments actions.
     *
     * @param   ActionsComments     $ap     The ActionsComments instance
     */
    public static function adminCommentsActionsPage(ActionsComments $ap): void
    {
        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_PUBLISH,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            $ap->addAction(
                [__('Status') => App::status()->comment()->action()],
                self::doChangeCommentStatus(...)
            );
        }

        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_DELETE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            $ap->addAction(
                [__('Delete') => [
                    __('Delete') => 'delete', ]],
                self::doDeleteComment(...)
            );
        }

        $ip_filter_active = false;
        if (App::blog()->settings()->antispam->antispam_filters !== null) {
            $filters_opt = App::blog()->settings()->antispam->antispam_filters;
            if (is_array($filters_opt)) {
                $filterActive     = fn ($name): bool => isset($filters_opt[$name]) && is_array($filters_opt[$name]) && $filters_opt[$name][0] == 1;
                $ip_filter_active = $filterActive('dcFilterIP') || $filterActive('dcFilterIPv6');
            }
        }

        if ($ip_filter_active) {
            $blocklist_actions = [__('Blocklist IP') => 'blocklist'];
            if (App::auth()->isSuperAdmin()) {
                $blocklist_actions[__('Blocklist IP (global)')] = 'blocklist_global';
            }

            $ap->addAction(
                [__('IP address') => $blocklist_actions],
                self::doBlocklistIP(...)
            );
        }
    }

    /**
     * Does a change comment status.
     *
     * @param   ActionsComments     $ap     The ActionsComments instance
     *
     * @throws  Exception   If no comment selected
     */
    public static function doChangeCommentStatus(ActionsComments $ap): void
    {
        $ids = $ap->getIDs();
        if ($ids === []) {
            throw new Exception(__('No comment selected'));
        }

        // unknown to published
        $status = App::status()->comment()->has((string) $ap->getAction()) ?
            App::status()->comment()->level((string) $ap->getAction()) :
            App::status()->comment()::PUBLISHED;

        App::blog()->updCommentsStatus($ids, $status);

        Notices::addSuccessNotice(__('Selected comments have been successfully updated.'));
        $ap->redirect(true);
    }

    /**
     * Does a delete comment.
     *
     * @param   ActionsComments     $ap     The ActionsComments instance
     *
     * @throws  Exception   If no comment selected
     */
    public static function doDeleteComment(ActionsComments $ap): void
    {
        $ids = $ap->getIDs();
        if ($ids === []) {
            throw new Exception(__('No comment selected'));
        }
        // Backward compatibility
        foreach ($ids as $id) {
            # --BEHAVIOR-- adminBeforeCommentDelete -- string
            App::behavior()->callBehavior('adminBeforeCommentDelete', $id);
        }

        # --BEHAVIOR-- adminBeforeCommentsDelete -- array<int,string>
        App::behavior()->callBehavior('adminBeforeCommentsDelete', $ids);

        App::blog()->delComments($ids);

        Notices::addSuccessNotice(__('Selected comments have been successfully deleted.'));
        $ap->redirect(false);
    }

    /**
     * Add comments IP in an antispam blacklist.
     *
     * @param   ActionsComments     $ap     The ActionsComments instance
     *
     * @throws  Exception   If no comment selected
     */
    public static function doBlocklistIP(ActionsComments $ap): void
    {
        $ids = $ap->getIDs();
        if ($ids === []) {
            throw new Exception(__('No comment selected'));
        }

        $action = $ap->getAction();
        $global = $action === 'blocklist_global' && App::auth()->isSuperAdmin();

        $filters_opt  = App::blog()->settings()->antispam->antispam_filters;
        $filterActive = fn ($name): bool => isset($filters_opt[$name]) && is_array($filters_opt[$name]) && $filters_opt[$name][0] == 1;
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
                } elseif ($filters['v4']) {
                    // Assume that IP is IPv4
                    (new dcFilterIP())->addIP('black', $rs->comment_ip, $global);
                    $count++;
                }
            }

            if ($count !== 0) {
                Notices::addSuccessNotice(__('IP addresses for selected comments have been blocklisted.'));
            }
        }
        $ap->redirect(true);
    }
}
