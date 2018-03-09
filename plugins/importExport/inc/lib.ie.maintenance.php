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

class ieMaintenanceExportblog extends dcMaintenanceTask
{
    protected $perm  = 'admin';
    protected $tab   = 'backup';
    protected $group = 'zipblog';

    protected $export_name;
    protected $export_type;

    protected function init()
    {
        $this->name = __('Database export');
        $this->task = __('Download database of current blog');

        $this->export_name = html::escapeHTML($this->core->blog->id . '-backup.txt');
        $this->export_type = 'export_blog';
    }

    public function execute()
    {
        // Create zip file
        if (!empty($_POST['file_name'])) {
            // This process make an http redirect
            $ie = new maintenanceDcExportFlat($this->core);
            $ie->setURL($this->id);
            $ie->process($this->export_type);
        }
        // Go to step and show form
        else {
            return 1;
        }
    }

    public function step()
    {
        // Download zip file
        if (isset($_SESSION['export_file']) && file_exists($_SESSION['export_file'])) {
            // Log task execution here as we sent file and stop script
            $this->log();

            // This process send file by http and stop script
            $ie = new maintenanceDcExportFlat($this->core);
            $ie->setURL($this->id);
            $ie->process('ok');
        } else {
            return
            '<p><label for="file_name">' . __('File name:') . '</label>' .
            form::field('file_name', 50, 255, date('Y-m-d-H-i-') . $this->export_name) .
            '</p>' .
            '<p><label for="file_zip" class="classic">' .
            form::checkbox('file_zip', 1) . ' ' .
            __('Compress file') . '</label>' .
                '</p>';
        }
    }
}

class ieMaintenanceExportfull extends dcMaintenanceTask
{
    protected $tab   = 'backup';
    protected $group = 'zipfull';

    protected $export_name;
    protected $export_type;

    protected function init()
    {
        $this->name = __('Database export');
        $this->task = __('Download database of all blogs');

        $this->export_name = 'dotclear-backup.txt';
        $this->export_type = 'export_all';
    }

    public function execute()
    {
        // Create zip file
        if (!empty($_POST['file_name'])) {
            // This process make an http redirect
            $ie = new maintenanceDcExportFlat($this->core);
            $ie->setURL($this->id);
            $ie->process($this->export_type);
        }
        // Go to step and show form
        else {
            return 1;
        }
    }

    public function step()
    {
        // Download zip file
        if (isset($_SESSION['export_file']) && file_exists($_SESSION['export_file'])) {
            // Log task execution here as we sent file and stop script
            $this->log();

            // This process send file by http and stop script
            $ie = new maintenanceDcExportFlat($this->core);
            $ie->setURL($this->id);
            $ie->process('ok');
        } else {
            return
            '<p><label for="file_name">' . __('File name:') . '</label>' .
            form::field('file_name', 50, 255, date('Y-m-d-H-i-') . $this->export_name) .
            '</p>' .
            '<p><label for="file_zip" class="classic">' .
            form::checkbox('file_zip', 1) . ' ' .
            __('Compress file') . '</label>' .
                '</p>';
        }
    }
}

class maintenanceDcExportFlat extends dcExportFlat
{
    /**
     * Set redirection URL of bakcup process.
     *
     * Bad hack to change redirection of dcExportFlat::process()
     *
     * @param    id    <b>string</b>    Task id
     */
    public function setURL($id)
    {
        $this->url = sprintf('plugin.php?p=maintenance&task=%s', $id);
    }
}
