<?php

/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend;

use Dotclear\App;
use Dotclear\Core\Backend\Filter\FilterMedia;
use Dotclear\Core\Backend\Listing\ListingMedia;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\File\MediaFile;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief   class for admin media page
 */
#[\AllowDynamicProperties]
class MediaPage extends FilterMedia
{
    /**
     * Page has a valid query
     */
    protected bool $media_has_query = false;

    /**
     * Media dir is writable
     */
    protected bool $media_writable = false;

    /**
     * Media dir is archivable
     */
    protected ?bool $media_archivable = null;

    /**
     * Dirs and files MediaFile objects
     *
     * @var array{dirs: MediaFile[], files: MediaFile[]} $media_dir
     */
    protected ?array $media_dir = null;

    /**
     * User media recents
     *
     * @var string[] $media_last
     */
    protected array $media_last;

    /**
     * User media favorites
     *
     * @var string[] $media_fav
     */
    protected array $media_fav;

    /**
     * Uses enhance uploader
     */
    protected bool $media_uploader;

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->media_uploader = (bool) App::auth()->prefs()->interface->enhanceduploader;

        // try to load core media and themes
        try {
            $file_type = is_string($file_type = $this->file_type) ? $file_type : '';
            $sortby    = is_string($sortby = $this->sortby) ? $sortby : '';
            $order     = is_string($order = $this->order) ? $order : '';

            App::media()->setFilterMimeType($file_type);
            App::media()->setFileSort($sortby . '-' . $order);

            $query = is_string($query = $this->q) ? $query : '';
            if ($query !== '') {
                $this->media_has_query = App::media()->searchMedia($query);
            }

            if (!$this->media_has_query) {
                // Get last dir from user
                $last_dir = is_string($last_dir = App::auth()->prefs()->interface->media_last_dir) ? $last_dir : '';

                // Use current dir if any else use user one
                $try_d = is_string($try_d = $this->d) ? $try_d : $last_dir;

                // Reset current dir
                $this->d = null;

                // Change directory (may cause an exception if directory doesn't exist)
                App::media()->chdir($try_d);

                // Restore current dir variable
                $this->d = $try_d;

                // Get directory content
                App::media()->getDir();

                if ($try_d !== $last_dir) {
                    // Store current dir for user
                    App::auth()->prefs()->interface->put('media_last_dir', $try_d, App::userWorkspace()::WS_STRING);
                }
            } else {
                $this->d = null;
                App::media()->chdir('');
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());

            // Back to media root directory
            $this->d = null;
            App::media()->chdir('');
        }

        $this->media_writable = App::media()->writable();
        $this->media_dir      = [
            'dirs'  => App::media()->getDirs(),
            'files' => App::media()->getFiles(),
        ];

