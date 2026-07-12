<?php

/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use Dotclear\App;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @since 2.27 Before as admin/post_media.php
 */
class PostMedia
{
    use TraitProcess;

    protected static ?int $post_id;

    protected static ?int $media_id;

    protected static ?string $link_type;

    public static function init(): bool
    {
        App::backend()->page()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        self::$post_id          = isset($_REQUEST['post_id']) && is_numeric($post_id = $_REQUEST['post_id']) ? (int) $post_id : null;
        App::backend()->post_id = self::$post_id;

        if (!App::backend()->post_id) {
            dotclear_exit();
        }

        self::$media_id  = isset($_REQUEST['media_id'])  && is_numeric($media_id = $_REQUEST['media_id']) ? (int) $media_id : null;
        self::$link_type = isset($_REQUEST['link_type']) && is_string($link_type = $_REQUEST['link_type']) ? $link_type : null;

        return self::status(true);
    }

    public static function process(): bool
    {
        self::$post_id = is_numeric($post_id = App::backend()->post_id) ? (int) $post_id : 0;

        $rs = App::blog()->getPosts(['post_id' => self::$post_id, 'post_type' => '']);
        if ($rs->isEmpty()) {
            dotclear_exit();
        }

        try {
            $pm = App::postMedia();

            if (self::$media_id && !empty($_REQUEST['attach'])) {
                // Attach a media to an entry

                if (is_string(self::$link_type)) {
                    $pm->addPostMedia(self::$post_id, self::$media_id, self::$link_type);
                } else {
                    // Use default type
                    $pm->addPostMedia(self::$post_id, self::$media_id);
                }

                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                    header('Content-type: application/json');
                    echo json_encode(['url' => App::postTypes()->get($rs->strField('post_type'))->adminUrl(self::$post_id, false)], JSON_THROW_ON_ERROR);
                    dotclear_exit();
                }

                Http::redirect(App::postTypes()->get($rs->strField('post_type'))->adminUrl(self::$post_id, false));
            }

            $f = App::media()->getPostMedia(self::$post_id, self::$media_id, self::$link_type);
            if ($f === []) {
                self::$post_id = null;
                App::backend()->post_id = null;
                self::$media_id = null;
                throw new Exception(__('This attachment does not exist'));
            }

            $f = $f[0];
        } catch (Exception $exception) {
            App::error()->add($exception->getMessage());
        }

        if ((self::$post_id && self::$media_id) || App::error()->flag()) {
            // Remove a media from entry

            if (self::$post_id && self::$media_id) {
                if (!empty($_POST['remove'])) {
                    $pm->removePostMedia(self::$post_id, self::$media_id, self::$link_type);

                    App::backend()->notices()->addSuccessNotice(__('Attachment has been successfully removed.'));
                    Http::redirect(App::postTypes()->get($rs->strField('post_type'))->adminUrl(self::$post_id, false));
                } elseif (isset($_POST['post_id'])) {
                    Http::redirect(App::postTypes()->get($rs->strField('post_type'))->adminUrl(self::$post_id, false));
                }
            }

            if (!empty($_GET['remove'])) {
                App::backend()->page()->open(__('Remove attachment'));

                echo (new Text('h2', ' &rsaquo; ' . (new Span(__('confirm removal')))->class('page-title')->render()))
                ->render();

                echo
                (new Form())
                ->action(App::backend()->url()->get('admin.post.media'))
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
                    (new Hidden('post_id', (string) self::$post_id)),
                    (new Hidden('media_id', (string) self::$media_id)),
                    App::nonce()->formNonce(),
                ])
                ->render();

                App::backend()->page()->close();
                dotclear_exit();
            }
        }

        return true;
    }
}
