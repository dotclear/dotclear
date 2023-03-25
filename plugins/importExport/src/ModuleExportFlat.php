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
use dcAuth;
use dcBlog;
use dcCategories;
use dcCore;
use dcMedia;
use dcMeta;
use dcNamespace;
use dcPostMedia;
use dcTrackback;
use Dotclear\Helper\Html\Html;
use initBlogroll;
use fileZip;
use form;
use http;

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
        if ($do === 'export_blog' && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_ADMIN,
        ]), dcCore::app()->blog->id)) {
            $fullname = dcCore::app()->blog->public_path . '/.backup_' . sha1(uniqid());
            $blog_id  = dcCore::app()->con->escape(dcCore::app()->blog->id);

            try {
                $exp = new FlatExport(dcCore::app()->con, $fullname, dcCore::app()->prefix);
                fwrite($exp->fp, '///DOTCLEAR|' . DC_VERSION . "|single\n");

                $exp->export(
                    'category',
                    'SELECT * FROM ' . dcCore::app()->prefix . dcCategories::CATEGORY_TABLE_NAME . ' ' .
                    "WHERE blog_id = '" . $blog_id . "'"
                );
                $exp->export(
                    'link',
                    'SELECT * FROM ' . dcCore::app()->prefix . initBlogroll::LINK_TABLE_NAME . ' ' .
                    "WHERE blog_id = '" . $blog_id . "'"
                );
                $exp->export(
                    'setting',
                    'SELECT * FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . ' ' .
                    "WHERE blog_id = '" . $blog_id . "'"
                );
                $exp->export(
                    'post',
                    'SELECT * FROM ' . dcCore::app()->prefix . dcBlog::POST_TABLE_NAME . ' ' .
                    "WHERE blog_id = '" . $blog_id . "'"
                );
                $exp->export(
                    'meta',
                    'SELECT meta_id, meta_type, M.post_id ' .
                    'FROM ' . dcCore::app()->prefix . dcMeta::META_TABLE_NAME . ' M, ' . dcCore::app()->prefix . dcBlog::POST_TABLE_NAME . ' P ' .
                    'WHERE P.post_id = M.post_id ' .
                    "AND P.blog_id = '" . $blog_id . "'"
                );
                $exp->export(
                    'media',
                    'SELECT * FROM ' . dcCore::app()->prefix . dcMedia::MEDIA_TABLE_NAME . " WHERE media_path = '" .
                    dcCore::app()->con->escape(dcCore::app()->blog->settings->system->public_path) . "'"
                );
                $exp->export(
                    'post_media',
                    'SELECT media_id, M.post_id ' .
                    'FROM ' . dcCore::app()->prefix . dcPostMedia::POST_MEDIA_TABLE_NAME . ' M, ' . dcCore::app()->prefix . dcBlog::POST_TABLE_NAME . ' P ' .
                    'WHERE P.post_id = M.post_id ' .
                    "AND P.blog_id = '" . $blog_id . "'"
                );
                $exp->export(
                    'ping',
                    'SELECT ping.post_id, ping_url, ping_dt ' .
                    'FROM ' . dcCore::app()->prefix . dcTrackback::PING_TABLE_NAME . ' ping, ' . dcCore::app()->prefix . dcBlog::POST_TABLE_NAME . ' P ' .
                    'WHERE P.post_id = ping.post_id ' .
                    "AND P.blog_id = '" . $blog_id . "'"
                );
                $exp->export(
                    'comment',
                    'SELECT C.* ' .
                    'FROM ' . dcCore::app()->prefix . dcBlog::COMMENT_TABLE_NAME . ' C, ' . dcCore::app()->prefix . dcBlog::POST_TABLE_NAME . ' P ' .
                    'WHERE P.post_id = C.post_id ' .
                    "AND P.blog_id = '" . $blog_id . "'"
                );

                # --BEHAVIOR-- exportSingle
                dcCore::app()->callBehavior('exportSingleV2', $exp, $blog_id);

                $_SESSION['export_file']     = $fullname;
                $_SESSION['export_filename'] = $_POST['file_name'];
                $_SESSION['export_filezip']  = !empty($_POST['file_zip']);
                http::redirect($this->getURL() . '&do=ok');
            } catch (Exception $e) {
                @unlink($fullname);

                throw $e;
            }
        }

        // Export all content
        if ($do === 'export_all' && dcCore::app()->auth->isSuperAdmin()) {
            $fullname = dcCore::app()->blog->public_path . '/.backup_' . sha1(uniqid());

            try {
                $exp = new FlatExport(dcCore::app()->con, $fullname, dcCore::app()->prefix);
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

                # --BEHAVIOR-- exportFull
                dcCore::app()->callBehavior('exportFullV2', $exp);

                $_SESSION['export_file']     = $fullname;
                $_SESSION['export_filename'] = $_POST['file_name'];
                $_SESSION['export_filezip']  = !empty($_POST['file_zip']);
                http::redirect($this->getURL() . '&do=ok');
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

            try {
                $file_zipname = $_SESSION['export_filename'] . '.zip';

                $fp  = fopen('php://output', 'wb');
                $zip = new fileZip($fp);
                $zip->addFile($_SESSION['export_file'], $_SESSION['export_filename']);

                header('Content-Disposition: attachment;filename=' . $file_zipname);
                header('Content-Type: application/x-zip');

                $zip->write();

                unlink($_SESSION['export_file']);
                unset($zip, $_SESSION['export_file'], $_SESSION['export_filename'], $file_zipname);
                exit;
            } catch (Exception $e) {
                unset($zip, $_SESSION['export_file'], $_SESSION['export_filename'], $file_zipname);
                @unlink($_SESSION['export_file']);

                throw new Exception(__('Failed to compress export file.'));
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
        '<p>' . sprintf(__('This will create an export of your current blog: %s'), '<strong>' . Html::escapeHTML(dcCore::app()->blog->name)) . '</strong>.</p>' .

        '<p><label for="file_name">' . __('File name:') . '</label>' .
        form::field('file_name', 50, 255, date('Y-m-d-H-i-') . Html::escapeHTML(dcCore::app()->blog->id . '-backup.txt')) .
        '</p>' .

        '<p><label for="file_zip" class="classic">' .
        form::checkbox(['file_zip', 'file_zip'], 1) . ' ' .
        __('Compress file') . '</label>' .
        '</p>' .

        '<p class="zip-dl"><a href="' . dcCore::app()->adminurl->decode('admin.media', ['d' => '', 'zipdl' => '1']) . '">' .
        __('You may also want to download your media directory as a zip file') . '</a></p>' .

        '<p><input type="submit" value="' . __('Export') . '" />' .
        form::hidden(['do'], 'export_blog') .
        dcCore::app()->formNonce() .
        '</p>' .
        '</form>';

        if (dcCore::app()->auth->isSuperAdmin()) {
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
            dcCore::app()->formNonce() .
            '</p>' .
            '</form>';
        }
    }
}
