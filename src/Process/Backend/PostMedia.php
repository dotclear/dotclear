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
        Page::check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_USAGE,
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
        ]));

        dcCore::app()->admin->post_id   = !empty($_REQUEST['post_id']) ? (int) $_REQUEST['post_id'] : null;
        dcCore::app()->admin->media_id  = !empty($_REQUEST['media_id']) ? (int) $_REQUEST['media_id'] : null;
        dcCore::app()->admin->link_type = !empty($_REQUEST['link_type']) ? $_REQUEST['link_type'] : null;

        if (!dcCore::app()->admin->post_id) {
            exit;
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        $rs = dcCore::app()->blog->getPosts(['post_id' => dcCore::app()->admin->post_id, 'post_type' => '']);
        if ($rs->isEmpty()) {
            exit;
        }

        try {
            if (dcCore::app()->admin->media_id && !empty($_REQUEST['attach'])) {
                // Attach a media to an entry

                $pm = new dcPostMedia();
                $pm->addPostMedia(dcCore::app()->admin->post_id, dcCore::app()->admin->media_id, dcCore::app()->admin->link_type);
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                    header('Content-type: application/json');
                    echo json_encode(['url' => dcCore::app()->getPostAdminURL($rs->post_type, dcCore::app()->admin->post_id, false)], JSON_THROW_ON_ERROR);
                    exit();
                }
                Http::redirect(dcCore::app()->getPostAdminURL($rs->post_type, dcCore::app()->admin->post_id, false));
            }

            dcCore::app()->media = new dcMedia();

            $f = dcCore::app()->media->getPostMedia(dcCore::app()->admin->post_id, dcCore::app()->admin->media_id, dcCore::app()->admin->link_type);
            if (empty($f)) {
                dcCore::app()->admin->post_id = dcCore::app()->admin->media_id = null;

                throw new Exception(__('This attachment does not exist'));
            }
            $f = $f[0];
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        if ((dcCore::app()->admin->post_id && dcCore::app()->admin->media_id) || dcCore::app()->error->flag()) {
            // Remove a media from entry

            if (!empty($_POST['remove'])) {
                $pm = new dcPostMedia();
                $pm->removePostMedia(dcCore::app()->admin->post_id, dcCore::app()->admin->media_id, dcCore::app()->admin->link_type);

                Notices::addSuccessNotice(__('Attachment has been successfully removed.'));
                Http::redirect(dcCore::app()->getPostAdminURL($rs->post_type, dcCore::app()->admin->post_id, false));
            } elseif (isset($_POST['post_id'])) {
                Http::redirect(dcCore::app()->getPostAdminURL($rs->post_type, dcCore::app()->admin->post_id, false));
            }

            if (!empty($_GET['remove'])) {
                Page::open(__('Remove attachment'));

                echo '<h2>' . __('Attachment') . ' &rsaquo; <span class="page-title">' . __('confirm removal') . '</span></h2>';

                echo
                (new Form())
                ->action(dcCore::app()->admin->url->get('admin.post.media'))
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
                    (new Hidden('post_id', (string) dcCore::app()->admin->post_id)),
                    (new Hidden('media_id', (string) dcCore::app()->admin->media_id)),
                    dcCore::app()->nonce->formNonce(),
                ])
                ->render();

                Page::close();
                exit;
            }
        }

        return true;
    }
}
