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

class dcImportFlat extends dcIeModule
{
    protected $status = false;

    public function setInfo()
    {
        $this->type        = 'import';
        $this->name        = __('Flat file import');
        $this->description = __('Imports a blog or a full Dotclear installation from flat file.');
    }

    public function process($do)
    {
        if ($do == 'single' || $do == 'full') {
            $this->status = $do;
            return;
        }

        $to_unlink = false;

        # Single blog import
        $files      = $this->getPublicFiles();
        $single_upl = null;
        if (!empty($_POST['public_single_file']) && in_array($_POST['public_single_file'], $files)) {
            $single_upl = false;
        } elseif (!empty($_FILES['up_single_file'])) {
            $single_upl = true;
        }

        if ($single_upl !== null) {
            if ($single_upl) {
                files::uploadStatus($_FILES['up_single_file']);
                $file = DC_TPL_CACHE . '/' . md5(uniqid());
                if (!move_uploaded_file($_FILES['up_single_file']['tmp_name'], $file)) {
                    throw new Exception(__('Unable to move uploaded file.'));
                }
                $to_unlink = true;
            } else {
                $file = $_POST['public_single_file'];
            }

            try {
                # Try to unzip file
                $unzip_file = $this->unzip($file);
                if (false !== $unzip_file) {
                    $bk = new flatImport($this->core, $unzip_file);
                }
                # Else this is a normal file
                else {
                    $bk = new flatImport($this->core, $file);
                }

                $bk->importSingle();
            } catch (Exception $e) {
                @unlink($unzip_file);
                if ($to_unlink) {
                    @unlink($file);
                }
                throw $e;
            }
            @unlink($unzip_file);
            if ($to_unlink) {
                @unlink($file);
            }
            http::redirect($this->getURL() . '&do=single');
        }

        # Full import
        $full_upl = null;
        if (!empty($_POST['public_full_file']) && in_array($_POST['public_full_file'], $files)) {
            $full_upl = false;
        } elseif (!empty($_FILES['up_full_file'])) {
            $full_upl = true;
        }

        if ($full_upl !== null && $this->core->auth->isSuperAdmin()) {
            if (empty($_POST['your_pwd']) || !$this->core->auth->checkPassword($_POST['your_pwd'])) {
                throw new Exception(__('Password verification failed'));
            }

            if ($full_upl) {
                files::uploadStatus($_FILES['up_full_file']);
                $file = DC_TPL_CACHE . '/' . md5(uniqid());
                if (!move_uploaded_file($_FILES['up_full_file']['tmp_name'], $file)) {
                    throw new Exception(__('Unable to move uploaded file.'));
                }
                $to_unlink = true;
            } else {
                $file = $_POST['public_full_file'];
            }

            try {
                # Try to unzip file
                $unzip_file = $this->unzip($file);
                if (false !== $unzip_file) {
                    $bk = new flatImport($this->core, $unzip_file);
                }
                # Else this is a normal file
                else {
                    $bk = new flatImport($this->core, $file);
                }

                $bk->importFull();
            } catch (Exception $e) {
                @unlink($unzip_file);
                if ($to_unlink) {
                    @unlink($file);
                }
                throw $e;
            }
            @unlink($unzip_file);
            if ($to_unlink) {
                @unlink($file);
            }
            http::redirect($this->getURL() . '&do=full');
        }

        header('content-type:text/plain');
        var_dump($_POST);
        exit;

        $this->status = true;
    }

