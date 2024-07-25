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

use Autoloader;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Form\Caption;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Details;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\File;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Password;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Summary;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Form\Url;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Text as Txt;
use Dotclear\Interface\Module\ModulesInterface;
use Dotclear\Module\ModuleDefine;
use Dotclear\Module\Store;
use Exception;

/**
 * Helper for admin list of plugins.
 *
 * Provides an object to parse XML feed of modules from a repository.
 *
 * @since 2.6
 */
class ModulesList
{
    /**
     * Stack of known modules
     *
     * @var ModulesInterface
     */
    public ModulesInterface $modules;

    /**
     * Store instance
     *
     * @var Store
     */
    public readonly Store $store;

    /**
     * Work with multiple root directories
     *
     * @var        bool
     */
    public static bool $allow_multi_install = false;

    /**
     * List of modules distributed with Dotclear
     *
     * @deprecated  since 2.26, use Modules::getDefine($id)->distributed instead
     *
     * @var        array<string>
     */
    public static array $distributed_modules = [];

    /**
     * Current list ID
     *
     * @var        string
     */
    protected string $list_id = 'unknown';

    /**
     * Current modules defines
     *
     * @var        array<ModuleDefine>
     */
    protected array $defines = [];

    /**
     * Module define to configure
     *
     * @var        ModuleDefine
     */
    protected ModuleDefine $config_define;
    /**
     * Module class to configure
     *
     * @var        string
     */
    protected string $config_class = '';
    /**
     * Module path to configure
     *
     * @var        string
     */
    protected string $config_file = '';
    /**
     * Module configuration page content
     *
     * @var        string
     */
    protected string $config_content = '';

    /**
     * Modules root directories
     *
     * @var        string|null
     */
    protected ?string $path;
    /**
     * Indicate if modules root directory is writable
     *
     * @var        bool
     */
    protected bool $path_writable = false;
    /**
     * Directory pattern to work on
     *
     * @var        string
     */
    protected string $path_pattern = '';

    /**
     * Page URL
     *
     * @var        string
     */
    protected string $page_url = '';
    /**
     * Page tab
     *
     * @var        string
     */
    protected string $page_tab = '';
    /**
     * Page redirection
     *
     * @var        string
     */
    protected string $page_redir = '';

    /**
     * Index list
     *
     * @var        string
     */
    public static string $nav_indexes = 'abcdefghijklmnopqrstuvwxyz0123456789';

    /**
     * Index list with special index
     *
     * @var        array<string>
     */
    protected array $nav_list = [];
    /**
     * Text for other special index
     *
     * @var        string
     */
    protected string $nav_special = 'other';

    /**
     * Field used to sort modules
     *
     * @var        string
     */
    protected string $sort_field = 'sname';
    /**
     * Ascendant sort order?
     *
     * @var        bool
     */
    protected bool $sort_asc = true;

    /**
     * Constructor.
     *
     * Note that this creates Store instance.
     *
     * @param    ModulesInterface   $modules        Modules instance
     * @param    string             $modules_root   Modules root directories
     * @param    null|string        $xml_url        URL of modules feed from repository
     * @param    null|bool          $force          Force query repository
     */
    public function __construct(ModulesInterface $modules, string $modules_root, ?string $xml_url, ?bool $force = false)
    {
        $this->modules = $modules;
        $this->store   = new Store($modules, $xml_url, $force);

        $this->page_url = App::backend()->url()->get('admin.plugins');

        $this->setPath($modules_root);
        $this->setIndex(__('other'));
    }

    /**
     * Begin a new list.
     *
     * @param    string    $id        New list ID
     *
     * @return    ModulesList self instance
     */
    public function setList(string $id): ModulesList
    {
        $this->defines  = [];
        $this->page_tab = '';
        $this->list_id  = $id;

        return $this;
    }

    /**
     * Get list ID.
     *
     * @return     string
     */
    public function getList(): string
    {
        return $this->list_id;
    }

    /// @name Modules root directory methods
    //@{

    /**
     * Set path info.
     *
     * @param    string    $root        Modules root directories
     *
     * @return    ModulesList self instance
     */
    protected function setPath(string $root): ModulesList
    {
        $paths = explode(PATH_SEPARATOR, $root);
        $path  = array_pop($paths);
        unset($paths);

        $this->path = $path;
        if (is_dir($path) && is_writeable($path)) {
            $this->path_writable = true;
            $this->path_pattern  = preg_quote($path, '!');
        }

        return $this;
    }

    /**
     * Get modules root directories.
     *
     * @return    null|string    directory to work on
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Check if modules root directory is writable.
     *
     * @return    bool  True if directory is writable
     */
    public function isWritablePath(): bool
    {
        return $this->path_writable;
    }

    /**
     * Check if root directory of a module is deletable.
     *
     * @param    string    $root        Module root directory
     *
     * @return    bool  True if directory is delatable
     */
    public function isDeletablePath(string $root): bool
    {
        return $this->path_writable
        && (preg_match('!^' . $this->path_pattern . '!', $root) || App::config()->devMode())
        && App::auth()->isSuperAdmin();
    }

    //@}

    /// @name Page methods
    //@{

    /**
     * Set page base URL.
     *
     * @param    string    $url        Page base URL
     *
     * @return    ModulesList self instance
     */
    public function setURL(string $url): ModulesList
    {
        $this->page_url = $url;

        return $this;
    }

    /**
     * Get page URL.
     *
     * @param    string|array<mixed>    $queries    Additionnal query string
     * @param    bool                   $with_tab   Add current tab to URL end
     *
     * @return   string Clean page URL
     */
    public function getURL($queries = '', bool $with_tab = true): string
    {
        return $this->page_url .
            (!empty($queries) ? (str_contains($this->page_url, '?') ? '&amp;' : '?') : '') .
            (is_array($queries) ? http_build_query($queries) : $queries) .
            ($with_tab && !empty($this->page_tab) ? '#' . $this->page_tab : '');
    }

    /**
     * Set page tab.
     *
     * @param    string    $tab        Page tab
     *
     * @return    ModulesList self instance
     */
    public function setTab(string $tab): ModulesList
    {
        $this->page_tab = $tab;

        return $this;
    }

    /**
     * Get page tab.
     *
     * @return  string  Page tab
     */
    public function getTab(): string
    {
        return $this->page_tab;
    }

    /**
     * Set page redirection.
     *
     * @param    string    $default        Default redirection
     *
     * @return    ModulesList self instance
     */
    public function setRedir(string $default = ''): ModulesList
    {
        $this->page_redir = empty($_REQUEST['redir']) ? $default : $_REQUEST['redir'];

        return $this;
    }

    /**
     * Get page redirection.
     *
     * @return  string  Page redirection
     */
    public function getRedir(): string
    {
        return empty($this->page_redir) ? $this->getURL() : $this->page_redir;
    }

    //@}

    /// @name Search methods
    //@{

    /**
     * Get search query.
     *
     * @return  mixed  Search query
     */
    public function getSearch()
    {
        $query = !empty($_REQUEST['m_search']) ? trim((string) $_REQUEST['m_search']) : null;

        return strlen((string) $query) >= 2 ? $query : null;
    }

