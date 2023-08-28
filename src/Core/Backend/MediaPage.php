<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * URL Handler for admin urls
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend;

use dcCore;
use dcMedia;
use dcThemes;
use Dotclear\Core\Backend\Filter\FilterMedia;
use Dotclear\Core\Backend\Listing\ListingMedia;
use Dotclear\Core\Core;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\File\File;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief class for admin media page
 */
#[\AllowDynamicProperties]
class MediaPage extends FilterMedia
{
    /** @var boolean Page has a valid query */
    protected $media_has_query = false;

    /** @var boolean Media dir is writable */
    protected $media_writable = false;

    /** @var boolean Media dir is archivable */
    protected $media_archivable = null;

    /** @var array Dirs and files File objects */
    protected $media_dir = null;

    /** @var array User media recents */
    protected $media_last = null;

    /** @var array User media favorites */
    protected $media_fav = null;

    /** @var boolean Uses enhance uploader */
    protected $media_uploader = null;

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        parent::__construct('media');

        $this->media_uploader = Core::auth()->user_prefs->interface->enhanceduploader;

        // try to load core media and themes
        try {
            dcCore::app()->media = new dcMedia($this->file_type ?? '');
            dcCore::app()->media->setFileSort($this->sortby . '-' . $this->order);

            if ($this->q != '') {
                $this->media_has_query = dcCore::app()->media->searchMedia($this->q);
            }
            if (!$this->media_has_query) {
                $try_d = $this->d;
                // Reset current dir
                $this->d = null;
                // Change directory (may cause an exception if directory doesn't exist)
                dcCore::app()->media->chdir($try_d);
                // Restore current dir variable
                $this->d = $try_d;
                dcCore::app()->media->getDir();
            } else {
                $this->d = null;
                dcCore::app()->media->chdir('');
            }
            $this->media_writable = dcCore::app()->media->writable();
            $this->media_dir      = &dcCore::app()->media->dir;

            if (dcCore::app()->themes === null) {
                # -- Loading themes, may be useful for some configurable theme --
                dcCore::app()->themes = new dcThemes();
                dcCore::app()->themes->loadModules(Core::blog()->themes_path, 'admin', dcCore::app()->lang);
            }
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
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

            $this->media_archivable = Core::auth()->check(Core::auth()->makePermissions([
                Core::auth()::PERMISSION_MEDIA_ADMIN,
            ]), Core::blog()->id)
                && !((is_countable($rs) ? count($rs) : 0) === 0 || ((is_countable($rs) ? count($rs) : 0) === 1 && $rs->parent)); // @phpstan-ignore-line
        }

        return $this->media_archivable;
    }

    /**
     * Return list of File objects of current dir
     *
     * @param string $type  dir, file, all type
     *
     * @return null|array Dirs and/or files File objects
     */
    public function getDirs(string $type = ''): ?array
    {
        if (!empty($type)) {
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
        $dir = $this->media_dir;
        // Remove hidden directories (unless DC_SHOW_HIDDEN_DIRS is set to true)
        if (!defined('DC_SHOW_HIDDEN_DIRS') || (!DC_SHOW_HIDDEN_DIRS)) {
            for ($i = (is_countable($dir['dirs']) ? count($dir['dirs']) : 0) - 1; $i >= 0; $i--) {
                if ($dir['dirs'][$i]->d && strpos($dir['dirs'][$i]->basename, '.') === 0) {
                    unset($dir['dirs'][$i]);
                }
            }
        }
        $items = array_values(array_merge($dir['dirs'], $dir['files']));

        // Transform each File array value to associative array if necessary
        $items = array_map(fn ($v) => $v instanceof File ? (array) $v : $v, $items);

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
        return ListingMedia::mediaLine($this, dcCore::app()->media->getFile((int) $file_id), 1, $this->media_has_query);
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
        return abs((int) Core::auth()->user_prefs->interface->media_nb_last_dirs);
    }

    /**
     * Return list of last dirs
     *
     * @return array Last dirs
     */
    public function getLast(): array
    {
        if ($this->media_last === null) {
            $m = Core::auth()->user_prefs->interface->media_last_dirs;
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
        if (!$nb_last_dirs) {
            return false;
        }

        $done      = false;
        $last_dirs = $this->getLast();

        if ($remove) {
            if (in_array($dir, $last_dirs)) {
                unset($last_dirs[array_search($dir, $last_dirs)]);
                $done = true;
            }
        } else {
            if (!in_array($dir, $last_dirs)) {
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
        }

        if ($done) {
            $this->media_last = $last_dirs;
            Core::auth()->user_prefs->interface->put('media_last_dirs', $last_dirs, 'array');
        }

        return $done;
    }

    /**
     * Return list of fav dirs
     *
     * @return array Fav dirs
     */
    public function getFav(): array
    {
        if ($this->media_fav === null) {
            $m = Core::auth()->user_prefs->interface->media_fav_dirs;
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
        if (!$nb_last_dirs) {
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
            Core::auth()->user_prefs->interface->put('media_fav_dirs', $fav_dirs, 'array');
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
     * @param array $element  The additionnal element
     *
     * @return string The HTML code of breadcrumb
     */
    public function breadcrumb(array $element = []): string
    {
        $option = $param = [];

        if (empty($element) && isset(dcCore::app()->media)) {
            $param = [
                'd' => '',
                'q' => '',
            ];

            if ($this->media_has_query || $this->q) {
                $count = $this->media_has_query ? count($this->getDirs('files')) : 0;

                $element[__('Search:') . ' ' . $this->q . ' (' . sprintf(__('%s file found', '%s files found', $count), $count) . ')'] = '';
            } else {
                $bc_url   = Core::backend()->url->get('admin.media', array_merge($this->values(), ['d' => '%s']), '&amp;', true);
                $bc_media = dcCore::app()->media->breadCrumb($bc_url, '<span class="page-title">%s</span>');
                if ($bc_media != '') {
                    $element[$bc_media] = '';
                    $option['hl']       = true;
                }
            }
        }

        $elements = [
            Html::escapeHTML(Core::blog()->name) => '',
            __('Media manager')                         => empty($param) ? '' :
                Core::backend()->url->get('admin.media', array_merge($this->values(), array_merge($this->values(), $param))),
        ];
        $options = [
            'home_link' => !$this->popup,
        ];

        return Page::breadcrumb(array_merge($elements, $element), array_merge($options, $option));
    }
}