    public function gui()
    {
        if ($this->status == 'single') {
            dcPage::success(__('Single blog successfully imported.'));
            return;
        }
        if ($this->status == 'full') {
            dcPage::success(__('Content successfully imported.'));
            return;
        }

        $public_files = array_merge(array('-' => ''), $this->getPublicFiles());
        $has_files    = (boolean) (count($public_files) - 1);

        echo
        '<script type="text/javascript">' . "\n" .
        dcPage::jsVar('dotclear.msg.confirm_full_import',
            __('Are you sure you want to import a full backup file?')) .
            "$(function() {" .
            "$('#up_single_file').change(function() { " .
            "if (this.value != '') { $('#public_single_file').val(''); } " .
            "}); " .
            "$('#public_single_file').change(function() { " .
            "if (this.value != '') { $('#up_single_file').val(''); } " .
            "}); " .
            "$('#up_full_file').change(function() { " .
            "if (this.value != '') { $('#public_full_file').val(''); } " .
            "}); " .
            "$('#public_full_file').change(function() { " .
            "if (this.value != '') { $('#up_full_file').val(''); } " .
            "}); " .
            "$('#formfull').submit(function() { " .
            "return window.confirm(dotclear.msg.confirm_full_import); " .
            "}); " .
            "});\n" .
            "</script>\n";

        echo
        '<form action="' . $this->getURL(true) . '" method="post" enctype="multipart/form-data" class="fieldset">' .
        '<h3>' . __('Single blog') . '</h3>' .
        '<p>' . sprintf(__('This will import a single blog backup as new content in the current blog: <strong>%s</strong>.'), html::escapeHTML($this->core->blog->name)) . '</p>' .

        '<p><label for="up_single_file">' . __('Upload a backup file') .
        ' (' . sprintf(__('maximum size %s'), files::size(DC_MAX_UPLOAD_SIZE)) . ')' . ' </label>' .
            ' <input type="file" id="up_single_file" name="up_single_file" size="20" />' .
            '</p>';

        if ($has_files) {
            echo
            '<p><label for="public_single_file" class="">' . __('or pick up a local file in your public directory') . ' </label> ' .
            form::combo('public_single_file', $public_files) .
                '</p>';
        }

        echo
        '<p>' .
        $this->core->formNonce() .
        form::hidden(array('do'), 1) .
        form::hidden(array('MAX_FILE_SIZE'), DC_MAX_UPLOAD_SIZE) .
        '<input type="submit" value="' . __('Import') . '" /></p>' .

            '</form>';

        if ($this->core->auth->isSuperAdmin()) {
            echo
            '<form action="' . $this->getURL(true) . '" method="post" enctype="multipart/form-data" id="formfull" class="fieldset">' .
            '<h3>' . __('Multiple blogs') . '</h3>' .
            '<p class="warning">' . __('This will reset all the content of your database, except users.') . '</p>' .

            '<p><label for="up_full_file">' . __('Upload a backup file') . ' ' .
            ' (' . sprintf(__('maximum size %s'), files::size(DC_MAX_UPLOAD_SIZE)) . ')' . ' </label>' .
                '<input type="file" id="up_full_file" name="up_full_file" size="20" />' .
                '</p>';

            if ($has_files) {
                echo
                '<p><label for="public_full_file">' . __('or pick up a local file in your public directory') . ' </label>' .
                form::combo('public_full_file', $public_files) .
                    '</p>';
            }

            echo
            '<p><label for="your_pwd" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Your password:') . '</label>' .
            form::password('your_pwd', 20, 255,
                array(
                    'extra_html'   => 'required placeholder="' . __('Password') . '"',
                    'autocomplete' => 'current-password'
                )
            ) . '</p>' .

            '<p>' .
            $this->core->formNonce() .
            form::hidden(array('do'), 1) .
            form::hidden(array('MAX_FILE_SIZE'), DC_MAX_UPLOAD_SIZE) .
            '<input type="submit" value="' . __('Import') . '" /></p>' .

                '</form>';
        }
    }

    protected function getPublicFiles()
    {
        $public_files = array();
        $dir          = @dir($this->core->blog->public_path);
        if ($dir) {
            while (($entry = $dir->read()) !== false) {
                $entry_path = $dir->path . '/' . $entry;

                if (is_file($entry_path) && is_readable($entry_path)) {
                    # Do not test each zip file content here, its too long
                    if (substr($entry_path, -4) == '.zip') {
                        $public_files[$entry] = $entry_path;
                    } elseif (self::checkFileContent($entry_path)) {
                        $public_files[$entry] = $entry_path;
                    }
                }
            }
        }
        return $public_files;
    }

    protected static function checkFileContent($entry_path)
    {
        $ret = false;

        $fp  = fopen($entry_path, 'rb');
        $ret = strpos(fgets($fp), '///DOTCLEAR|') === 0;
        fclose($fp);

        return $ret;
    }

    private function unzip($file)
    {
        $zip = new fileUnzip($file);

        if ($zip->isEmpty()) {
            $zip->close();
            return false; //throw new Exception(__('File is empty or not a compressed file.'));
        }

        foreach ($zip->getFilesList() as $zip_file) {
            # Check zipped file name
            if (substr($zip_file, -4) != '.txt') {
                continue;
            }

            # Check zipped file contents
            $content = $zip->unzip($zip_file);
            if (strpos($content, '///DOTCLEAR|') !== 0) {
                unset($content);
                continue;
            }

            $target = path::fullFromRoot($zip_file, dirname($file));

            # Check existing files with same name
            if (file_exists($target)) {
                $zip->close();
                unset($content);
                throw new Exception(__('Another file with same name exists.'));
            }

            # Extract backup content
            if (file_put_contents($target, $content) === false) {
                $zip->close();
                unset($content);
                throw new Exception(__('Failed to extract backup file.'));
            }

            $zip->close();
            unset($content);

            # Return extracted file name
            return $target;
        }

        $zip->close();
        throw new Exception(__('No backup in compressed file.'));
    }
}
