<?php
/**
 * @brief maintenance, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

class dcMaintenanceZiptheme extends dcMaintenanceTask
{
    protected $perm  = 'admin';
    protected $blog  = true;
    protected $tab   = 'backup';
    protected $group = 'zipblog';

    protected function init()
    {
        $this->task = __('Download active theme of current blog');

        $this->description = __('It may be useful to backup the active theme before any change or update. This compress theme folder into a single zip file.');
    }

    public function execute()
    {
        // Get theme path
        $path  = $this->core->blog->themes_path;
        $theme = $this->core->blog->settings->system->theme;
        $dir   = path::real($path . '/' . $theme);
        if (empty($path) || empty($theme) || !is_dir($dir)) {
            return false;
        }

        // Create zip
        @set_time_limit(300);
        $fp  = fopen('php://output', 'wb');
        $zip = new fileZip($fp);
        $zip->addExclusion('#(^|/).(.*?)_(m|s|sq|t).jpg$#');
        $zip->addDirectory($dir . '/', '', true);

        // Log task execution here as we sent file and stop script
        $this->log();

        // Send zip
        header('Content-Disposition: attachment;filename=theme-' . $theme . '.zip');
        header('Content-Type: application/x-zip');
        $zip->write();
        unset($zip);
        exit(1);
    }
}
