<?php

/**
 * @package     Dotclear
 * @subpackage  Backend
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend\Filter;

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Stack\Filter;

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

    /**
     * @var    string  MODE_GRID
     *
     * Media grid display mode
     */
    public const MODE_GRID = 'grid';

    /**
     * @var    string  MODE_LIST
     *
     * Media list display mode
     */
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
        $post_id = isset($_REQUEST['post_id']) && is_numeric($post_id = $_REQUEST['post_id']) ? (int) $post_id : null;
        if ($post_id) {
            $post = App::blog()->getPosts(['post_id' => $post_id, 'post_type' => '']);
            if ($post->isEmpty()) {
                $post_id = null;
            }
            // keep track of post_title_ and post_type without using filters
            $this->post_title = $post->strField('post_title');
            $this->post_type  = $post->strField('post_type');
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
        $get = $_REQUEST['d'] ?? App::auth()->prefs()->get('interface')->getStr('media_manager_dir');

        // Remove previous current dir from user pref
        App::auth()->prefs()->get('interface')->drop('media_manager_dir');

        if ($get) {
            // Store current dir in user pref
            App::auth()->prefs()->get('interface')->put('media_manager_dir', $get, App::userWorkspace()::WS_STRING);
        }

        return new Filter('d', $get);
    }

    protected function getFileModeFilter(): Filter
    {
        $get = $_REQUEST['file_mode'] ?? App::auth()->prefs()->get('interface')->getStr('media_file_mode');

        // Remove previous current view from user pref
        App::auth()->prefs()->get('interface')->drop('media_file_mode');

        if ($get) {
            // Store current view in user pref
            App::auth()->prefs()->get('interface')->put('media_file_mode', $get, App::userWorkspace()::WS_STRING);
        } else {
            $get = self::MODE_GRID;
        }

        return new Filter('file_mode', $get);
    }

    protected function getFileTypeFilter(): Filter
    {
        return (new Filter('file_type'))
            ->title(__('Media type:'))
            ->options([
                '-'         => '',
                __('image') => 'image',
                __('text')  => 'text',
                __('audio') => 'audio',
                __('video') => 'video',
            ])
            ->prime(true);
    }

    protected function getPluginIdFilter(): Filter
    {
        $plugin_id = isset($_REQUEST['plugin_id']) && is_string($plugin_id = $_REQUEST['plugin_id']) ? Html::sanitizeURL($plugin_id) : '';

        return new Filter('plugin_id', $plugin_id);
    }

    protected function getLinkTypeFilter(): Filter
    {
        $link_type = isset($_REQUEST['link_type']) && is_string($link_type = $_REQUEST['link_type']) ? Html::escapeHTML($link_type) : '';

        return new Filter('link_type', $link_type);
    }

    protected function getPopupFilter(): Filter
    {
        $get = (int) !empty($_REQUEST['popup']);

        return new Filter('popup', $get);
    }

    protected function getSelectFilter(): Filter
    {
        // 0 : none, 1 : single media, >1 : multiple media
        $select = isset($_REQUEST['select']) && is_numeric($select = $_REQUEST['select']) ? (int) $select : 0;

        return new Filter('select', $select);
    }
}
