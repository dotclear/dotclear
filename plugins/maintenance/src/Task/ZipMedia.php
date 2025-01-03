<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\maintenance\Task;

use Dotclear\App;
use Dotclear\Helper\File\Zip\Zip;
use Dotclear\Plugin\maintenance\MaintenanceTask;

/**
 * @brief   The media zip maintenance task.
 * @ingroup maintenance
 */
class ZipMedia extends MaintenanceTask
{
    /**
     * Task ID (class name).
     */
    protected ?string $id = 'dcMaintenanceZipmedia';

    /**
     * Task permissions.
     */
    protected ?string $perm = 'admin';

    /**
     * Task limited to current blog.
     */
    protected bool $blog = true;

    /**
     * Task tab container.
     */
    protected string $tab = 'backup';

    /**
     * Task group container.
     */
    protected string $group = 'zipblog';

    /**
     * Initialize task object.
     */
    protected function init(): void
    {
        $this->task = __('Download media folder of current blog');

        $this->description = __('It may be useful to backup your media folder. This compress all content of media folder into a single zip file. Notice : with some hosters, the media folder cannot be compressed with this plugin if it is too big.');
    }

    /**
     * Zip media folder
     */
    public function execute(): never
    {
        // Instance media
        App::media()->chdir('');
        App::media()->getDir();

        // Create zip
        @set_time_limit(300);
        $fp  = fopen('php://output', 'wb');
        $zip = new Zip($fp);

        $thumb_sizes  = implode('|', array_keys(App::media()->getThumbSizes()));
        $thumb_prefix = App::media()->getThumbnailPrefix();
        // Exclude . (hidden files) and prefixed thumbnails (if necessary)
        $pattern_prefix = $thumb_prefix !== '.' ? sprintf('(\.|%s)', preg_quote($thumb_prefix)) : '\.';
        $zip->addExclusion('/(^|\/)' . $pattern_prefix . '(.*?)_(' . $thumb_sizes . ')\.(jpg|jpeg|png|webp|avif)$/');
        $zip->addExclusion('#(^|/)(__MACOSX|\.svn|\.hg.*|\.git.*|\.DS_Store|\.directory|Thumbs\.db)(/|$)#');

        $zip->addDirectory(App::media()->getRoot() . '/', '', true);

        // Log task execution here as we sent file and stop script
        $this->log();

        // Send zip
        header('Content-Disposition: attachment;filename=' . date('Y-m-d') . '-' . App::blog()->id() . '-' . 'media.zip');
        header('Content-Type: application/x-zip');

        $zip->write();
        unset($zip);
        exit(1);
    }
}
