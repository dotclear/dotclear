<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend\Filter;

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\Html\Html;

/**
 * @brief   Media list filters form helper.
 *
 * @since   2.20
 */
class FilterMedia extends Filters
{
    /**
     * The post type.
     *
     * @var     string  $post_type
     */
    protected $post_type = '';

    /**
     * The post_title.
     *
     * @var     string  $post_title
     */
    protected $post_title = '';

    /** @var    string  media grid display mode */
    public const MODE_GRID = 'grid';

    /** @var    string  media list display mode */
    public const MODE_LIST = 'list';

    public function __construct(string $type = 'media')
    {
        parent::__construct($type);

        $filters = new ArrayObject([
            FiltersLibrary::getPageFilter(),
            FiltersLibrary::getSearchFilter(),

            $this->getPostIdFilter(),
            $this->getDirFilter(),
            $this->getFileModeFilter(),
            $this->getFileTypeFilter(),
            $this->getPluginIdFilter(),
            $this->getLinkTypeFilter(),
            $this->getPopupFilter(),
            $this->getSelectFilter(),
        ]);

        # --BEHAVIOR-- adminMediaFilter -- ArrayObject
        App::behavior()->callBehavior('adminMediaFilterV2', $filters);

        $filters = $filters->getArrayCopy();

        $this->add($filters);

        $this->legacyBehavior();
    }

    /**
     * Cope with old behavior.
     */
    protected function legacyBehavior(): void
    {
        $values = new ArrayObject($this->values());

        # --BEHAVIOR-- adminMediaURLParams -- ArrayObject
        App::behavior()->callBehavior('adminMediaURLParams', $values);

        foreach ($values->getArrayCopy() as $filter => $new_value) {
            if (isset($this->filters[$filter])) {
                $this->filters[$filter]->value($new_value);
            } else {
                $this->add($filter, $new_value);
            }
        }
    }

    protected function getPostIdFilter(): Filter
    {
        $post_id = !empty($_REQUEST['post_id']) ? (int) $_REQUEST['post_id'] : null;
        if ($post_id) {
            $post = App::blog()->getPosts(['post_id' => $post_id, 'post_type' => '']);
            if ($post->isEmpty()) {
                $post_id = null;
            }
            // keep track of post_title_ and post_type without using filters
            $this->post_title = $post->post_title;
            $this->post_type  = $post->post_type;
        }

        return new Filter('post_id', $post_id);
    }

    public function getPostTitle(): string
    {
        return $this->post_title;
    }

    public function getPostType(): string
    {
        return $this->post_type;
    }

    protected function getDirFilter(): Filter
    {
        $get = $_REQUEST['d'] ?? App::auth()->prefs()->interface->media_manager_dir ?? null;
        if ($get) {
            // Store current dir in user pref
            App::auth()->prefs()->interface->put('media_manager_dir', $get, 'string');
        } else {
            // Remove current dir from user pref
            App::auth()->prefs()->interface->drop('media_manager_dir');
        }

        return new Filter('d', $get);
    }

    protected function getFileModeFilter(): Filter
    {
        $get = $_REQUEST['file_mode'] ?? $get = App::auth()->prefs()->interface->media_file_mode ?? null;
        if ($get) {
            // Store current view in user pref
            App::auth()->prefs()->interface->put('media_file_mode', $get, 'string');
        } else {
            // Remove current view from user pref
            App::auth()->prefs()->interface->drop('media_file_mode');
            $get = self::MODE_GRID;
        }

        return new Filter('file_mode', $get);
    }

    protected function getFileTypeFilter(): Filter
    {
        return (new Filter('file_type'))
            ->title(__('Media type:'))
            ->options(array_merge(
                ['-' => ''],
                [
                    __('image') => 'image',
                    __('text')  => 'text',
                    __('audio') => 'audio',
                    __('video') => 'video',
                ]
            ))
            ->prime(true);
    }

    protected function getPluginIdFilter(): Filter
    {
        $get = isset($_REQUEST['plugin_id']) ? Html::sanitizeURL($_REQUEST['plugin_id']) : '';

        return new Filter('plugin_id', $get);
    }

    protected function getLinkTypeFilter(): Filter
    {
        $get = !empty($_REQUEST['link_type']) ? Html::escapeHTML($_REQUEST['link_type']) : null;

        return new Filter('link_type', $get);
    }

    protected function getPopupFilter(): Filter
    {
        $get = (int) !empty($_REQUEST['popup']);

        return new Filter('popup', $get);
    }

    protected function getSelectFilter(): Filter
    {
        // 0 : none, 1 : single media, >1 : multiple media
        $get = !empty($_REQUEST['select']) ? (int) $_REQUEST['select'] : 0;

        return new Filter('select', $get);
    }
}
