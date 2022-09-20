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
if (!defined('DC_RC_PATH')) {
    return;
}

class dcMaintenanceZipmedia extends dcMaintenanceTask
{
    protected $perm  = 'admin';
    protected $blog  = true;
    protected $tab   = 'backup';
    protected $group = 'zipblog';

    protected function init()
    {
        $this->task = __('Download media folder of current blog');

        $this->description = __('It may be useful to backup your media folder. This compress all content of media folder into a single zip file. Notice : with some hosters, the media folder cannot be compressed with this plugin if it is too big.');
    }

    public function execute()
    {
        // Instance media
        dcCore::app()->media = new dcMedia();
        dcCore::app()->media->chdir('');
        dcCore::app()->media->getDir();

        // Create zip
        @set_time_limit(300);
        $fp  = fopen('php://output', 'wb');
        $zip = new fileZip($fp);
        $zip->addExclusion('#(^|/).(.*?)_(m|s|sq|t).jpg$#');
        $zip->addDirectory(dcCore::app()->media->root . '/', '', true);

        // Log task execution here as we sent file and stop script
        $this->log();

        // Send zip
        header('Content-Disposition: attachment;filename=' . date('Y-m-d') . '-' . dcCore::app()->blog->id . '-' . 'media.zip');
        header('Content-Type: application/x-zip');
        $zip->write();
        unset($zip);
        exit(1);
    }
}