    /**
     * Display searh form.
     *
     * @return    ModulesList self instance
     */
    public function displaySearch(): ModulesList
    {
        $query = $this->getSearch();

        if (empty($this->defines) && $query === null) {
            return $this;
        }

        echo (new Div())
            ->class('modules-search')
            ->items([
                (new Form(['modules-search-form']))
                    ->method('get')
                    ->action($this->getURL())
                    ->fields([
                        (new Para())
                            ->class('form-buttons')
                            ->items([
                                (new Input('m_search'))
                                    ->size(30)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML($query))
                                    ->label(new Label(__('Search in repository:'), Label::IL_TF)),
                                (new Submit(['modules-search-submit'], __('OK'))),
                                (new Hidden(['process'], is_a($this, ThemesList::class) ? 'BlogTheme' : 'Plugins')),
                                $query ? (new Link())->class('button')->text(__('Reset search')) : (new None()),
                            ]),
                        (new Note())
                            ->class('form-note')
                            ->text(__('Search is allowed on multiple terms longer than 2 chars, terms must be separated by space.')),
                    ]),
                $query ?
                    (new Note())
                        ->class('message')
                        ->text(sprintf(
                            __('Found %d result for search "%s":', 'Found %d results for search "%s":', count($this->defines)),
                            count($this->defines),
                            Html::escapeHTML($query)
                        )) :
                    (new None()),
            ])
        ->render();

