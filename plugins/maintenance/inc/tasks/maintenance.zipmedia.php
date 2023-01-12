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
class dcMaintenanceZipmedia extends dcMaintenanceTask
{
    /**
     * Task permissions
     *
     * @var null|string
     */
    protected $perm = 'admin';

    /**
     * Task limited to current blog
     *
     * @var bool
     */
    protected $blog = true;

    /**
     * Task tab container
     *
     * @var string
     */
    protected $tab = 'backup';

    /**
     * Task group container
     *
     * @var string
     */
    protected $group = 'zipblog';

    /**
     * Initialize task object.
     */
    protected function init(): void
    {
        $this->task = __('Download media folder of current blog');

        $this->description = __('It may be useful to backup your media folder. This compress all content of media folder into a single zip file. Notice : with some hosters, the media folder cannot be compressed with this plugin if it is too big.');
    }

    /**
     * Execute task.
     *
     * @return never
     *    - FALSE on error,
     *    - TRUE if task is finished
     *    - INT if task required a next step
     */
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
