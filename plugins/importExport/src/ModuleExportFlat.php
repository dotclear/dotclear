<?php
/**
 * @brief importExport, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\importExport;

use Exception;
use dcBlog;
use dcCategories;
use dcMedia;
use dcMeta;
use dcNamespace;
use dcPostMedia;
use dcTrackback;
use Dotclear\Core\Core;
use Dotclear\Helper\File\Zip\Zip;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use initBlogroll;
use form;

class ModuleExportFlat extends Module
{
    /**
     * Sets the module information.
     */
    public function setInfo()
    {
        $this->type        = 'export';
        $this->name        = __('Flat file export');
        $this->description = __('Exports a blog or a full Dotclear installation to flat file.');
    }

    /**
     * Processes the import/export.
     *
     * @param      string  $do     action
     */
    public function process(string $do): void
    {
        // Export a blog
        if ($do === 'export_blog' && Core::auth()->check(Core::auth()->makePermissions([
            Core::auth()::PERMISSION_ADMIN,
        ]), Core::blog()->id)) {
            $fullname = Core::blog()->public_path . '/.backup_' . sha1(uniqid());
            $blog_id  = Core::con()->escape(Core::blog()->id);

            try {
                $exp = new FlatExport(Core::con(), $fullname, Core::con()->prefix());
                fwrite($exp->fp, '///DOTCLEAR|' . DC_VERSION . "|single\n");

                $exp->export(
                    'category',
                    'SELECT * FROM ' . Core::con()->prefix() . dcCategories::CATEGORY_TABLE_NAME . ' ' .
                    "WHERE blog_id = '" . $blog_id . "'"
                );
                $exp->export(
                    'link',
                    'SELECT * FROM ' . Core::con()->prefix() . initBlogroll::LINK_TABLE_NAME . ' ' .
                    "WHERE blog_id = '" . $blog_id . "'"
                );
                $exp->export(
                    'setting',
                    'SELECT * FROM ' . Core::con()->prefix() . dcNamespace::NS_TABLE_NAME . ' ' .
                    "WHERE blog_id = '" . $blog_id . "'"
                );
                $exp->export(
                    'post',
                    'SELECT * FROM ' . Core::con()->prefix() . dcBlog::POST_TABLE_NAME . ' ' .
                    "WHERE blog_id = '" . $blog_id . "'"
                );
                $exp->export(
                    'meta',
                    'SELECT meta_id, meta_type, M.post_id ' .
                    'FROM ' . Core::con()->prefix() . dcMeta::META_TABLE_NAME . ' M, ' . Core::con()->prefix() . dcBlog::POST_TABLE_NAME . ' P ' .
                    'WHERE P.post_id = M.post_id ' .
                    "AND P.blog_id = '" . $blog_id . "'"
                );
                $exp->export(
                    'media',
                    'SELECT * FROM ' . Core::con()->prefix() . dcMedia::MEDIA_TABLE_NAME . " WHERE media_path = '" .
                    Core::con()->escape(Core::blog()->settings->system->public_path) . "'"
                );
                $exp->export(
                    'post_media',
                    'SELECT media_id, M.post_id ' .
                    'FROM ' . Core::con()->prefix() . dcPostMedia::POST_MEDIA_TABLE_NAME . ' M, ' . Core::con()->prefix() . dcBlog::POST_TABLE_NAME . ' P ' .
                    'WHERE P.post_id = M.post_id ' .
                    "AND P.blog_id = '" . $blog_id . "'"
                );
                $exp->export(
                    'ping',
                    'SELECT ping.post_id, ping_url, ping_dt ' .
                    'FROM ' . Core::con()->prefix() . dcTrackback::PING_TABLE_NAME . ' ping, ' . Core::con()->prefix() . dcBlog::POST_TABLE_NAME . ' P ' .
                    'WHERE P.post_id = ping.post_id ' .
                    "AND P.blog_id = '" . $blog_id . "'"
                );
                $exp->export(
                    'comment',
                    'SELECT C.* ' .
                    'FROM ' . Core::con()->prefix() . dcBlog::COMMENT_TABLE_NAME . ' C, ' . Core::con()->prefix() . dcBlog::POST_TABLE_NAME . ' P ' .
                    'WHERE P.post_id = C.post_id ' .
                    "AND P.blog_id = '" . $blog_id . "'"
                );

                # --BEHAVIOR-- exportSingle -- FlatExport, string
                Core::behavior()->callBehavior('exportSingleV2', $exp, $blog_id);

                $_SESSION['export_file']     = $fullname;
                $_SESSION['export_filename'] = $_POST['file_name'];
                $_SESSION['export_filezip']  = !empty($_POST['file_zip']);
                Http::redirect($this->getURL() . '&do=ok');
            } catch (Exception $e) {
                @unlink($fullname);

                throw $e;
            }
        }

        // Export all content
        if ($do === 'export_all' && Core::auth()->isSuperAdmin()) {
            $fullname = Core::blog()->public_path . '/.backup_' . sha1(uniqid());

            try {
                $exp = new FlatExport(Core::con(), $fullname, Core::con()->prefix());
                fwrite($exp->fp, '///DOTCLEAR|' . DC_VERSION . "|full\n");
                $exp->exportTable('blog');
                $exp->exportTable('category');
                $exp->exportTable('link');
                $exp->exportTable('setting');
                $exp->exportTable('user');
                $exp->exportTable('pref');
                $exp->exportTable('permissions');
                $exp->exportTable('post');
                $exp->exportTable('meta');
                $exp->exportTable('media');
                $exp->exportTable('post_media');
                $exp->exportTable('log');
                $exp->exportTable('ping');
                $exp->exportTable('comment');
                $exp->exportTable('spamrule');
                $exp->exportTable('version');

                # --BEHAVIOR-- exportFull -- FlatExport
                Core::behavior()->callBehavior('exportFullV2', $exp);

                $_SESSION['export_file']     = $fullname;
                $_SESSION['export_filename'] = $_POST['file_name'];
                $_SESSION['export_filezip']  = !empty($_POST['file_zip']);
                Http::redirect($this->getURL() . '&do=ok');
            } catch (Exception $e) {
                @unlink($fullname);

                throw $e;
            }
        }

        // Send file content
        if ($do === 'ok') {
            if (!file_exists($_SESSION['export_file'])) {
                throw new Exception(__('Export file not found.'));
            }

            ob_end_clean();

            if (substr($_SESSION['export_filename'], -4) == '.zip') {
                $_SESSION['export_filename'] = substr($_SESSION['export_filename'], 0, -4); //.'.txt';
            }

            // Flat export
            if (empty($_SESSION['export_filezip'])) {
                header('Content-Disposition: attachment;filename=' . $_SESSION['export_filename']);
                header('Content-Type: text/plain; charset=UTF-8');
                readfile($_SESSION['export_file']);

                unlink($_SESSION['export_file']);
                unset($_SESSION['export_file'], $_SESSION['export_filename'], $_SESSION['export_filezip']);
                exit;
            }

            // Zip export

            $file_zipname = $_SESSION['export_filename'] . '.zip';

            try {
                $fp  = fopen('php://output', 'wb');
                $zip = new Zip($fp);
                $zip->addFile($_SESSION['export_file'], $_SESSION['export_filename']);

                header('Content-Disposition: attachment;filename=' . $file_zipname);
                header('Content-Type: application/x-zip');

                $zip->write();

                unlink($_SESSION['export_file']);
                exit;
            } catch (Exception $e) {
                @unlink($_SESSION['export_file']);

                throw new Exception(__('Failed to compress export file.'));
            } finally {
                unset($zip, $_SESSION['export_file'], $_SESSION['export_filename'], $file_zipname);
            }
        }
    }

    /**
     * GUI for import/export module
     */
    public function gui(): void
    {
        echo
        '<form action="' . $this->getURL(true) . '" method="post" class="fieldset">' .
        '<h3>' . __('Single blog') . '</h3>' .
        '<p>' . sprintf(__('This will create an export of your current blog: %s'), '<strong>' . Html::escapeHTML(Core::blog()->name)) . '</strong>.</p>' .

        '<p><label for="file_name">' . __('File name:') . '</label>' .
        form::field('file_name', 50, 255, date('Y-m-d-H-i-') . Html::escapeHTML(Core::blog()->id . '-backup.txt')) .
        '</p>' .

        '<p><label for="file_zip" class="classic">' .
        form::checkbox(['file_zip', 'file_zip'], 1) . ' ' .
        __('Compress file') . '</label>' .
        '</p>' .

        '<p class="zip-dl"><a href="' . Core::backend()->url->decode('admin.media', ['d' => '', 'zipdl' => '1']) . '">' .
        __('You may also want to download your media directory as a zip file') . '</a></p>' .

        '<p><input type="submit" value="' . __('Export') . '" />' .
        form::hidden(['do'], 'export_blog') .
        Core::nonce()->getFormNonce() .
        '</p>' .
        '</form>';

        if (Core::auth()->isSuperAdmin()) {
            echo
            '<form action="' . $this->getURL(true) . '" method="post" class="fieldset">' .
            '<h3>' . __('Multiple blogs') . '</h3>' .
            '<p>' . __('This will create an export of all the content of your database.') . '</p>' .

            '<p><label for="file_name2">' . __('File name:') . '</label>' .
            form::field(['file_name', 'file_name2'], 50, 255, date('Y-m-d-H-i-') . 'dotclear-backup.txt') .
            '</p>' .

            '<p><label for="file_zip2" class="classic">' .
            form::checkbox(['file_zip', 'file_zip2'], 1) . ' ' .
            __('Compress file') . '</label>' .
            '</p>' .

            '<p><input type="submit" value="' . __('Export') . '" />' .
            form::hidden(['do'], 'export_all') .
            Core::nonce()->getFormNonce() .
            '</p>' .
            '</form>';
        }
    }
}
