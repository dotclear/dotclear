<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\importExport;

use Exception;
use Dotclear\App;
use Dotclear\Helper\File\Zip\Zip;
use Dotclear\Helper\Network\Http;
use Dotclear\Plugin\blogroll\Blogroll;

/**
 * @brief   The default export flat module handler.
 * @ingroup importExport
 */
class ModuleExportFlat extends Module
{
    public function setInfo(): void
    {
        $this->type        = 'export';
        $this->name        = __('Flat file export');
        $this->description = __('Exports a blog or a full Dotclear installation to flat file.');
    }

    public function process(string $do): void
    {
        // Export a blog
        if ($do === 'export_blog' && App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_ADMIN,
        ]), App::blog()->id())) {
            $fullname = App::blog()->publicPath() . '/.backup_' . sha1(uniqid());
            $blog_id  = App::db()->con()->escapeStr(App::blog()->id());

            try {
                $exp = new FlatExport(App::db()->con(), $fullname, App::db()->con()->prefix());
                fwrite($exp->fp, '///DOTCLEAR|' . App::config()->dotclearVersion() . "|single\n");

                $exp->export(
                    'category',
                    'SELECT * FROM ' . App::db()->con()->prefix() . App::blog()->categories()::CATEGORY_TABLE_NAME . ' ' .
                    "WHERE blog_id = '" . $blog_id . "'"
                );
                $exp->export(
                    'link',
                    'SELECT * FROM ' . App::db()->con()->prefix() . Blogroll::LINK_TABLE_NAME . ' ' .
                    "WHERE blog_id = '" . $blog_id . "'"
                );
                $exp->export(
                    'setting',
                    'SELECT * FROM ' . App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME . ' ' .
                    "WHERE blog_id = '" . $blog_id . "'"
                );
                $exp->export(
                    'post',
                    'SELECT * FROM ' . App::db()->con()->prefix() . App::blog()::POST_TABLE_NAME . ' ' .
                    "WHERE blog_id = '" . $blog_id . "'"
                );
                $exp->export(
                    'meta',
                    'SELECT meta_id, meta_type, M.post_id ' .
                    'FROM ' . App::db()->con()->prefix() . App::meta()::META_TABLE_NAME . ' M, ' . App::db()->con()->prefix() . App::blog()::POST_TABLE_NAME . ' P ' .
                    'WHERE P.post_id = M.post_id ' .
                    "AND P.blog_id = '" . $blog_id . "'"
                );
                $exp->export(
                    'media',
                    'SELECT * FROM ' . App::db()->con()->prefix() . App::postMedia()::MEDIA_TABLE_NAME . " WHERE media_path = '" .
                    App::db()->con()->escapeStr(App::blog()->settings()->system->public_path) . "'"
                );
                $exp->export(
                    'post_media',
                    'SELECT media_id, M.post_id ' .
                    'FROM ' . App::db()->con()->prefix() . App::postMedia()::POST_MEDIA_TABLE_NAME . ' M, ' . App::db()->con()->prefix() . App::blog()::POST_TABLE_NAME . ' P ' .
                    'WHERE P.post_id = M.post_id ' .
                    "AND P.blog_id = '" . $blog_id . "'"
                );
                $exp->export(
                    'ping',
                    'SELECT ping.post_id, ping_url, ping_dt ' .
                    'FROM ' . App::db()->con()->prefix() . App::trackback()::PING_TABLE_NAME . ' ping, ' . App::db()->con()->prefix() . App::blog()::POST_TABLE_NAME . ' P ' .
                    'WHERE P.post_id = ping.post_id ' .
                    "AND P.blog_id = '" . $blog_id . "'"
                );
                $exp->export(
                    'comment',
                    'SELECT C.* ' .
                    'FROM ' . App::db()->con()->prefix() . App::blog()::COMMENT_TABLE_NAME . ' C, ' . App::db()->con()->prefix() . App::blog()::POST_TABLE_NAME . ' P ' .
                    'WHERE P.post_id = C.post_id ' .
                    "AND P.blog_id = '" . $blog_id . "'"
                );

                # --BEHAVIOR-- exportSingle -- FlatExport, string
                App::behavior()->callBehavior('exportSingleV2', $exp, $blog_id);

                App::session()->set('export_file', $fullname);
                App::session()->set('export_filename', $_POST['file_name']);
                App::session()->set('export_filezip', !empty($_POST['file_zip']));
                Http::redirect($this->getURL() . '&do=ok');
            } catch (Exception $e) {
                @unlink($fullname);

                throw $e;
            }
        }

        // Export all content
        if ($do === 'export_all' && App::auth()->isSuperAdmin()) {
            $fullname = App::blog()->publicPath() . '/.backup_' . sha1(uniqid());

            try {
                $exp = new FlatExport(App::db()->con(), $fullname, App::db()->con()->prefix());
                fwrite($exp->fp, '///DOTCLEAR|' . App::config()->dotclearVersion() . "|full\n");
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
                App::behavior()->callBehavior('exportFullV2', $exp);

                App::session()->set('export_file', $fullname);
                App::session()->set('export_filename', $_POST['file_name']);
                App::session()->set('export_filezip', !empty($_POST['file_zip']));
                Http::redirect($this->getURL() . '&do=ok');
            } catch (Exception $e) {
                @unlink($fullname);

                throw $e;
            }
        }

        // Send file content
        if ($do === 'ok') {
            if (App::session()->get('export_file') == '' || !file_exists(App::session()->get('export_file'))) {
                throw new Exception(__('Export file not found.'));
            }

            ob_end_clean();

            if (str_ends_with((string) App::session()->get('export_filename'), '.zip')) {
                App::session()->set('export_filename', substr((string) App::session()->get('export_filename'), 0, -4)); //.'.txt';
            }

            // Flat export
            if (App::session()->get('export_filezip') != '') {
                header('Content-Disposition: attachment;filename=' . App::session()->get('export_filename'));
                header('Content-Type: text/plain; charset=UTF-8');
                readfile(App::session()->get('export_file'));

                unlink(App::session()->get('export_file'));
                App::session()->unset('export_file', 'export_filename', 'export_filezip');
                dotclear_exit();
            }

            // Zip export

            $file_zipname = App::session()->get('export_filename') . '.zip';

            try {
                $fp  = fopen('php://output', 'wb');
                $zip = new Zip($fp);
                $zip->addFile(App::session()->get('export_file'), (string) App::session()->get('export_filename'));

                header('Content-Disposition: attachment;filename=' . $file_zipname);
                header('Content-Type: application/x-zip');

                $zip->write();

                unlink(App::session()->get('export_file'));
                dotclear_exit();
            } catch (Exception) {
                @unlink(App::session()->get('export_file'));

                throw new Exception(__('Failed to compress export file.'));
            } finally {
                unset($zip, $file_zipname);
                App::session()->unset('export_file', 'export_filename');
            }
        }
    }

    public function gui(): void
    {
        // No GUI here, see Export(Blog|Full)MaintenanceTask
    }
}
