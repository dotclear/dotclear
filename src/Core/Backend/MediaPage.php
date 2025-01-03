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
use Dotclear\Helper\File\File;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief   class for admin media page
 */
#[\AllowDynamicProperties]
class MediaPage extends FilterMedia
{
    /**
     * @var bool Page has a valid query
     */
    protected bool $media_has_query = false;

    /**
     * @var bool Media dir is writable
     */
    protected bool $media_writable = false;

    /**
     * @var bool Media dir is archivable
     */
    protected ?bool $media_archivable = null;

    /**
     * @var array<string, mixed> Dirs and files File objects
     */
    protected ?array $media_dir = null;

    /**
     * @var array<string> User media recents
     */
    protected ?array $media_last = null;

    /**
     * @var array<string> User media favorites
     */
    protected ?array $media_fav = null;

    /**
     * @var bool Uses enhance uploader
     */
    protected ?bool $media_uploader = null;

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        parent::__construct('media');

        $this->media_uploader = App::auth()->prefs()->interface->enhanceduploader;

        // try to load core media and themes
        try {
            App::media()->setFilterMimeType($this->file_type ?? '');
            App::media()->setFileSort($this->sortby . '-' . $this->order);

            if ($this->q != '') {
                $this->media_has_query = App::media()->searchMedia($this->q);
            }
            if (!$this->media_has_query) {
                $try_d = $this->d;
                // Reset current dir
                $this->d = null;
                // Change directory (may cause an exception if directory doesn't exist)
                App::media()->chdir($try_d);
                // Restore current dir variable
                $this->d = $try_d;
                App::media()->getDir();
            } else {
                $this->d = null;
                App::media()->chdir('');
            }
            $this->media_writable = App::media()->writable();
            $this->media_dir      = &App::media()->dir;

            if (App::themes()->isEmpty()) {
                # -- Loading themes, may be useful for some configurable theme --
                App::themes()->loadModules(App::blog()->themesPath(), 'admin', App::lang()->getLang());
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
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
                && ($rs->count() !== 0 && !($rs->count() === 1 && $rs->parent));
        }

        return $this->media_archivable;
    }

    /**
     * Return list of File objects of current dir
     *
     * @param string $type  dir, file, all type
     *
     * @return null|array<string, mixed> Dirs and/or files File objects
     */
    public function getDirs(string $type = ''): ?array
    {
        if ($type !== '') {
            return $this->media_dir[$type] ?? null;
        }

        return $this->media_dir;
    }

    /**
     * Return MetaRecord instance of File objects
     *
     * @return MetaRecord Dirs and/or files File objects
     */
    public function getDirsRecord(): MetaRecord
    {
        $items = [];

        if ($dir = $this->media_dir) {
            // Remove hidden directories (unless DC_SHOW_HIDDEN_DIRS is set to true)
            if (!App::config()->showHiddenDirs()) {
                for ($i = (is_countable($dir['dirs']) ? count($dir['dirs']) : 0) - 1; $i >= 0; $i--) {
                    if ($dir['dirs'][$i]->d && str_starts_with($dir['dirs'][$i]->basename, '.')) {
                        unset($dir['dirs'][$i]);
                    }
                }
            }
            $items = array_values(array_merge($dir['dirs'], $dir['files']));

            // Transform each File array value to associative array if necessary
            $items = array_map(fn ($v): mixed => $v instanceof File ? (array) $v : $v, $items);
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

        return $file instanceof File ? ListingMedia::mediaLine($this, $file, 1, $this->media_has_query) : '';
    }

    /**
     * Show enhance uploader
     *
     * @return boolean Show enhance uploader
     */
    public function showUploader(): bool
    {
        return (bool) $this->media_uploader;
    }

    /**
     * Number of recent/fav dirs to show
     *
     * @return integer Nb of dirs
     */
    public function showLast(): int
    {
        return abs((int) App::auth()->prefs()->interface->media_nb_last_dirs);
    }

    /**
     * Return list of last dirs
     *
     * @return array<string> Last dirs
     */
    public function getLast(): array
    {
        if ($this->media_last === null) {
            $m = App::auth()->prefs()->interface->media_last_dirs;
            if (!is_array($m)) {
                $m = [];
            }
            $this->media_last = $m;
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
                unset($last_dirs[array_search($dir, $last_dirs)]);
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
            unset($last_dirs[array_search($dir, $last_dirs)]);
            array_unshift($last_dirs, $dir);
            $done = true;
        }

        if ($done) {
            $this->media_last = $last_dirs;
            App::auth()->prefs()->interface->put('media_last_dirs', $last_dirs, 'array');
        }

        return $done;
    }

    /**
     * Return list of fav dirs
     *
     * @return array<string> Fav dirs
     */
    public function getFav(): array
    {
        if ($this->media_fav === null) {
            $m = App::auth()->prefs()->interface->media_fav_dirs;
            if (!is_array($m)) {
                $m = [];
            }
            $this->media_fav = $m;
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
            unset($fav_dirs[array_search($dir, $fav_dirs)]);
            $done = true;
        }

        if ($done) {
            $this->media_fav = $fav_dirs;
            App::auth()->prefs()->interface->put('media_fav_dirs', $fav_dirs, 'array');
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
            Page::openPopup(__('Media manager'), $header, $breadcrumb);
        } else {
            Page::open(__('Media manager'), $header, $breadcrumb);
        }
    }

    /**
     * The end of media page or popup
     */
    public function closePage(): void
    {
        if ($this->popup) {
            Page::closePopup();
        } else {
            Page::helpBlock('core_media');
            Page::close();
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

            if ($this->media_has_query || $this->q) {
                $count = $this->media_has_query ? count((array) $this->getDirs('files')) : 0;

                $element[__('Search:') . ' ' . $this->q . ' (' . sprintf(__('%s file found', '%s files found', $count), $count) . ')'] = '';
            } else {
                $bc_url   = App::backend()->url()->get('admin.media', [...$this->values(), 'd' => '%s'], '&amp;', true);
                $bc_media = App::media()->breadCrumb($bc_url, '<span class="page-title">%s</span>');
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

        return Page::breadcrumb(array_merge($elements, $element), array_merge($options, $option));
    }
}
