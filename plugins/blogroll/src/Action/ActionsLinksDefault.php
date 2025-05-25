<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\blogroll\Action;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Plugin\blogroll\Blogroll;
use Dotclear\Plugin\blogroll\Status\Link;
use Exception;

/**
 * @brief   Handler for default actions on links.
 */
class ActionsLinksDefault
{
    /**
     * Set links actions.
     *
     * @param   ActionsPosts    $ap     The ActionsPosts instance
     */
    public static function adminLinksActionsPage(ActionsLinks $ap): void
    {
        $statusLink = new Link();

        if (App::auth()->check(App::auth()->makePermissions([
            Blogroll::PERMISSION_BLOGROLL,
            App::auth()::PERMISSION_ADMIN,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            $ap->addAction(
                [__('Status') => $statusLink->action()],
                self::doChangeLinkStatus(...)
            );
            $ap->addAction(
                [__('Delete') => [
                    __('Delete') => 'delete', ]],
                self::doDeleteLink(...)
            );
        }
    }

    /**
     * Does a change link status.
     *
     * @param   ActionsLinks    $ap     The ActionsLinks instance
     *
     * @throws  Exception
     */
    public static function doChangeLinkStatus(ActionsLinks $ap): void
    {
        $statusLink = new Link();

        // unknown to online
        $status = $statusLink->has((string) $ap->getAction()) ?
            $statusLink->level((string) $ap->getAction()) :
            $statusLink::ONLINE;

        $ids = $ap->getIDs();
        if ($ids === []) {
            throw new Exception(__('No entry selected'));
        }

        // Set status of remaining entries
        (new Blogroll(App::blog()))->updLinksStatus($ids, $status);

        Notices::addSuccessNotice(
            sprintf(
                __(
                    '%d link has been successfully updated to status : "%s"',
                    '%d links have been successfully updated to status : "%s"',
                    count($ids)
                ),
                count($ids),
                $statusLink->name($status)
            )
        );
        $ap->redirect(true);
    }

    /**
     * Does a delete post.
     *
     * @param   ActionsLinks    $ap     The ActionsLinks instance
     *
     * @throws  Exception
     */
    public static function doDeleteLink(ActionsLinks $ap): void
    {
        $ids = $ap->getIDs();
        if ($ids === []) {
            throw new Exception(__('No link selected'));
        }
        // Backward compatibility
        foreach ($ids as $id) {
            # --BEHAVIOR-- adminBeforePostDelete -- int
            App::behavior()->callBehavior('adminBeforeLinkDelete', (int) $id);
        }

        # --BEHAVIOR-- adminBeforePostsDelete -- array<int,string>
        App::behavior()->callBehavior('adminBeforeLinksDelete', $ids);

        // Delete selected entries
        (new Blogroll(App::blog()))->delLinks($ids);

        Notices::addSuccessNotice(
            sprintf(
                __(
                    '%d link has been successfully deleted',
                    '%d links have been successfully deleted',
                    count($ids)
                ),
                count($ids)
            )
        );

        $ap->redirect(false);
    }
}
