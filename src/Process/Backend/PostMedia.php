<?php
/**
 * @since 2.27 Before as admin/post_media.php
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use dcCore;
use dcMedia;
use dcPostMedia;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Core;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Network\Http;
use Exception;

class PostMedia extends Process
{
    public static function init(): bool
    {
        Page::check(Core::auth()->makePermissions([
            Core::auth()::PERMISSION_USAGE,
            Core::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        Core::backend()->post_id   = !empty($_REQUEST['post_id']) ? (int) $_REQUEST['post_id'] : null;
        Core::backend()->media_id  = !empty($_REQUEST['media_id']) ? (int) $_REQUEST['media_id'] : null;
        Core::backend()->link_type = !empty($_REQUEST['link_type']) ? $_REQUEST['link_type'] : null;

        if (!Core::backend()->post_id) {
            exit;
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        $rs = Core::blog()->getPosts(['post_id' => Core::backend()->post_id, 'post_type' => '']);
        if ($rs->isEmpty()) {
            exit;
        }

        try {
            if (Core::backend()->media_id && !empty($_REQUEST['attach'])) {
                // Attach a media to an entry

                $pm = new dcPostMedia();
                $pm->addPostMedia(Core::backend()->post_id, Core::backend()->media_id, Core::backend()->link_type);
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                    header('Content-type: application/json');
                    echo json_encode(['url' => Core::postTypes()->get($rs->post_type)->adminurl(Core::backend()->post_id, false)], JSON_THROW_ON_ERROR);
                    exit();
                }
                Http::redirect(Core::postTypes()->get($rs->post_type)->adminUrl(Core::backend()->post_id, false));
            }

            dcCore::app()->media = new dcMedia();

            $f = dcCore::app()->media->getPostMedia(Core::backend()->post_id, Core::backend()->media_id, Core::backend()->link_type);
            if (empty($f)) {
                Core::backend()->post_id = Core::backend()->media_id = null;

                throw new Exception(__('This attachment does not exist'));
            }
            $f = $f[0];
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        if ((Core::backend()->post_id && Core::backend()->media_id) || dcCore::app()->error->flag()) {
            // Remove a media from entry

            if (!empty($_POST['remove'])) {
                $pm = new dcPostMedia();
                $pm->removePostMedia(Core::backend()->post_id, Core::backend()->media_id, Core::backend()->link_type);

                Notices::addSuccessNotice(__('Attachment has been successfully removed.'));
                Http::redirect(Core::postTypes()->get($rs->post_type)->adminUrl(Core::backend()->post_id, false));
            } elseif (isset($_POST['post_id'])) {
                Http::redirect(Core::postTypes()->get($rs->post_type)->adminUrl(Core::backend()->post_id, false));
            }

            if (!empty($_GET['remove'])) {
                Page::open(__('Remove attachment'));

                echo '<h2>' . __('Attachment') . ' &rsaquo; <span class="page-title">' . __('confirm removal') . '</span></h2>';

                echo
                (new Form())
                ->action(Core::backend()->url->get('admin.post.media'))
                ->method('post')
                ->fields([
                    (new Para())
                        ->items([
                            (new Text())->text(__('Are you sure you want to remove this attachment?')),
                        ]),
                    (new Para())
                        ->separator(' &nbsp; ')
                        ->items([
                            (new Submit('cancel'))->class('reset')->value(__('Cancel')),
                            (new Submit('remove'))->class('delete')->value(__('Yes')),
                        ]),
                    (new Hidden('post_id', (string) Core::backend()->post_id)),
                    (new Hidden('media_id', (string) Core::backend()->media_id)),
                    Core::nonce()->formNonce(),
                ])
                ->render();

                Page::close();
                exit;
            }
        }

        return true;
    }
}
