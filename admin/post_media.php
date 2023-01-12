<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
require __DIR__ . '/../inc/admin/prepend.php';

class adminPostMedia
{
    /**
     * Initializes the page.
     */
    public static function init()
    {
        dcPage::check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_USAGE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]));

        dcCore::app()->admin->post_id   = !empty($_REQUEST['post_id']) ? (int) $_REQUEST['post_id'] : null;
        dcCore::app()->admin->media_id  = !empty($_REQUEST['media_id']) ? (int) $_REQUEST['media_id'] : null;
        dcCore::app()->admin->link_type = !empty($_REQUEST['link_type']) ? $_REQUEST['link_type'] : null;

        if (!dcCore::app()->admin->post_id) {
            exit;
        }
    }

    /**
     * Processes the request(s).
     */
    public static function process()
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
                http::redirect(dcCore::app()->getPostAdminURL($rs->post_type, dcCore::app()->admin->post_id, false));
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

                dcPage::addSuccessNotice(__('Attachment has been successfully removed.'));
                http::redirect(dcCore::app()->getPostAdminURL($rs->post_type, dcCore::app()->admin->post_id, false));
            } elseif (isset($_POST['post_id'])) {
                http::redirect(dcCore::app()->getPostAdminURL($rs->post_type, dcCore::app()->admin->post_id, false));
            }

            if (!empty($_GET['remove'])) {
                dcPage::open(__('Remove attachment'));

                echo '<h2>' . __('Attachment') . ' &rsaquo; <span class="page-title">' . __('confirm removal') . '</span></h2>';

                echo
                (new formForm())
                ->action(dcCore::app()->adminurl->get('admin.post.media'))
                ->method('post')
                ->fields([
                    (new formPara())
                        ->items([
                            (new formText())->text(__('Are you sure you want to remove this attachment?')),
                        ]),
                    (new formPara())
                        ->separator(' &nbsp; ')
                        ->items([
                            (new formSubmit('cancel'))->class('reset')->value(__('Cancel')),
                            (new formSubmit('remove'))->class('delete')->value(__('Yes')),
                        ]),
                    (new formHidden('post_id', (string) dcCore::app()->admin->post_id)),
                    (new formHidden('media_id', (string) dcCore::app()->admin->media_id)),
                    dcCore::app()->formNonce(false),
                ])
                ->render();

                dcPage::close();
                exit;
            }
        }
    }
}

adminPostMedia::init();
adminPostMedia::process();
