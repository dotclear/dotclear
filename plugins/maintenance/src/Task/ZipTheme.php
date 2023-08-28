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
declare(strict_types=1);

namespace Dotclear\Plugin\maintenance\Task;

use dcCore;
use Dotclear\Core\Core;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\File\Zip\Zip;
use Dotclear\Plugin\maintenance\MaintenanceTask;

class ZipTheme extends MaintenanceTask
{
    protected $id = 'dcMaintenanceZiptheme';

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
        $this->task = __('Download active theme of current blog');

        $this->description = __('It may be useful to backup the active theme before any change or update. This compress theme folder into a single zip file.');
    }

    /**
     * Execute task.
     *
     * @return    bool|int
     *    - FALSE on error,
     *    - TRUE if task is finished
     *    - INT if task required a next step
     */
    public function execute()
    {
        // Get theme path
        $path  = Core::blog()->themes_path;
        $theme = Core::blog()->settings->system->theme;
        $dir   = Path::real($path . '/' . $theme);
        if (empty($path) || empty($theme) || !is_dir($dir)) {
            return false;
        }

        // Create zip
        @set_time_limit(300);
        $fp  = fopen('php://output', 'wb');
        $zip = new Zip($fp);

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