        return $this;
    }

    //@}

    /// @name Navigation menu methods
    //@{

    /**
     * Set navigation special index.
     *
     * @param     string     $str   Index
     *
     * @return    ModulesList self instance
     */
    public function setIndex(string $str): ModulesList
    {
        $this->nav_special = $str;
        $this->nav_list    = [...str_split(self::$nav_indexes), ...[$this->nav_special]];

        return $this;
    }

    /**
     * Get index from query.
     *
     * @return  string  Query index or default one
     */
    public function getIndex(): string
    {
        return (string) (isset($_REQUEST['m_nav']) && in_array($_REQUEST['m_nav'], $this->nav_list) ? $_REQUEST['m_nav'] : $this->nav_list[0]);
    }

    /**
     * Display navigation by index menu.
     *
     * @return    ModulesList self instance
     */
    public function displayIndex(): ModulesList
    {
        if (empty($this->defines) || $this->getSearch() !== null) {
            return $this;
        }

        # Fetch modules required field
        $indexes = [];
        foreach ($this->defines as $define) {
            if ($define->get($this->sort_field) === null) {
                continue;
            }
            $char = substr($define->get($this->sort_field), 0, 1);
            if (!in_array($char, $this->nav_list)) {
                $char = $this->nav_special;
            }
            if (!isset($indexes[$char])) {
                $indexes[$char] = 0;
            }
            $indexes[$char]++;
        }

        $buttons = [];
        foreach ($this->nav_list as $char) {
            # Selected letter
            if ($this->getIndex() == $char) {
                $buttons[] = (new Li())
                    ->class('active')
                    ->title(__('current selection'))
                    ->items([
                        (new Text('strong', $char)),
                    ]);
            }
            # Letter having modules
            elseif (!empty($indexes[$char])) {
                $title     = sprintf(__('%d result', '%d results', $indexes[$char]), $indexes[$char]);
                $buttons[] = (new Li())
                    ->class('btn')
                    ->title($title)
                    ->items([
                        (new Link())
                            ->href($this->getURL('m_nav=' . $char))
                            ->title($title)
                            ->text($char),
                    ]);
            }
            # Letter without modules
            else {
                $buttons[] = (new Li())
                    ->class(['btn', 'no-link'])
                    ->title(__('no results'))
                    ->text($char);
            }
        }
        # Parse navigation menu
        echo (new Div())
            ->class('pager')
            ->items([
                (new Para())
                    ->items([
                        (new Text(null, __('Browse index:'))),
                    ]),
                (new Ul())
                    ->items($buttons),
            ])
        ->render();

        return $this;
    }
    //@}

    /// @name Sort methods
    //@{

    /**
     * Set default sort field.
     *
     * @param      string                 $field  The field
     * @param      bool                   $asc    The ascending
     *
     * @return     ModulesList     self instance
     */
    public function setSort(string $field, bool $asc = true): ModulesList
    {
        $this->sort_field = $field;
        $this->sort_asc   = $asc;

        return $this;
    }

    /**
     * Get sort field from query.
     *
     * @return    string    Query sort field or default one
     */
    public function getSort(): string
    {
        return (string) (!empty($_REQUEST['m_sort']) ? $_REQUEST['m_sort'] : $this->sort_field);
    }

    /**
     * Display sort field form. (not implemented)
     *
     * @todo    Implement ModulesList::displaySort method
     *
     * @return  ModulesList self instance
     */
    public function displaySort(): ModulesList
    {
        return $this;
    }

    //@}

    /// @name Modules methods
    //@{

    /**
     * Set modules defines and sanitize them.
     *
     * @param   array<ModuleDefine>   $defines
     *
     * @return    ModulesList self instance
     */
    public function setDefines(array $defines): ModulesList
    {
        $this->defines = [];

        foreach ($defines as $define) {
            if (!($define instanceof ModuleDefine)) {
                continue;
            }
            self::fillSanitizeModule($define);
            $this->defines[] = $define;
        }

        return $this;
    }

    /**
     * Get modules defines currently set.
     *
     * @return    array<ModuleDefine>        Array of modules
     */
    public function getDefines(): array
    {
        return $this->defines;
    }

    /**
     * Set modules and sanitize them.
     *
     * @deprecated  since 2.26, use self::setDefines() instead
     *
     * @param   array<string, mixed>   $modules
     *
     * @return    ModulesList self instance
     */
    public function setModules(array $modules): ModulesList
    {
        App::deprecated()->set('adminModulesList::setDefines()', '2.26');

        $defines = [];
        foreach ($modules as $id => $module) {
            $define = new ModuleDefine($id);
            foreach ($module as $k => $v) {
                $define->set($k, $v);
            }
            $defines[] = $define;
        }

        return $this->setDefines($defines);
    }

    /**
     * Get modules currently set.
     *
     * @deprecated  since 2.26, use self::getDefines() instead
     *
     * @return    array<string, array<string,mixed>>        Array of modules
     */
    public function getModules(): array
    {
        App::deprecated()->set('adminModulesList::getDefines()', '2.26');

        $res = [];
        foreach ($this->defines as $define) {
            $res[$define->getId()] = $define->dump();
        }

        return $res;
    }

    /**
     * Sanitize a module.
     *
     * This clean infos of a module by adding default keys
     * and clean some of them, sanitize module can safely
     * be used in lists.
     *
     * @param      ModuleDefine         $define The module definition
     * @param      array<string,mixed>  $module  The module
     */
    public static function fillSanitizeModule(ModuleDefine $define, array $module = []): void
    {
        foreach ($module as $k => $v) {
            $define->set($k, $v);
        }

        $define
            ->set('sid', self::sanitizeString($define->getId()))
            ->set('label', $define->get('label') ?: ($define->get('name') ?: $define->getId()))
            ->set('name', __($define->get('name') ?: $define->get('label')))
            ->set('sname', self::sanitizeString(strtolower(Txt::removeDiacritics($define->get('name')))));
    }

    /**
     * Sanitize a module (static version).
     *
     * This clean infos of a module by adding default keys
     * and clean some of them, sanitize module can safely
     * be used in lists.
     *
     * Warning: this static method will not fill module dependencies
     *
     * @deprecated  since 2.26, use self::fillSanitizeModule() instead
     *
     * @param      string               $id      The identifier
     * @param      array<string,mixed>  $module  The module
     *
     * @return   array<string,mixed>  Array of the module informations
     */
    public static function sanitizeModule(string $id, array $module): array
    {
        App::deprecated()->set('adminModulesList::fillSanitizeModule()', '2.26');

        $define = new ModuleDefine($id);
        self::fillSanitizeModule($define, $module);

        return $define->dump();
    }

    /**
     * Sanitize a module (dynamic version).
     *
     * This clean infos of a module by adding default keys
     * and clean some of them, sanitize module can safely
     * be used in lists.
     *
     * @deprecated  since 2.26, use self::fillSanitizeModule() instead
     *
     * @param      string                   $id      The identifier
     * @param      array<string,mixed>      $module  The module
     *
     * @return   array<string,mixed>  Array of the module informations
     */
    public function doSanitizeModule(string $id, array $module): array
    {
        App::deprecated()->set('adminModulesList::fillSanitizeModule()', '2.26');

        $define = $this->modules->getDefine($id);
        self::fillSanitizeModule($define, $module);

        return $define->dump();
    }

    /**
     * Check if a module is part of the distribution.
     *
     * @deprecated  since 2.26, use Modules::getDefine($id)->distributed instead
     *
     * @param    string    $id        Module root directory
     *
     * @return   bool  True if module is part of the distribution
     */
    public static function isDistributedModule(string $id): bool
    {
        App::deprecated()->set('Modules::getDefine($id)->distributed', '2.26');

        return in_array($id, self::$distributed_modules);
    }

    /**
     * Sort modules list by specific field.
     *
     * @deprecated  since 2.26, use something like uasort($defines, fn ($a, $b) => $a->get($field) <=> $b->get($field)); instead
     *
     * @param    array<string, array<string, mixed>>    $modules      Array of modules
     * @param    string                                 $field        Field to sort from
     * @param    bool                                   $asc          Sort asc if true, else decs
     *
     * @return   array<int|string, array<string, mixed>>  Array of sorted modules
     */
    public static function sortModules(array $modules, string $field, bool $asc = true): array
    {
        App::deprecated()->set('uasort()', '2.26');

        $origin = $sorter = $final = [];

        foreach ($modules as $module) {
            $origin[] = $module;
            $sorter[] = $module[$field] ?? $field;
        }

        array_multisort($sorter, $asc ? SORT_ASC : SORT_DESC, $origin);

        foreach ($origin as $module) {
            $final[$module['id']] = $module;
        }

        return $final;
    }

    /**
     * Display list of modules.
     *
     * @param    array<string>      $cols         List of columns (module field) to display
     * @param    array<string>      $actions      List of predefined actions to show on form
     * @param    bool               $nav_limit    Limit list to previously selected index
     *
     * @return    ModulesList self instance
     */
    public function displayModules(array $cols = ['name', 'version', 'desc'], array $actions = [], bool $nav_limit = false): ModulesList
    {
        $rows    = [];
        $headers = [];

        if (in_array('name', $cols)) {
            $colspan = 1;
            if (in_array('checkbox', $cols)) {
                $colspan++;
            }
            if (in_array('icon', $cols)) {
                $colspan++;
            }
            $headers[] = (new Th())
                ->class(['first', 'nowrap'])
                ->text(__('Name'));
            if ($colspan > 1) {
                current($headers)->colspan($colspan);
            }
        }

        if (in_array('score', $cols) && $this->getSearch() !== null && App::config()->debugMode()) {
            $headers[] = (new Th())
                ->class('nowrap')
                ->text(__('Score'));
        }

        if (in_array('version', $cols)) {
            $headers[] = (new Th())
                ->class(['nowrap', 'count'])
                ->scope('col')
                ->text(__('Version'));
        }

        if (in_array('current_version', $cols)) {
            $headers[] = (new Th())
                ->class(['nowrap', 'count'])
                ->scope('col')
                ->text(__('Current version'));
        }

        if (in_array('desc', $cols)) {
            $headers[] = (new Th())
                ->class(['nowrap', 'module-desc'])
                ->scope('col')
                ->text(__('Details'));
        }

        if (in_array('repository', $cols) && App::config()->allowRepositories()) {
            $headers[] = (new Th())
                ->class(['nowrap', 'count'])
                ->scope('col')
                ->text(__('Repository'));
        }

        if (in_array('distrib', $cols)) {
            $headers[] = (new Th());
            if (!in_array('desc', $cols)) {
                current($headers)->class('maximal');
            }
        }

        if (!empty($actions) && App::auth()->isSuperAdmin()) {
            $headers[] = (new Th())
                ->class(['nowrap', 'minimal'])
                ->text(__('Action'));
        }

        $rows[] = (new Tr())->cols($headers);

        $sort_field = $this->getSort();

        # Sort modules by $sort_field (default sname)
        if ($this->getSearch() === null) {
            uasort($this->defines, fn ($a, $b) => $a->get($sort_field) <=> $b->get($sort_field));
        }

        $count = 0;
        foreach ($this->defines as $define) {
            $data = [];
            $id   = $define->getId();

            # Show only requested modules
            if ($nav_limit && $this->getSearch() === null) {
                $char = substr($define->get($sort_field), 0, 1);
                if (!in_array($char, $this->nav_list)) {
                    $char = $this->nav_special;
                }
                if ($this->getIndex() != $char) {
                    continue;
                }
            }
            $git = (App::config()->devMode() || App::config()->debugMode()) && file_exists($define->get('root') . '/.git');
            $tds = 0;

            if (in_array('checkbox', $cols)) {
                $tds++;
                $data[] = (new Td())
                    ->class(['module-icon', 'nowrap'])
                    ->items([
                        (new Checkbox(['modules[' . $count . ']', Html::escapeHTML($this->list_id) . '_modules_' . Html::escapeHTML($id)]))
                            ->value(Html::escapeHTML($id)),
                    ]);
            }

            if (in_array('icon', $cols)) {
                $tds++;
                $default_icon = false;

                if (file_exists($define->get('root') . DIRECTORY_SEPARATOR . 'icon.svg')) {
                    $icon = Page::getPF($id . '/icon.svg');
                } elseif (file_exists($define->get('root') . DIRECTORY_SEPARATOR . 'icon.png')) {
                    $icon = Page::getPF($id . '/icon.png');
                } else {
                    $icon         = 'images/module.svg';
                    $default_icon = true;
                }
                if (file_exists($define->get('root') . DIRECTORY_SEPARATOR . 'icon-dark.svg')) {
                    $icon = [$icon, Page::getPF($id . '/icon-dark.svg')];
                } elseif (file_exists($define->get('root') . DIRECTORY_SEPARATOR . 'icon-dark.png')) {
                    $icon = [$icon, Page::getPF($id . '/icon-dark.png')];
                } elseif ($default_icon) {
                    $icon = [$icon, 'images/module-dark.svg'];
                }

                $data[] = (new Td())
                    ->class(['module-icon', 'nowrap'])
                    ->text(Helper::adminIcon($icon, false, Html::escapeHTML($id), Html::escapeHTML($id)));
            }

            $tds++;
            $data_items = [];
            $name       = Html::escapeHTML($define->get('name')) . ($id != $define->get('name') ? sprintf(__(' (%s)'), $id) : '');
            if (in_array('checkbox', $cols)) {
                if (in_array('expander', $cols)) {
                    $data_items[] = (new Text(null, $name));
                } else {
                    $data_items[] = (new Label($name))
                        ->for(Html::escapeHTML($this->list_id) . '_modules_' . Html::escapeHTML($id));
                }
            } else {
                $data_items[] = (new Text(null, $name));
                $data_items[] = (new Hidden(['modules[' . $count . ']'], Html::escapeHTML($id)));
            }
            $data[] = (new Th())
                ->class(['module-name', 'nowrap'])
                ->items($data_items);

            # Display score only for debug purpose
            if (in_array('score', $cols) && $this->getSearch() !== null && App::config()->debugMode()) {
                $tds++;
                $data[] = (new Td())
                    ->class(['module-version', 'nowrap', 'count'])
                    ->items([
                        (new Text('span', $define->get('score')))->class('debug'),
                    ]);
            }

            if (in_array('version', $cols)) {
                $tds++;
                $data[] = (new Td())
                    ->class(['module-version', 'nowrap', 'count'])
                    ->text(Html::escapeHTML($define->get('version')));
            }

            if (in_array('current_version', $cols)) {
                $tds++;
                $data[] = (new Td())
                    ->class(['module-current-version', 'nowrap', 'count'])
                    ->text(Html::escapeHTML($define->get('current-version')));
            }

            if (in_array('desc', $cols)) {
                $tds++;
                $infos = [];
                if (!empty($define->getUsing()) && $define->get('state') == ModuleDefine::STATE_ENABLED) {
                    $infos[] = (new Para())
                        ->items([
                            (new Text('span', sprintf(
                                __('This module cannot be disabled nor deleted, since the following modules are also enabled : %s'),
                                join(', ', $define->getUsing())
                            )))->class('info'),
                        ]);
                }
                if (!empty($define->getMissing())) {
                    $reasons = [];
                    foreach ($define->getMissing() as $reason) {
                        $reasons[] = (new Li())->text($reason);
                    }
                    $infos[] = (new Para())
                        ->items([
                            (new Text('span', __('This module cannot be enabled, because of the following reasons :')))->class('info'),
                            (new Ul())->items($reasons),
                        ]);
                }

                $data[] = (new Td())
                    ->class(['module-desc', 'maximal'])
                    ->items([
                        count($infos) ?
                        (new Details())
                            ->summary((new Summary(Html::escapeHTML(__($define->get('desc'))))))
                            ->items($infos) :
                        (new Text(null, Html::escapeHTML(__($define->get('desc'))))),
                    ]);
            }

            if (in_array('repository', $cols) && App::config()->allowRepositories()) {
                $tds++;
                $data[] = (new Td())
                    ->class(['module-repository', 'nowrap', 'count'])
                    ->text(!empty($define->get('repository')) ? __('Third-party repository') : __('Official repository'));
            }

            if (in_array('distrib', $cols)) {
                $tds++;
                $data[] = (new Td())
                    ->class('module-distrib')
                    ->items([
                        $define->get('distributed') ?
                        (new Img('images/dotclear-leaf.svg'))->alt(__('Plugin from official distribution')) :
                        ($git ? (new Img('images/git-branch.svg'))->alt(__('Plugin in development')) : (new None())),
                    ]);
            }

            if (!empty($actions) && App::auth()->isSuperAdmin()) {
                $buttons = $this->getActions($define, $actions);

                $tds++;
                $data[] = (new Td())
                    ->class(['module-actions', 'nowrap'])
                    ->text(implode(' ', $buttons));
            }

            $rows[] = (new Tr(Html::escapeHTML($this->list_id) . '_m_' . Html::escapeHTML($id)))
                ->class(array_filter(['line', $git ? ' module-git' : '']))
                ->cols($data);
            if (in_array('desc', $cols)) {
                current($rows)->title(Html::escapeHTML(__($define->get('desc'))));
            }

            # Other informations
            if (in_array('expander', $cols)) {
                $items = [];

                if (!empty($define->get('author')) || !empty($define->get('details')) || !empty($define->get('support'))) {
                    $lines = [];

                    if (!empty($define->get('author'))) {
                        $lines[] = (new Li())
                            ->class('module-author')
                            ->text(__('Author:') . ' ' . Html::escapeHTML($define->get('author')));
                    }

                    $links = [];
                    if (!empty($define->get('details'))) {
                        $links[] = (new Link())
                            ->class('module-details')
                            ->href($define->get('details'))
                            ->text(__('Details'));
                    }

                    if (!empty($define->get('support'))) {
                        $links[] = (new Link())
                            ->class('module-support')
                            ->href($define->get('support'))
                            ->text(__('Support'));
                    }

                    if ($define->updLocked()) {
                        $links[] = (new Text('span', __('update locked')))
                            ->class('module-locked');
                    }

                    if (!empty($links)) {
                        $lines[] = (new Li())
                            ->separator(' - ')
                            ->items($links);
                    }

                    $items[] = (new Div())->items([
                        (new Ul())->class('mod-more')->items($lines),
                    ]);
                }

                if (static::hasFileOrClass($id, $this->modules::MODULE_CLASS_CONFIG, $this->modules::MODULE_FILE_CONFIG)
                 || static::hasFileOrClass($id, $this->modules::MODULE_CLASS_MANAGE, $this->modules::MODULE_FILE_MANAGE)
                 || !empty($define->get('section'))
                 || !empty($define->get('tags'))
                 || !empty($define->get('settings'))   && $define->get('state') == ModuleDefine::STATE_ENABLED
                 || !empty($define->get('repository')) && App::config()->debugMode() && App::config()->allowRepositories()
                ) {
                    $lines = [];

                    $settings = static::getSettingsUrls($id);
                    if (!empty($settings) && $define->get('state') == ModuleDefine::STATE_ENABLED) {
                        $lines[] = (new Li())
                            ->text(implode(' - ', $settings));
                    }

                    if (!empty($define->get('repository')) && App::config()->debugMode() && App::config()->allowRepositories()) {
                        $lines[] = (new Li())
                            ->class('modules-repository')
                            ->items([
                                (new Link())->href($define->get('repository'))->text(__('Third-party repository')),
                            ]);
                    }

                    if (!empty($define->get('section'))) {
                        $lines[] = (new Li())
                            ->class('module-section')
                            ->text(__('Section:') . ' ' . Html::escapeHTML($define->get('section')));
                    }

                    if (!empty($define->get('tags'))) {
                        $lines[] = (new Li())
                            ->class('module-tags')
                            ->text(__('Tags:') . ' ' . Html::escapeHTML($define->get('tags')));
                    }

                    $items[] = (new Div())->items([
                        (new Ul())->class('mod-more')->items($lines),
                    ]);
                }

                $rows[] = (new Tr())
                    ->class('module-more')
                    ->cols([
                        (new Td())
                            ->class('expand')
                            ->colspan($tds)
                            ->items($items),
                    ]);
            }

            $count++;
        }

        $bottom = [];
        if (!$count && $this->getSearch() === null) {
            $bottom[] = (new Note())
                ->class('message')
                ->text(__('No plugins matched your search.'));
        } elseif ((in_array('checkbox', $cols) || $count > 1) && !empty($actions) && App::auth()->isSuperAdmin()) {
            $buttons = $this->getGlobalActions($actions, in_array('checkbox', $cols));

            if (!empty($buttons)) {
                if (in_array('checkbox', $cols)) {
                    $bottom[] = (new Para())->class('checkboxes-helpers');
                }
                $bottom[] = (new Div())->items([
                    (new Text(null, implode(' ', $buttons))),
                ]);
            }
        }

        $form = (new Form(['modules-form-actions']))
            ->method('post')
            ->action($this->getURL())
            ->fields([
                (new Div())
                    ->class('table-outer')
                    ->items([
                        (new Table(Html::escapeHTML($this->list_id)))
                            ->class(array_filter(['modules', in_array('expander', $cols) ? ' expandable' : '']))
                            ->caption((new Caption(Html::escapeHTML(__('Plugins list'))))->class('hidden'))
                            ->items($rows),
                    ]),
                ...$bottom,
                App::nonce()->formNonce(),
            ])
        ->render();

        echo $form;

        return $this;
    }

    /**
     * Get settings URLs if any
     *
     * @param   string  $id     Module ID
     * @param   boolean $check  Check permission
     * @param   boolean $self   Include self URL (→ plugin index.php URL)
     *
     * @return array<string>    Array of settings URLs
     */
    public static function getSettingsUrls(string $id, bool $check = false, bool $self = true): array
    {
        $settings_urls = [];

        $config = static::hasFileOrClass($id, App::plugins()::MODULE_CLASS_CONFIG, App::plugins()::MODULE_FILE_CONFIG);
        $index  = static::hasFileOrClass($id, App::plugins()::MODULE_CLASS_MANAGE, App::plugins()::MODULE_FILE_MANAGE);

        $settings = App::plugins()->moduleInfo($id, 'settings');
        if ($self) {
            if (isset($settings['self']) && $settings['self'] === false) {
                $self = false;
            }
        }
        if ($config || $index || !empty($settings)) {
            if ($config) {
                if (!$check || App::auth()->isSuperAdmin() || App::auth()->check(App::plugins()->moduleInfo($id, 'permissions'), App::blog()->id())) {
                    $params = ['module' => $id, 'conf' => '1'];
                    if (!App::plugins()->moduleInfo($id, 'standalone_config') && !$self) {
                        $params['redir'] = App::backend()->url()->get('admin.plugin.' . $id);
                    }
                    $settings_urls[] = (new Link())
                        ->href(App::backend()->url()->get('admin.plugins', $params))
                        ->class('module-config')
                        ->text(__('Configure plugin'))
                    ->render();
                }
            }
            if (is_array($settings)) {
                foreach ($settings as $sk => $sv) {
                    switch ($sk) {
                        case 'blog':
                            if (!$check || App::auth()->isSuperAdmin() || App::auth()->check(App::auth()->makePermissions([
                                App::auth()::PERMISSION_ADMIN,
                            ]), App::blog()->id())) {
                                $settings_urls[] = (new Link())
                                    ->href(App::backend()->url()->get('admin.blog.pref') . $sv)
                                    ->class('module-config')
                                    ->text(__('Plugin settings (in blog parameters)'))
                                ->render();
                            }

                            break;
                        case 'pref':
                            if (!$check || App::auth()->isSuperAdmin() || App::auth()->check(App::auth()->makePermissions([
                                App::auth()::PERMISSION_USAGE,
                                App::auth()::PERMISSION_CONTENT_ADMIN,
                            ]), App::blog()->id())) {
                                $settings_urls[] = (new Link())
                                    ->href(App::backend()->url()->get('admin.user.preferences') . $sv)
                                    ->class('module-config')
                                    ->text(__('Plugin settings (in user preferences)'))
                                ->render();
                            }

                            break;
                        case 'self':
                            if ($self) {
                                if (!$check || App::auth()->isSuperAdmin() || App::auth()->check(App::plugins()->moduleInfo($id, 'permissions'), App::blog()->id())) {
                                    $settings_urls[] = (new Link())
                                        ->href(App::backend()->url()->get('admin.plugin.' . $id) . $sv)
                                        ->class('module-config')
                                        ->text(__('Plugin settings'))
                                    ->render();
                                }
                                // No need to use default index.php
                                $index = false;
                            }

                            break;
                        case 'other':
                            if (!$check || App::auth()->isSuperAdmin() || App::auth()->check(App::plugins()->moduleInfo($id, 'permissions'), App::blog()->id())) {
                                $settings_urls[] = (new Link())
                                    ->href($sv)
                                    ->class('module-config')
                                    ->text(__('Plugin settings'))
                                ->render();
                            }

                            break;
                    }
                }
            }
            if ($index && $self) {
                if (!$check || App::auth()->isSuperAdmin() || App::auth()->check(App::plugins()->moduleInfo($id, 'permissions'), App::blog()->id())) {
                    $settings_urls[] = (new Link())
                        ->href(App::backend()->url()->get('admin.plugin.' . $id))
                        ->class('module-config')
                        ->text(__('Plugin main page'))
                    ->render();
                }
            }
        }

        return $settings_urls;
    }

    /**
     * Get action buttons to add to modules list.
     *
     * @param    ModuleDefine     $define     Module info
     * @param    array<string>    $actions    Actions keys
     *
     * @return   array<string>    Array of actions buttons
     */
    protected function getActions(ModuleDefine $define, array $actions): array
    {
        $submits = [];
        $id      = $define->getId();

        // mark module state
        if ($define->get('state') != ModuleDefine::STATE_ENABLED) {
            $submits[] = (new Hidden(['disabled[' . Html::escapeHTML($id) . ']'], '1'))
            ->render();
        }

        # Use loop to keep requested order
        foreach ($actions as $action) {
            switch ($action) {
                # Deactivate
                case 'activate':
                    // do not allow activation of duplicate modules already activated
                    $multi = !self::$allow_multi_install && count($this->modules->getDefines(['id' => $id, 'state' => ModuleDefine::STATE_ENABLED])) > 0;
                    if (App::auth()->isSuperAdmin() && $define->get('root_writable') && empty($define->getMissing()) && !$multi) {
                        $submits[] = (new Submit(['activate[' . Html::escapeHTML($id) . ']'], __('Activate')))
                        ->render();
                    }

                    break;

                    # Activate
                case 'deactivate':
                    if (App::auth()->isSuperAdmin() && $define->get('root_writable') && empty($define->getUsing())) {
                        $submits[] = (new Submit(['deactivate[' . Html::escapeHTML($id) . ']'], __('Deactivate')))
                            ->class('reset')
                        ->render();
                    }

                    break;

                    # Delete
                case 'delete':
                    if (App::auth()->isSuperAdmin() && !$define->distributed && $this->isDeletablePath($define->get('root')) && empty($define->getUsing())) {
                        $dev       = !preg_match('!^' . $this->path_pattern . '!', $define->get('root')) && App::config()->devMode() ? ' debug' : '';
                        $submits[] = (new Submit(['delete[' . Html::escapeHTML($id) . ']'], __('Delete')))
                            ->class(array_filter(['delete', $dev]))
                        ->render();
                    }

                    break;

                    # Clone
                case 'clone':
                    if (App::auth()->isSuperAdmin() && $this->path_writable) {
                        $submits[] = (new Submit(['clone[' . Html::escapeHTML($id) . ']'], __('Clone')))
                            ->class('clone')
                        ->render();
                    }

                    break;

                    # Install (from store)
                case 'install':
                    if (App::auth()->isSuperAdmin() && $this->path_writable) {
                        $submits[] = (new Submit(['install[' . Html::escapeHTML($id) . ']'], __('Install')))
                        ->render();
                    }

                    break;

                    # Update (from store)
                case 'update':
                    if (App::auth()->isSuperAdmin() && $this->path_writable && !$define->updLocked()) {
                        $submits[] = (new Submit(['update[' . Html::escapeHTML($id) . ']'], __('Update')))
                        ->render();
                    }

                    break;

                    # Behavior
                case 'behavior':

                    # --BEHAVIOR-- adminModulesListGetActions -- ModulesList, ModuleDefine
                    $tmp = App::behavior()->callBehavior('adminModulesListGetActionsV2', $this, $define);

                    if (!empty($tmp)) {
                        $submits[] = $tmp;
                    }

                    break;
            }
        }

        return $submits;
    }

    /**
     * Get global action buttons to add to modules list.
     *
     * @param   array<string>   $actions            Actions keys
     * @param   bool            $with_selection     Limit action to selected modules
     *
     * @return  array<string>   Array of actions buttons
     */
    protected function getGlobalActions(array $actions, bool $with_selection = false): array
    {
        $submits = [];

        # Use loop to keep requested order
        foreach ($actions as $action) {
            switch ($action) {
                # Deactivate
                case 'activate':
                    if (App::auth()->isSuperAdmin() && $this->path_writable) {
                        $submits[] = (new Submit(['activate'], $with_selection ?
                            __('Activate selected plugins') :
                            __('Activate all plugins from this list')))
                        ->render();
                    }

                    break;

                    # Activate
                case 'deactivate':
                    if (App::auth()->isSuperAdmin() && $this->path_writable) {
                        $submits[] = (new Submit(['deactivate'], $with_selection ?
                            __('Deactivate selected plugins') :
                            __('Deactivate all plugins from this list')))
                        ->render();
                    }

                    break;

                    # Update (from store)
                case 'update':
                    if (App::auth()->isSuperAdmin() && $this->path_writable) {
                        $submits[] = (new Submit(['update'], $with_selection ?
                            __('Update selected plugins') :
                            __('Update all plugins from this list')))
                        ->render();
                    }

                    break;

                    # Behavior
                case 'behavior':

                    # --BEHAVIOR-- adminModulesListGetGlobalActions -- ModulesList, bool
                    $tmp = App::behavior()->callBehavior('adminModulesListGetGlobalActions', $this, $with_selection);

                    if (!empty($tmp)) {
                        $submits[] = $tmp;
                    }

                    break;
            }
        }

        return $submits;
    }

    /**
     * Execute POST action.
     *
     * Set a notice on success through Notices::addSuccessNotice
     *
     * @throws    Exception    Module not find or command failed
     */
    public function doActions(): void
    {
        if (empty($_POST) || !empty($_REQUEST['conf'])
                          || !$this->isWritablePath()) {
            return;
        }

        $modules = !empty($_POST['modules']) && is_array($_POST['modules']) ? array_values($_POST['modules']) : [];

        if (App::auth()->isSuperAdmin() && !empty($_POST['delete'])) {
            if (is_array($_POST['delete'])) {
                $modules = array_keys($_POST['delete']);
            }

            $failed = false;
            $count  = 0;
            foreach ($modules as $id) {
                $disabled = !empty($_POST['disabled'][$id]);
                $define   = $this->modules->getDefine($id, ['state' => ($disabled ? '!' : '') . ModuleDefine::STATE_ENABLED]);
                // module is not defined
                if (!$define->isDefined()) {
                    throw new Exception(__('No such plugin.'));
                }
                if (!$this->isDeletablePath($define->get('root'))) {
                    $failed = true;

                    continue;
                }

                # --BEHAVIOR-- moduleBeforeDelete -- ModuleDefine
                App::behavior()->callBehavior('pluginBeforeDeleteV2', $define);

                $this->modules->deleteModule($define->getId(), $disabled);

                # --BEHAVIOR-- moduleAfterDelete -- ModuleDefine
                App::behavior()->callBehavior('pluginAfterDeleteV2', $define);

                $count++;
            }

            if (!$count && $failed) {
                throw new Exception(__("You don't have permissions to delete this plugin."));
            } elseif ($failed) {
                Notices::addWarningNotice(__('Some plugins have not been delete.'));
            } else {
                Notices::addSuccessNotice(
                    __('Plugin has been successfully deleted.', 'Plugins have been successuflly deleted.', $count)
                );
            }
            Http::redirect($this->getURL());
        } elseif (App::auth()->isSuperAdmin() && !empty($_POST['install'])) {
            if (is_array($_POST['install'])) {
                $modules = array_keys($_POST['install']);
            }

            $count = 0;
            foreach ($this->store->getDefines() as $define) {
                if (!in_array($define->getId(), $modules)) {
                    continue;
                }

                $dest = $this->getPath() . DIRECTORY_SEPARATOR . basename($define->get('file'));

                # --BEHAVIOR-- moduleBeforeAdd -- ModuleDefine
                App::behavior()->callBehavior('pluginBeforeAddV2', $define);

                $this->store->process($define->get('file'), $dest);

                # --BEHAVIOR-- moduleAfterAdd -- ModuleDefine
                App::behavior()->callBehavior('pluginAfterAddV2', $define);

                $count++;
            }

            if (!$count) {
                throw new Exception(__('No such plugin.'));
            }

            Notices::addSuccessNotice(
                __('Plugin has been successfully installed.', 'Plugins have been successfully installed.', $count)
            );
            Http::redirect($this->getURL());
        } elseif (App::auth()->isSuperAdmin() && !empty($_POST['activate'])) {
            if (is_array($_POST['activate'])) {
                $modules = array_keys($_POST['activate']);
            }

            $count = 0;
            foreach ($modules as $id) {
                $define = $this->modules->getDefine($id, ['state' => '!' . ModuleDefine::STATE_ENABLED]);
                if (!$define->isDefined()) {
                    continue;
                }

                # --BEHAVIOR-- moduleBeforeActivate -- string
                App::behavior()->callBehavior('pluginBeforeActivate', $define->getId());

                $this->modules->activateModule($define->getId());

                # --BEHAVIOR-- moduleAfterActivate -- string
                App::behavior()->callBehavior('pluginAfterActivate', $define->getId());

                $count++;
            }

            if (!$count) {
                throw new Exception(__('No such plugin.'));
            }

            Notices::addSuccessNotice(
                __('Plugin has been successfully activated.', 'Plugins have been successuflly activated.', $count)
            );
            Http::redirect($this->getURL());
        } elseif (App::auth()->isSuperAdmin() && !empty($_POST['deactivate'])) {
            if (is_array($_POST['deactivate'])) {
                $modules = array_keys($_POST['deactivate']);
            }

            $failed = false;
            $count  = 0;
            foreach ($modules as $id) {
                $define = $this->modules->getDefine($id, ['state' => '!' . ModuleDefine::STATE_HARD_DISABLED]);
                if (!$define->isDefined()) {
                    continue;
                }

                if (!$define->get('root_writable')) {
                    $failed = true;

                    continue;
                }

                # --BEHAVIOR-- moduleBeforeDeactivate -- ModuleDefine
                App::behavior()->callBehavior('pluginBeforeDeactivateV2', $define);

                $this->modules->deactivateModule($define->getId());

                # --BEHAVIOR-- moduleAfterDeactivate -- ModuleDefine
                App::behavior()->callBehavior('pluginAfterDeactivateV2', $define);

                $count++;
            }

            if (!$count) {
                throw new Exception(__('No such plugin.'));
            }

            if ($failed) {
                Notices::addWarningNotice(__('Some plugins have not been deactivated.'));
            } else {
                Notices::addSuccessNotice(
                    __('Plugin has been successfully deactivated.', 'Plugins have been successuflly deactivated.', $count)
                );
            }
            Http::redirect($this->getURL());
        } elseif (App::auth()->isSuperAdmin() && !empty($_POST['update'])) {
            if (is_array($_POST['update'])) {
                $modules = array_keys($_POST['update']);
            }

            $locked  = [];
            $count   = 0;
            $defines = $this->store->getDefines(true);
            foreach ($defines as $define) {
                if (!in_array($define->getId(), $modules)) {
                    continue;
                }

                if ($define->updLocked()) {
                    $locked[] = $define->get('name');

                    continue;
                }

                if (!self::$allow_multi_install) {
                    $dest = implode(DIRECTORY_SEPARATOR, [Path::dirWithSym($define->get('root')), '..', basename($define->get('file'))]);
                } else {
                    $dest = $this->getPath() . DIRECTORY_SEPARATOR . basename($define->get('file'));
                    if ($define->get('root') != $dest) {
                        @file_put_contents($define->get('root') . DIRECTORY_SEPARATOR . $this->modules::MODULE_FILE_DISABLED, '');
                    }
                }

                # --BEHAVIOR-- moduleBeforeUpdate -- ModuleDefine
                App::behavior()->callBehavior('pluginBeforeUpdateV2', $define);

                $this->store->process($define->get('file'), $dest);

                # --BEHAVIOR-- moduleAfterUpdate -- ModuleDefine
                App::behavior()->callBehavior('pluginAfterUpdateV2', $define);

                $count++;
            }

            $tab = $count == count($defines) ? '#plugins' : '#update';

            if ($count) {
                Notices::addSuccessNotice(
                    __('Plugin has been successfully updated.', 'Plugins have been successfully updated.', $count)
                );
            } elseif (!empty($locked)) {
                Notices::addWarningNotice(
                    sprintf(__('Following plugins updates are locked: %s'), implode(', ', $locked))
                );
            } else {
                throw new Exception(__('No such plugin.'));
            }
            Http::redirect($this->getURL() . $tab);
        }

        # Manual actions
        elseif (!empty($_POST['upload_pkg']) && !empty($_FILES['pkg_file'])
            || !empty($_POST['fetch_pkg'])   && !empty($_POST['pkg_url'])) {
            if (empty($_POST['your_pwd']) || !App::auth()->checkPassword($_POST['your_pwd'])) {
                throw new Exception(__('Password verification failed'));
            }

            if (!empty($_POST['upload_pkg'])) {
                Files::uploadStatus($_FILES['pkg_file']);

                $dest = $this->getPath() . DIRECTORY_SEPARATOR . $_FILES['pkg_file']['name'];
                if (!move_uploaded_file($_FILES['pkg_file']['tmp_name'], $dest)) {
                    throw new Exception(__('Unable to move uploaded file.'));
                }
            } else {
                $url  = urldecode($_POST['pkg_url']);
                $dest = $this->getPath() . DIRECTORY_SEPARATOR . basename($url);
                $this->store->download($url, $dest);
            }

            # --BEHAVIOR-- moduleBeforeAdd --
            App::behavior()->callBehavior('pluginBeforeAdd', null);

            $ret_code = $this->store->install($dest);

            # --BEHAVIOR-- moduleAfterAdd --
            App::behavior()->callBehavior('pluginAfterAdd', null);

            Notices::addSuccessNotice(
                $ret_code === $this->modules::PACKAGE_UPDATED ?
                __('The plugin has been successfully updated.') :
                __('The plugin has been successfully installed.')
            );
            Http::redirect($this->getURL() . '#plugins');
        } else {
            # --BEHAVIOR-- adminModulesListDoActions -- ModulesList, array<int,string>, string
            App::behavior()->callBehavior('adminModulesListDoActions', $this, $modules, 'plugin');
        }
    }

    /**
     * Display tab for manual installation.
     *
     * @return    mixed self instance or null
     */
    public function displayManualForm()
    {
        if (!App::auth()->isSuperAdmin() || !$this->isWritablePath()) {
            return;
        }

        // 'Upload module' form
        echo (new Form())
            ->method('post')
            ->action($this->getURL())
            ->enctype('multipart/form-data')
            ->id('uploadpkg')
            ->items([
                (new Fieldset())
                    ->legend(new Legend(__('Upload a zip file')))
                    ->items([
                        (new Note())
                            ->class('form-note')
                            ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Text('span', '*'))->class('required')->render())),
                        (new Para())
                            ->class('field')
                            ->items([
                                (new File('pkg_file'))
                                    ->required(true)
                                    ->label(
                                        (new Label((new Text('span', '*'))->render() . __('Zip file path:'), Label::OL_TF))
                                            ->class('required')
                                    ),
                            ]),
                        (new Para())
                            ->class('field')
                            ->items([
                                (new Password(['your_pwd', 'your_pwd1']))
                                    ->size(20)
                                    ->maxlength(255)
                                    ->required(true)
                                    ->placeholder(__('Password'))
                                    ->autocomplete('current-password')
                                    ->label(
                                        (new Label((new Text('span', '*'))->render() . __('Your password:'), Label::OL_TF))
                                            ->class('required')
                                    ),
                            ]),
                        (new Para())
                            ->class('form-buttons')
                            ->items([
                                App::nonce()->formNonce(),
                                (new Submit(['upload_pkg'], __('Upload'))),
                            ]),
                    ]),
            ])
        ->render();

        // 'Fetch module' form
        echo (new Form())
            ->method('post')
            ->action($this->getURL())
            ->enctype('multipart/form-data')
            ->id('fetchpkg')
            ->items([
                (new Fieldset())
                    ->legend(new Legend(__('Download a zip file')))
                    ->items([
                        (new Note())
                            ->class('form-note')
                            ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Text('span', '*'))->class('required')->render())),
                        (new Para())
                            ->class('field')
                            ->items([
                                (new Url('pkg_url'))
                                    ->size(40)
                                    ->maxlength(255)
                                    ->required(true)
                                    ->placeholder(__('URL'))
                                    ->label(
                                        (new Label((new Text('span', '*'))->render() . __('Zip file URL:'), Label::OL_TF))
                                            ->class('required')
                                    ),
                            ]),
                        (new Para())
                            ->class('field')
                            ->items([
                                (new Password(['your_pwd', 'your_pwd2']))
                                    ->size(20)
                                    ->maxlength(255)
                                    ->required(true)
                                    ->placeholder(__('Password'))
                                    ->autocomplete('current-password')
                                    ->label(
                                        (new Label((new Text('span', '*'))->render() . __('Your password:'), Label::OL_TF))
                                            ->class('required')
                                    ),
                            ]),
                        (new Para())
                            ->class('form-buttons')
                            ->items([
                                App::nonce()->formNonce(),
                                (new Submit(['fetch_pkg'], __('Download'))),
                            ]),
                    ]),
            ])
        ->render();

        return $this;
    }

    //@}

    /// @name Module configuration methods
    //@{

    /**
     * Prepare module configuration.
     *
     * We need to get configuration content in three steps
     * and out of this class to keep backward compatibility.
     *
     * if ($xxx->setConfiguration()) {
     *    include $xxx->includeConfiguration();
     * }
     * $xxx->getConfiguration();
     * ... [put here page headers and other stuff]
     * $xxx->displayConfiguration();
     *
     * @param    string    $id        Module to work on or it gather through REQUEST
     *
     * @return   bool  True if config set
     */
    public function setConfiguration(string $id = null): bool
    {
        if (empty($_REQUEST['conf']) || empty($_REQUEST['module']) && !$id) {
            return false;
        }

        if (!empty($_REQUEST['module']) && empty($id)) {
            $id = $_REQUEST['module'];
        }

        $define = $this->modules->getDefine($id, ['state' => ModuleDefine::STATE_ENABLED]);
        if (!$define->isDefined()) {
            App::error()->add(__('Unknown plugin ID'));

            return false;
        }

        self::fillSanitizeModule($define);
        $class = $define->get('namespace') . Autoloader::NS_SEP . $this->modules::MODULE_CLASS_CONFIG;
        $class = is_subclass_of($class, Process::class) ? $class : '';
        $file  = (string) Path::real($define->get('root') . DIRECTORY_SEPARATOR . $this->modules::MODULE_FILE_CONFIG);

        if (empty($class) && empty($file)) {
            App::error()->add(__('This plugin has no configuration file.'));

            return false;
        }

        if (!App::auth()->isSuperAdmin()
            && !App::auth()->check(App::plugins()->moduleInfo($id, 'permissions'), App::blog()->id())
        ) {
            App::error()->add(__('Insufficient permissions'));

            return false;
        }

        $this->config_define  = $define;
        $this->config_class   = $class;
        $this->config_file    = $file;
        $this->config_content = '';

        App::task()->addContext('MODULE');

        return true;
    }

    /**
     * Get path of module configuration file.
     *
     * @note Required previously set file info
     *
     * @return mixed    Full path of config file or null
     */
    public function includeConfiguration()
    {
        if (empty($this->config_class) && empty($this->config_file)) {
            return;
        }
        $this->setRedir($this->getURL() . '#plugins');

        ob_start();

        if (!empty($this->config_class)) {
            if ($this->config_class::init() && $this->config_class::process()) {
                $this->config_class::render();
            }

            return null;
        }

        return $this->config_file;
    }

    /**
     * Gather module configuration file content.
     *
     * @note Required previously file inclusion
     *
     * @return bool     True if content has been captured
     */
    public function getConfiguration(): bool
    {
        if (!empty($this->config_class) || !empty($this->config_file)) {
            $content              = ob_get_contents();
            $this->config_content = $content === false ? '' : $content;
        }

        ob_end_clean();

        return !empty($this->config_content);
    }

    /**
     * Display module configuration form.
     *
     * @note Required previously gathered content
     *
     * @return    ModulesList self instance
     */
    public function displayConfiguration(): ModulesList
    {
        if (($this->config_define instanceof ModuleDefine) && (!empty($this->config_class) || !empty($this->config_file))) {
            $items = [];

            if (!$this->config_define->get('standalone_config')) {
                $items = array_merge($items, [
                    (new Text('h3', sprintf(__('Configure "%s"'), Html::escapeHTML($this->config_define->get('name'))))),
                    (new Para())
                    ->items([
                        (new Link())
                            ->class('back')
                            ->href($this->getRedir())
                            ->text(__('Back')),
                    ]),
                ]);
            }

            $items[] = (new Text(null, $this->config_content));

            if (!$this->config_define->get('standalone_config') && !$this->config_define->get('information_config')) {
                // Module config is not only informative so it must be encapsulated in a form
                $items = array_merge($items, [
                    (new Para())
                        ->class('clear')
                        ->items([
                            (new Submit(['save'], __('Save'))),
                            (new Hidden('module', $this->config_define->getId())),
                            (new Hidden('redir', $this->getRedir())),
                            App::nonce()->formNonce(),
                        ]),
                ]);
            }

            if (!$this->config_define->get('standalone_config') && !$this->config_define->get('information_config')) {
                echo (new Form())
                    ->method('post')
                    ->action($this->getURL('conf=1'))
                    ->enctype('multipart/form-data')
                    ->id('module_config')
                    ->items($items)
                ->render();
            } else {
                echo (new Set())
                    ->items($items)
                ->render();
            }
        }

        return $this;
    }

    //@}

    /**
     * Helper to sanitize a string.
     *
     * Used for search or id.
     *
     * @param    string    $str        String to sanitize
     *
     * @return   string     Sanitized string
     */
    public static function sanitizeString(string $str): string
    {
        return (string) preg_replace('/[^A-Za-z0-9\@\#+_-]/', '', strtolower($str));
    }

    /**
     * Helper to check if a module's ns class or file exists.
     *
     * @param   string  $id     The module identifier
     * @param   string  $class  The module class name
     * @param   string  $file   The module file name
     *
     * @return  bool    True if one exists
     */
    protected static function hasFileOrClass(string $id, string $class, string $file): bool
    {
        // by class name
        $ns    = App::plugins()->moduleInfo($id, 'namespace');
        $class = $ns . Autoloader::NS_SEP . $class;
        if (!empty($ns) && class_exists($class)) {
            $has = $class::init();
            // by file name
        } else {
            $root = App::plugins()->moduleInfo($id, 'root');
            $has  = !empty($root) && file_exists((string) Path::real($root . DIRECTORY_SEPARATOR . $file));
        }

        return $has;
    }
}
