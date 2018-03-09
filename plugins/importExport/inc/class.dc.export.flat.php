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

if (!defined('DC_RC_PATH')) {return;}

class dcExportFlat extends dcIeModule
{
    public function setInfo()
    {
        $this->type        = 'export';
        $this->name        = __('Flat file export');
        $this->description = __('Exports a blog or a full Dotclear installation to flat file.');
    }

    public function process($do)
    {
        # Export a blog
        if ($do == 'export_blog' && $this->core->auth->check('admin', $this->core->blog->id)) {
            $fullname = $this->core->blog->public_path . '/.backup_' . sha1(uniqid());
            $blog_id  = $this->core->con->escape($this->core->blog->id);

            try
            {
                $exp = new flatExport($this->core->con, $fullname, $this->core->prefix);
                fwrite($exp->fp, '///DOTCLEAR|' . DC_VERSION . "|single\n");

                $exp->export('category',
                    'SELECT * FROM ' . $this->core->prefix . 'category ' .
                    "WHERE blog_id = '" . $blog_id . "'"
                );
                $exp->export('link',
                    'SELECT * FROM ' . $this->core->prefix . 'link ' .
                    "WHERE blog_id = '" . $blog_id . "'"
                );
                $exp->export('setting',
                    'SELECT * FROM ' . $this->core->prefix . 'setting ' .
                    "WHERE blog_id = '" . $blog_id . "'"
                );
                $exp->export('post',
                    'SELECT * FROM ' . $this->core->prefix . 'post ' .
                    "WHERE blog_id = '" . $blog_id . "'"
                );
                $exp->export('meta',
                    'SELECT meta_id, meta_type, M.post_id ' .
                    'FROM ' . $this->core->prefix . 'meta M, ' . $this->core->prefix . 'post P ' .
                    'WHERE P.post_id = M.post_id ' .
                    "AND P.blog_id = '" . $blog_id . "'"
                );
                $exp->export('media',
                    'SELECT * FROM ' . $this->core->prefix . "media WHERE media_path = '" .
                    $this->core->con->escape($this->core->blog->settings->system->public_path) . "'"
                );
                $exp->export('post_media',
                    'SELECT media_id, M.post_id ' .
                    'FROM ' . $this->core->prefix . 'post_media M, ' . $this->core->prefix . 'post P ' .
                    'WHERE P.post_id = M.post_id ' .
                    "AND P.blog_id = '" . $blog_id . "'"
                );
                $exp->export('ping',
                    'SELECT ping.post_id, ping_url, ping_dt ' .
                    'FROM ' . $this->core->prefix . 'ping ping, ' . $this->core->prefix . 'post P ' .
                    'WHERE P.post_id = ping.post_id ' .
                    "AND P.blog_id = '" . $blog_id . "'"
                );
                $exp->export('comment',
                    'SELECT C.* ' .
                    'FROM ' . $this->core->prefix . 'comment C, ' . $this->core->prefix . 'post P ' .
                    'WHERE P.post_id = C.post_id ' .
                    "AND P.blog_id = '" . $blog_id . "'"
                );

                # --BEHAVIOR-- exportSingle
                $this->core->callBehavior('exportSingle', $this->core, $exp, $blog_id);

                $_SESSION['export_file']     = $fullname;
                $_SESSION['export_filename'] = $_POST['file_name'];
                $_SESSION['export_filezip']  = !empty($_POST['file_zip']);
                http::redirect($this->getURL() . '&do=ok');
            } catch (Exception $e) {
                @unlink($fullname);
                throw $e;
            }
        }

        # Export all content
        if ($do == 'export_all' && $this->core->auth->isSuperAdmin()) {
            $fullname = $this->core->blog->public_path . '/.backup_' . sha1(uniqid());
            try
            {
                $exp = new flatExport($this->core->con, $fullname, $this->core->prefix);
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
                $this->core->callBehavior('exportFull', $this->core, $exp);

                $_SESSION['export_file']     = $fullname;
                $_SESSION['export_filename'] = $_POST['file_name'];
                $_SESSION['export_filezip']  = !empty($_POST['file_zip']);
                http::redirect($this->getURL() . '&do=ok');
            } catch (Exception $e) {
                @unlink($fullname);
                throw $e;
            }
        }

        # Send file content
        if ($do == 'ok') {
            if (!file_exists($_SESSION['export_file'])) {
                throw new Exception(__('Export file not found.'));
            }

            ob_end_clean();

            if (substr($_SESSION['export_filename'], -4) == '.zip') {
                $_SESSION['export_filename'] = substr($_SESSION['export_filename'], 0, -4); //.'.txt';
            }

            # Flat export
            if (empty($_SESSION['export_filezip'])) {

                header('Content-Disposition: attachment;filename=' . $_SESSION['export_filename']);
                header('Content-Type: text/plain; charset=UTF-8');
                readfile($_SESSION['export_file']);

                unlink($_SESSION['export_file']);
                unset($_SESSION['export_file'], $_SESSION['export_filename'], $_SESSION['export_filezip']);
                exit;
            }
            # Zip export
            else {
                try
                {
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
    }

    public function gui()
    {
        echo
        '<form action="' . $this->getURL(true) . '" method="post" class="fieldset">' .
        '<h3>' . __('Single blog') . '</h3>' .
        '<p>' . sprintf(__('This will create an export of your current blog: %s'), '<strong>' . html::escapeHTML($this->core->blog->name)) . '</strong>.</p>' .

        '<p><label for="file_name">' . __('File name:') . '</label>' .
        form::field('file_name', 50, 255, date('Y-m-d-H-i-') . html::escapeHTML($this->core->blog->id . '-backup.txt')) .
        '</p>' .

        '<p><label for="file_zip" class="classic">' .
        form::checkbox(array('file_zip', 'file_zip'), 1) . ' ' .
        __('Compress file') . '</label>' .
        '</p>' .

        '<p class="zip-dl"><a href="' . $this->core->decode('admin.media', array('d' => '', 'zipdl' => '1')) . '">' .
        __('You may also want to download your media directory as a zip file') . '</a></p>' .

        '<p><input type="submit" value="' . __('Export') . '" />' .
        form::hidden(array('do'), 'export_blog') .
        $this->core->formNonce() . '</p>' .

            '</form>';

        if ($this->core->auth->isSuperAdmin()) {
            echo
            '<form action="' . $this->getURL(true) . '" method="post" class="fieldset">' .
            '<h3>' . __('Multiple blogs') . '</h3>' .
            '<p>' . __('This will create an export of all the content of your database.') . '</p>' .

            '<p><label for="file_name2">' . __('File name:') . '</label>' .
            form::field(array('file_name', 'file_name2'), 50, 255, date('Y-m-d-H-i-') . 'dotclear-backup.txt') .
            '</p>' .

            '<p><label for="file_zip2" class="classic">' .
            form::checkbox(array('file_zip', 'file_zip2'), 1) . ' ' .
            __('Compress file') . '</label>' .
            '</p>' .

            '<p><input type="submit" value="' . __('Export') . '" />' .
            form::hidden(array('do'), 'export_all') .
            $this->core->formNonce() . '</p>' .

                '</form>';
        }
    }
}