        if (App::themes()->isEmpty()) {
            // Load themes, may be useful for some configurable one
            App::themes()->loadModules(App::blog()->themesPath(), 'admin', App::lang()->getLang());
        }
    }

    /**
     * Check if page has a valid query
     *
     * @return boolean Has query
     */
    public function hasQuery(): bool
    {
        return $this->media_has_query;
    }

    /**
     * Check if page has a current dir
     *
     * @return ?string default dir
     */
    public function currentDir(): ?string
    {
        return is_string($this->d) ? $this->d : null;
    }

    /**
     * Check if media dir is writable
     *
     * @return boolean Is writable
     */
    public function mediaWritable(): bool
    {
        return $this->media_writable;
    }

    /**
     * Check if media dir is archivable
     *
     * @return boolean Is archivable
     */
    public function mediaArchivable(): bool
    {
        if ($this->media_archivable === null) {
            $rs = $this->getDirsRecord();

            $this->media_archivable = App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_MEDIA_ADMIN,
            ]), App::blog()->id())
                && ($rs->count() !== 0 && ($rs->count() !== 1 || !$rs->parent));
        }

        return $this->media_archivable;
    }

    /**
     * Return list of MediaFile objects of current dir
     *
     * @param string $type  dir, file, all type
     *
     * @return ($type is '' ? null|array{dirs: MediaFile[], files: MediaFile[]} : null|MediaFile[]) Dirs and/or files MediaFile objects
     */
    public function getDirs(string $type = ''): ?array
    {
        if ($type !== '') {
            return $this->media_dir[$type] ?? null;
        }

        return $this->media_dir;
    }

    /**
     * Return MetaRecord instance of MediaFile objects
     *
     * @return MetaRecord Dirs and/or files MediaFile objects
     */
    public function getDirsRecord(): MetaRecord
    {
        $items = [];

        $list = $this->media_dir;
        if ($list !== null) {
            // Remove hidden directories (unless DC_SHOW_HIDDEN_DIRS is set to true)
            if (!App::config()->showHiddenDirs()) {
                $count = count($list['dirs']);
                if ($count > 0) {
                    for ($index = $count - 1; $index >= 0; $index--) {
                        if ($list['dirs'][$index]->d
                            && str_starts_with($list['dirs'][$index]->basename, '.')
                        ) {
                            unset($list['dirs'][$index]);
                        }
                    }
                }
            }

            /**
             * @var MediaFile[] $items
             */
            $items = [
                ... $list['dirs'],
                ... $list['files'],
            ];

            // Transform each File array value to associative array if necessary
            $items = array_map(fn (MediaFile $v): mixed => (array) $v, $items);
        }

        return MetaRecord::newFromArray($items);
    }

    /**
     * Return HTML code of an element of list or grid items list
     *
     * @param string $file_id  The file id
     *
     * @return string The element
     */
    public function mediaLine(string $file_id): string
    {
        $file = App::media()->getFile((int) $file_id);

        return $file instanceof MediaFile ? ListingMedia::mediaLine($this, $file, 1, $this->media_has_query) : '';
    }

    /**
     * Show enhance uploader
     *
     * @return boolean Show enhance uploader
     */
    public function showUploader(): bool
    {
        return $this->media_uploader;
    }

    /**
     * Number of recent/fav dirs to show
     *
     * @return integer Nb of dirs
     */
    public function showLast(): int
    {
        return is_numeric($nb = App::auth()->prefs()->interface->media_nb_last_dirs) ? abs((int) $nb) : 0;
    }

    /**
     * Return list of last dirs
     *
     * @return string[] Last dirs
     */
    public function getLast(): array
    {
        if (!isset($this->media_last)) {
            $list   = [];
            $values = App::auth()->prefs()->interface->media_last_dirs;
            if (is_array($values)) {
                foreach ($values as $value) {
                    if (is_string($value)) {
                        $list[] = $value;
                    }
                }
            }
            $this->media_last = $list;
        }

        return $this->media_last;
    }

    /**
     * Update user last dirs
     *
     * @param string    $dir        The directory
     * @param boolean   $remove     Remove
     *
     * @return boolean The change
     */
    public function updateLast(string $dir, bool $remove = false): bool
    {
        if ($this->q) {
            return false;
        }

        $nb_last_dirs = $this->showLast();
        if ($nb_last_dirs === 0) {
            return false;
        }

        $done      = false;
        $last_dirs = $this->getLast();

        if ($remove) {
            if (in_array($dir, $last_dirs)) {
                $index = array_search($dir, $last_dirs);
                if ($index !== false) {
                    unset($last_dirs[$index]);
                }
                $done = true;
            }
        } elseif (!in_array($dir, $last_dirs)) {
            // Add new dir at the top of the list
            array_unshift($last_dirs, $dir);
            // Remove oldest dir(s)
            while (count($last_dirs) > $nb_last_dirs) {
                array_pop($last_dirs);
            }
            $done = true;
        } else {
            // Move current dir at the top of list
            $index = array_search($dir, $last_dirs);
            if ($index !== false) {
                unset($last_dirs[$index]);
            }
            array_unshift($last_dirs, $dir);
            $done = true;
        }

        if ($done) {
            $this->media_last = $last_dirs;
            App::auth()->prefs()->interface->put('media_last_dirs', $last_dirs, App::userWorkspace()::WS_ARRAY);
        }

        return $done;
    }

    /**
     * Return list of fav dirs
     *
     * @return string[] Fav dirs
     */
    public function getFav(): array
    {
        if (!isset($this->media_fav)) {
            $list   = [];
            $values = App::auth()->prefs()->interface->media_fav_dirs;
            if (is_array($values)) {
                foreach ($values as $value) {
                    if (is_string($value)) {
                        $list[] = $value;
                    }
                }
            }
            $this->media_fav = $list;
        }

        return $this->media_fav;
    }

    /**
     * Update user fav dirs
     *
     * @param string    $dir        The directory
     * @param boolean   $remove     Remove
     *
     * @return boolean The change
     */
    public function updateFav(string $dir, bool $remove = false): bool
    {
        if ($this->q) {
            return false;
        }

        $nb_last_dirs = $this->showLast();
        if ($nb_last_dirs === 0) {
            return false;
        }

        $done     = false;
        $fav_dirs = $this->getFav();
        if (!in_array($dir, $fav_dirs) && !$remove) {
            array_unshift($fav_dirs, $dir);
            $done = true;
        } elseif (in_array($dir, $fav_dirs) && $remove) {
            $index = array_search($dir, $fav_dirs);
            if ($index !== false) {
                unset($fav_dirs[$index]);
            }
            $done = true;
        }

        if ($done) {
            $this->media_fav = $fav_dirs;
            App::auth()->prefs()->interface->put('media_fav_dirs', $fav_dirs, App::userWorkspace()::WS_ARRAY);
        }

        return $done;
    }

    /**
     * The top of media page or popup
     *
     * @param string $breadcrumb    The breadcrumb
     * @param string $header        The headers
     */
    public function openPage(string $breadcrumb, string $header = ''): void
    {
        if ($this->popup) {
            App::backend()->page()->openPopup(__('Media manager'), $header, $breadcrumb);
        } else {
            App::backend()->page()->open(__('Media manager'), $header, $breadcrumb);
        }
    }

    /**
     * The end of media page or popup
     */
    public function closePage(): void
    {
        if ($this->popup) {
            App::backend()->page()->closePopup();
        } else {
            App::backend()->page()->helpBlock('core_media');
            App::backend()->page()->close();
        }
    }

    /**
     * The breadcrumb of media page or popup
     *
     * @param array<string, string> $element  The additionnal element
     *
     * @return string The HTML code of breadcrumb
     */
    public function breadcrumb(array $element = []): string
    {
        $option = $param = [];

        if ($element === []) {
            $param = [
                'd' => '',
                'q' => '',
            ];

            $query = is_string($query = $this->q) ? $query : '';
            if ($this->media_has_query || $query !== '') {
                $count = $this->media_has_query ? count((array) $this->getDirs('files')) : 0;

                $element[__('Search:') . ' ' . $query . ' (' . sprintf(__('%s file found', '%s files found', $count), $count) . ')'] = '';
            } else {
                $bc_url            = App::backend()->url()->get('admin.media', [...$this->values(), 'd' => '%s'], '&amp;', true);
                $last_item_pattern = (new Span('%s'))->class('page-title')->render();
                $bc_media          = App::media()->breadCrumb($bc_url, $last_item_pattern);
                if ($bc_media !== '') {
                    $element[$bc_media] = '';
                    $option['hl']       = true;
                }
            }
        }

        $elements = [
            Html::escapeHTML(App::blog()->name()) => '',
            __('Media manager')                   => $param === [] ? '' :
                App::backend()->url()->get('admin.media', array_merge($this->values(), [...$this->values(), ...$param])),
        ];
        $options = [
            'home_link' => !$this->popup,
        ];

        return App::backend()->page()->breadcrumb(array_merge($elements, $element), array_merge($options, $option));
    }
}
