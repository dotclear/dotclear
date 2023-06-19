<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * Helper for admin list of modules.
 *
 * Provides an object to parse XML feed of modules from a repository.
 *
 * @since 2.6
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Text;

class adminModulesList
{
    /**
     * Stack of known modules
     *
     * @var dcModules
     */
    public $modules;

    /**
     * Store instance
     *
     * @var dcStore
     */
    public $store;

    /**
     * Work with multiple root directories
     *
     * @var        bool
     */
    public static $allow_multi_install = false;

    /**
     * List of modules distributed with Dotclear
     *
     * @deprecated 2.26 Use dcModules::getDefine($id)->distributed
     *
     * @var        array
     */
    public static $distributed_modules = [];

    /**
     * Current list ID
     *
     * @var        string
     */
    protected $list_id = 'unknown';

    /**
     * Current modules defines
     *
     * @var        array
     */
    protected $defines = [];

    /**
     * Module define to configure
     *
     * @var        dcModuleDefine
     */
    protected $config_define;
    /**
     * Module class to configure
     *
     * @var        string
     */
    protected $config_class = '';
    /**
     * Module path to configure
     *
     * @var        string
     */
    protected $config_file = '';
    /**
     * Module configuration page content
     *
     * @var        string
     */
    protected $config_content = '';

    /**
     * Modules root directories
     *
     * @var        string|null
     */
    protected $path;
    /**
     * Indicate if modules root directory is writable
     *
     * @var        bool
     */
    protected $path_writable = false;
    /**
     * Directory pattern to work on
     *
     * @var        string
     */
    protected $path_pattern = '';

    /**
     * Page URL
     *
     * @var        string
     */
    protected $page_url = '';
    /**
     * Page tab
     *
     * @var        string
     */
    protected $page_tab = '';
    /**
     * Page redirection
     *
     * @var        string
     */
    protected $page_redir = '';

    /**
     * Index list
     *
     * @var        string
     */
    public static $nav_indexes = 'abcdefghijklmnopqrstuvwxyz0123456789';

    /**
     * Index list with special index
     *
     * @var        array
     */
    protected $nav_list = [];
    /**
     * Text for other special index
     *
     * @var        string
     */
    protected $nav_special = 'other';

    /**
     * Field used to sort modules
     *
     * @var        string
     */
    protected $sort_field = 'sname';
    /**
     * Ascendant sort order?
     *
     * @var        bool
     */
    protected $sort_asc = true;

    /**
     * Constructor.
     *
     * Note that this creates dcStore instance.
     *
     * @param    dcModules    $modules        dcModules instance
     * @param    string       $modules_root   Modules root directories
     * @param    string       $xml_url        URL of modules feed from repository
     * @param    null|bool    $force          Force query repository
     */
    public function __construct(dcModules $modules, string $modules_root, string $xml_url, ?bool $force = false)
    {
        $this->modules = $modules;
        $this->store   = new dcStore($modules, $xml_url, $force);

        $this->page_url = dcCore::app()->adminurl->get('admin.plugins');

        $this->setPath($modules_root);
        $this->setIndex(__('other'));
    }

    /**
     * Begin a new list.
     *
     * @param    string    $id        New list ID
     *
     * @return    adminModulesList self instance
     */
    public function setList(string $id): adminModulesList
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
     * @return    adminModulesList self instance
     */
    protected function setPath(string $root): adminModulesList
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
        && (preg_match('!^' . $this->path_pattern . '!', $root) || defined('DC_DEV') && DC_DEV)
        && dcCore::app()->auth->isSuperAdmin();
    }

    //@}

    /// @name Page methods
    //@{

    /**
     * Set page base URL.
     *
     * @param    string    $url        Page base URL
     *
     * @return    adminModulesList self instance
     */
    public function setURL(string $url): adminModulesList
    {
        $this->page_url = $url;

        return $this;
    }

    /**
     * Get page URL.
     *
     * @param    string|array   $queries    Additionnal query string
     * @param    bool           $with_tab   Add current tab to URL end
     *
     * @return   string Clean page URL
     */
    public function getURL($queries = '', bool $with_tab = true): string
    {
        return $this->page_url .
            (!empty($queries) ? strpos($this->page_url, '?') ? '&amp;' : '?' : '') .
            (is_array($queries) ? http_build_query($queries) : $queries) .
            ($with_tab && !empty($this->page_tab) ? '#' . $this->page_tab : '');
    }

    /**
     * Set page tab.
     *
     * @param    string    $tab        Page tab
     *
     * @return    adminModulesList self instance
     */
    public function setTab(string $tab): adminModulesList
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
     * @return    adminModulesList self instance
     */
    public function setRedir(string $default = ''): adminModulesList
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
     * @return    adminModulesList self instance
     */
    public function displaySearch(): adminModulesList
    {
        $query = $this->getSearch();

        if (empty($this->defines) && $query === null) {
            return $this;
        }

        echo
        '<div class="modules-search">' .
        '<form action="' . $this->getURL() . '" method="get">' .
        '<p><label for="m_search" class="classic">' . __('Search in repository:') . '&nbsp;</label><br />' .
        form::field('m_search', 30, 255, Html::escapeHTML($query)) .
        '<input type="submit" value="' . __('OK') . '" /> ';

        if ($query) {
            echo
            ' <a href="' . $this->getURL() . '" class="button">' . __('Reset search') . '</a>';
        }

        echo
        '</p>' .
        '<p class="form-note">' .
        __('Search is allowed on multiple terms longer than 2 chars, terms must be separated by space.') .
            '</p>' .
            '</form>';

        if ($query) {
            echo
            '<p class="message">' . sprintf(
                __('Found %d result for search "%s":', 'Found %d results for search "%s":', count($this->defines)),
                count($this->defines),
                Html::escapeHTML($query)
            ) .
                '</p>';
        }
        echo '</div>';

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
     * @return    adminModulesList self instance
     */
    public function setIndex(string $str): adminModulesList
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
     * @return    adminModulesList self instance
     */
    public function displayIndex(): adminModulesList
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
                $buttons[] = '<li class="active" title="' . __('current selection') . '"><strong> ' . $char . ' </strong></li>';
            }
            # Letter having modules
            elseif (!empty($indexes[$char])) {
                $title     = sprintf(__('%d result', '%d results', $indexes[$char]), $indexes[$char]);
                $buttons[] = '<li class="btn" title="' . $title . '"><a href="' . $this->getURL('m_nav=' . $char) . '" title="' . $title . '"> ' . $char . ' </a></li>';
            }
            # Letter without modules
            else {
                $buttons[] = '<li class="btn no-link" title="' . __('no results') . '"> ' . $char . ' </li>';
            }
        }
        # Parse navigation menu
        echo '<div class="pager">' . __('Browse index:') . ' <ul class="index">' . implode('', $buttons) . '</ul></div>';

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
     * @return     adminModulesList     self instance
     */
    public function setSort(string $field, bool $asc = true): adminModulesList
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
     * Display sort field form.
     *
     * @todo      This method is not implemented yet
     *
     * @return    adminModulesList self instance
     */
    public function displaySort(): adminModulesList
    {
        // TODO

        return $this;
    }

    //@}

    /// @name Modules methods
    //@{

    /**
     * Set modules defines and sanitize them.
     *
     * @param   array   $defines
     *
     * @return    adminModulesList self instance
     */
    public function setDefines(array $defines): adminModulesList
    {
        $this->defines = [];

        foreach ($defines as $define) {
            if (!($define instanceof dcModuleDefine)) {
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
     * @return    array        Array of modules
     */
    public function getDefines(): array
    {
        return $this->defines;
    }

    /**
     * Set modules and sanitize them.
     *
     * @deprecated since 2.26 Use self::setDefines()
     *
     * @param   array   $modules
     *
     * @return    adminModulesList self instance
     */
    public function setModules(array $modules): adminModulesList
    {
        dcDeprecated::set('adminModulesList::setDefines()', '2.26');

        $defines = [];
        foreach ($modules as $id => $module) {
            $define = new dcModuleDefine($id);
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
     * @deprecated since 2.26 Use self::getDefines()
     *
     * @return    array        Array of modules
     */
    public function getModules(): array
    {
        dcDeprecated::set('adminModulesList::getDefines()', '2.26');

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
     * @param      dcModuleDefine   $define The module definition
     * @param      array            $module  The module
     */
    public static function fillSanitizeModule(dcModuleDefine $define, array $module = []): void
    {
        foreach ($module as $k => $v) {
            $define->set($k, $v);
        }

        $define
            ->set('sid', self::sanitizeString($define->getId()))
            ->set('label', empty($define->get('label')) ? $define->getId() : $define->get('label'))
            ->set('name', __(empty($define->get('name')) ? $define->label : $define->get('name')))
            ->set('sname', self::sanitizeString(strtolower(Text::removeDiacritics($define->get('name')))));
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
     * @deprecated since 2.26 Use self::fillSanitizeModule()
     *
     * @param      string  $id      The identifier
     * @param      array   $module  The module
     *
     * @return   array  Array of the module informations
     */
    public static function sanitizeModule(string $id, array $module): array
    {
        dcDeprecated::set('adminModulesList::fillSanitizeModule()', '2.26');

        $define = new dcModuleDefine($id);
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
     * @deprecated since 2.26 Use self::fillSanitizeModule()
     *
     * @param      string  $id      The identifier
     * @param      array   $module  The module
     *
     * @return   array  Array of the module informations
     */
    public function doSanitizeModule(string $id, array $module): array
    {
        dcDeprecated::set('adminModulesList::fillSanitizeModule()', '2.26');

        $define = $this->modules->getDefine($id);
        self::fillSanitizeModule($define, $module);

        return $define->dump();
    }

    /**
     * Check if a module is part of the distribution.
     *
     * @deprecated 2.26 Use dcModules::getDefine($id)->distributed
     *
     * @param    string    $id        Module root directory
     *
     * @return   bool  True if module is part of the distribution
     */
    public static function isDistributedModule(string $id): bool
    {
        dcDeprecated::set('dcModules::getDefine($id)->distributed', '2.26');

        return in_array($id, self::$distributed_modules);
    }

    /**
     * Sort modules list by specific field.
     *
     * @deprecated since 2.26 Use something like uasort($defines, fn ($a, $b) => $a->get($field) <=> $b->get($field));
     *
     * @param    array     $modules      Array of modules
     * @param    string    $field        Field to sort from
     * @param    bool      $asc          Sort asc if true, else decs
     *
     * @return   array  Array of sorted modules
     */
    public static function sortModules(array $modules, string $field, bool $asc = true): array
    {
        dcDeprecated::set('uasort()', '2.26');

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
     * @param    array    $cols         List of colones (module field) to display
     * @param    array    $actions      List of predefined actions to show on form
     * @param    bool     $nav_limit    Limit list to previously selected index
     *
     * @return    adminModulesList self instance
     */
    public function displayModules(array $cols = ['name', 'version', 'desc'], array $actions = [], bool $nav_limit = false): adminModulesList
    {
        echo
        '<form action="' . $this->getURL() . '" method="post" class="modules-form-actions">' .
        '<div class="table-outer">' .
        '<table id="' . Html::escapeHTML($this->list_id) . '" class="modules' . (in_array('expander', $cols) ? ' expandable' : '') . '">' .
        '<caption class="hidden">' . Html::escapeHTML(__('Plugins list')) . '</caption><tr>';

        if (in_array('name', $cols)) {
            $colspan = 1;
            if (in_array('checkbox', $cols)) {
                $colspan++;
            }
            if (in_array('icon', $cols)) {
                $colspan++;
            }
            echo
            '<th class="first nowrap"' . ($colspan > 1 ? ' colspan="' . $colspan . '"' : '') . '>' . __('Name') . '</th>';
        }

        if (in_array('score', $cols) && $this->getSearch() !== null && defined('DC_DEBUG') && DC_DEBUG) {
            echo
            '<th class="nowrap">' . __('Score') . '</th>';
        }

        if (in_array('version', $cols)) {
            echo
            '<th class="nowrap count" scope="col">' . __('Version') . '</th>';
        }

        if (in_array('current_version', $cols)) {
            echo
            '<th class="nowrap count" scope="col">' . __('Current version') . '</th>';
        }

        if (in_array('desc', $cols)) {
            echo
            '<th class="nowrap module-desc" scope="col">' . __('Details') . '</th>';
        }

        if (in_array('repository', $cols) && DC_ALLOW_REPOSITORIES) {
            echo
            '<th class="nowrap count" scope="col">' . __('Repository') . '</th>';
        }

        if (in_array('distrib', $cols)) {
            echo
                '<th' . (in_array('desc', $cols) ? '' : ' class="maximal"') . '></th>';
        }

        if (!empty($actions) && dcCore::app()->auth->isSuperAdmin()) {
            echo
            '<th class="minimal nowrap">' . __('Action') . '</th>';
        }

        echo
            '</tr>';

        $sort_field = $this->getSort();

        # Sort modules by $sort_field (default sname)
        if ($this->getSearch() === null) {
            uasort($this->defines, fn ($a, $b) => $a->get($sort_field) <=> $b->get($sort_field));
        }

        $count = 0;
        foreach ($this->defines as $define) {
            $id = $define->getId();

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
            $git = ((defined('DC_DEV') && DC_DEV) || (defined('DC_DEBUG') && DC_DEBUG)) && file_exists($define->get('root') . '/.git');

            echo
            '<tr class="line' . ($git ? ' module-git' : '') . '" id="' . Html::escapeHTML($this->list_id) . '_m_' . Html::escapeHTML($id) . '"' .
                (in_array('desc', $cols) ? ' title="' . Html::escapeHTML(__($define->get('desc'))) . '" ' : '') .
                '>';

            $tds = 0;

            if (in_array('checkbox', $cols)) {
                $tds++;
                echo
                '<td class="module-icon nowrap">' .
                form::checkbox(['modules[' . $count . ']', Html::escapeHTML($this->list_id) . '_modules_' . Html::escapeHTML($id)], Html::escapeHTML($id)) .
                    '</td>';
            }

            if (in_array('icon', $cols)) {
                $tds++;
                $default_icon = false;

                if (file_exists($define->get('root') . DIRECTORY_SEPARATOR . 'icon.svg')) {
                    $icon = dcPage::getPF($id . '/icon.svg');
                } elseif (file_exists($define->get('root') . DIRECTORY_SEPARATOR . 'icon.png')) {
                    $icon = dcPage::getPF($id . '/icon.png');
                } else {
                    $icon         = 'images/module.svg';
                    $default_icon = true;
                }
                if (file_exists($define->get('root') . DIRECTORY_SEPARATOR . 'icon-dark.svg')) {
                    $icon = [$icon, dcPage::getPF($id . '/icon-dark.svg')];
                } elseif (file_exists($define->get('root') . DIRECTORY_SEPARATOR . 'icon-dark.png')) {
                    $icon = [$icon, dcPage::getPF($id . '/icon-dark.png')];
                } elseif ($default_icon) {
                    $icon = [$icon, 'images/module-dark.svg'];
                }

                echo
                '<td class="module-icon nowrap">' .
                dcAdminHelper::adminIcon($icon, false, Html::escapeHTML($id), Html::escapeHTML($id)) .
                '</td>';
            }

            $tds++;
            echo
            '<th class="module-name nowrap" scope="row">';
            if (in_array('checkbox', $cols)) {
                if (in_array('expander', $cols)) {
                    echo
                    Html::escapeHTML($define->get('name')) . ($id != $define->get('name') ? sprintf(__(' (%s)'), $id) : '');
                } else {
                    echo
                    '<label for="' . Html::escapeHTML($this->list_id) . '_modules_' . Html::escapeHTML($id) . '">' .
                    Html::escapeHTML($define->get('name')) . ($id != $define->get('name') ? sprintf(__(' (%s)'), $id) : '') .
                    '</label>';
                }
            } else {
                echo
                Html::escapeHTML($define->get('name')) . ($id != $define->get('name') ? sprintf(__(' (%s)'), $id) : '') .
                form::hidden(['modules[' . $count . ']'], Html::escapeHTML($id));
            }
            echo
            dcCore::app()->formNonce() .
            '</td>';

            # Display score only for debug purpose
            if (in_array('score', $cols) && $this->getSearch() !== null && defined('DC_DEBUG') && DC_DEBUG) {
                $tds++;
                echo
                '<td class="module-version nowrap count"><span class="debug">' . $define->get('score') . '</span></td>';
            }

            if (in_array('version', $cols)) {
                $tds++;
                echo
                '<td class="module-version nowrap count">' . Html::escapeHTML($define->get('version')) . '</td>';
            }

            if (in_array('current_version', $cols)) {
                $tds++;
                echo
                '<td class="module-current-version nowrap count">' . Html::escapeHTML($define->get('current_version')) . '</td>';
            }

            if (in_array('desc', $cols)) {
                $tds++;
                $note = '';
                if (!empty($define->getUsing()) && $define->get('state') == dcModuleDefine::STATE_ENABLED) {
                    $note .= '<p><span class="info">' .
                    sprintf(
                        __('This module cannot be disabled nor deleted, since the following modules are also enabled : %s'),
                        join(',', $define->getUsing())
                    ) . '</span></p>';
                }
                if (!empty($define->getMissing()) && $define->get('state') != dcModuleDefine::STATE_ENABLED) {
                    $note .= '<p><span class="info">' .
                    __('This module cannot be enabled, because of the following reasons :') . '<ul>';
                    foreach ($define->getMissing() as $reason) {
                        $note .= '<li>' . $reason . '</li>';
                    }
                    $note .= '</ul></span></p>';
                }
                echo
                '<td class="module-desc maximal">' .
                ($note !== '' ? '<details><summary>' : '') .
                Html::escapeHTML(__($define->get('desc'))) .
                ($note !== '' ? '</summary>' . $note . '</details>' : '') .
                '</td>';
            }

            if (in_array('repository', $cols) && DC_ALLOW_REPOSITORIES) {
                $tds++;
                echo
                '<td class="module-repository nowrap count">' . (!empty($define->get('repository')) ? __('Third-party repository') : __('Official repository')) . '</td>';
            }

            if (in_array('distrib', $cols)) {
                $tds++;
                echo
                    '<td class="module-distrib">' . ($define->get('distributed') ?
                    '<img src="images/dotclear-leaf.svg" alt="' .
                    __('Plugin from official distribution') . '" title="' .
                    __('Plugin from official distribution') . '" />'
                    : ($git ?
                        '<img src="images/git-branch.svg" alt="' .
                        __('Plugin in development') . '" title="' .
                        __('Plugin in development') . '" />'
                        : '')) . '</td>';
            }

            if (!empty($actions) && dcCore::app()->auth->isSuperAdmin()) {
                $buttons = $this->getActions($define, $actions);

                $tds++;
                echo
                '<td class="module-actions nowrap">' .

                '<div>' . implode(' ', $buttons) . '</div>' .

                    '</td>';
            }

            echo
                '</tr>';

            # Other informations
            if (in_array('expander', $cols)) {
                echo
                    '<tr class="module-more"><td colspan="' . $tds . '" class="expand">';

                if (!empty($define->get('author')) || !empty($define->get('details')) || !empty($define->get('support'))) {
                    echo
                        '<div><ul class="mod-more">';

                    if (!empty($define->get('author'))) {
                        echo
                        '<li class="module-author">' . __('Author:') . ' ' . Html::escapeHTML($define->get('author')) . '</li>';
                    }

                    $more = [];
                    if (!empty($define->get('details'))) {
                        $more[] = '<a class="module-details" href="' . $define->get('details') . '">' . __('Details') . '</a>';
                    }

                    if (!empty($define->get('support'))) {
                        $more[] = '<a class="module-support" href="' . $define->get('support') . '">' . __('Support') . '</a>';
                    }

                    if ($define->updLocked()) {
                        $more[] = '<span class="module-locked">' . __('update locked') . '</span>';
                    }

                    if (!empty($more)) {
                        echo
                        '<li>' . implode(' - ', $more) . '</li>';
                    }

                    echo
                        '</ul></div>';
                }

                if (self::hasFileOrClass($id, dcModules::MODULE_CLASS_CONFIG, dcModules::MODULE_FILE_CONFIG)
                 || self::hasFileOrClass($id, dcModules::MODULE_CLASS_MANAGE, dcModules::MODULE_FILE_MANAGE)
                 || !empty($define->get('section'))
                 || !empty($define->get('tags'))
                 || !empty($define->get('settings'))   && $define->get('state') == dcModuleDefine::STATE_ENABLED
                 || !empty($define->get('repository')) && DC_DEBUG && DC_ALLOW_REPOSITORIES
                ) {
                    echo
                        '<div><ul class="mod-more">';

                    $settings = static::getSettingsUrls($id);
                    if (!empty($settings) && $define->get('state') == dcModuleDefine::STATE_ENABLED) {
                        echo '<li>' . implode(' - ', $settings) . '</li>';
                    }

                    if (!empty($define->get('repository')) && DC_DEBUG && DC_ALLOW_REPOSITORIES) {
                        echo '<li class="modules-repository"><a href="' . $define->get('repository') . '">' . __('Third-party repository') . '</a></li>';
                    }

                    if (!empty($define->get('section'))) {
                        echo
                        '<li class="module-section">' . __('Section:') . ' ' . Html::escapeHTML($define->get('section')) . '</li>';
                    }

                    if (!empty($define->get('tags'))) {
                        echo
                        '<li class="module-tags">' . __('Tags:') . ' ' . Html::escapeHTML($define->get('tags')) . '</li>';
                    }

                    echo
                        '</ul></div>';
                }

                echo
                    '</td></tr>';
            }

            $count++;
        }
        echo
            '</table></div>';

        if (!$count && $this->getSearch() === null) {
            echo
            '<p class="message">' . __('No plugins matched your search.') . '</p>';
        } elseif ((in_array('checkbox', $cols) || $count > 1) && !empty($actions) && dcCore::app()->auth->isSuperAdmin()) {
            $buttons = $this->getGlobalActions($actions, in_array('checkbox', $cols));

            if (!empty($buttons)) {
                if (in_array('checkbox', $cols)) {
                    echo
                        '<p class="checkboxes-helpers"></p>';
                }
                echo
                '<div>' . implode(' ', $buttons) . '</div>';
            }
        }
        echo
            '</form>';

        return $this;
    }

    /**
     * Get settings URLs if any
     *
     * @param   string  $id     Module ID
     * @param   boolean $check  Check permission
     * @param   boolean $self   Include self URL (→ plugin index.php URL)
     *
     * @return array    Array of settings URLs
     */
    public static function getSettingsUrls(string $id, bool $check = false, bool $self = true): array
    {
        $settings_urls = [];

        $config = self::hasFileOrClass($id, dcModules::MODULE_CLASS_CONFIG, dcModules::MODULE_FILE_CONFIG);
        $index  = self::hasFileOrClass($id, dcModules::MODULE_CLASS_MANAGE, dcModules::MODULE_FILE_MANAGE);

        $settings = dcCore::app()->plugins->moduleInfo($id, 'settings');
        if ($self) {
            if (isset($settings['self']) && $settings['self'] === false) {
                $self = false;
            }
        }
        if ($config || $index || !empty($settings)) {
            if ($config) {
                if (!$check || dcCore::app()->auth->isSuperAdmin() || dcCore::app()->auth->check(dcCore::app()->plugins->moduleInfo($id, 'permissions'), dcCore::app()->blog->id)) {
                    $params = ['module' => $id, 'conf' => '1'];
                    if (!dcCore::app()->plugins->moduleInfo($id, 'standalone_config') && !$self) {
                        $params['redir'] = dcCore::app()->adminurl->get('admin.plugin.' . $id);
                    }
                    $settings_urls[] = '<a class="module-config" href="' .
                    dcCore::app()->adminurl->get('admin.plugins', $params) .
                    '">' . __('Configure plugin') . '</a>';
                }
            }
            if (is_array($settings)) {
                foreach ($settings as $sk => $sv) {
                    switch ($sk) {
                        case 'blog':
                            if (!$check || dcCore::app()->auth->isSuperAdmin() || dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                                dcAuth::PERMISSION_ADMIN,
                            ]), dcCore::app()->blog->id)) {
                                $settings_urls[] = '<a class="module-config" href="' .
                                dcCore::app()->adminurl->get('admin.blog.pref') . $sv .
                                '">' . __('Plugin settings (in blog parameters)') . '</a>';
                            }

                            break;
                        case 'pref':
                            if (!$check || dcCore::app()->auth->isSuperAdmin() || dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                                dcAuth::PERMISSION_USAGE,
                                dcAuth::PERMISSION_CONTENT_ADMIN,
                            ]), dcCore::app()->blog->id)) {
                                $settings_urls[] = '<a class="module-config" href="' .
                                dcCore::app()->adminurl->get('admin.user.preferences') . $sv .
                                '">' . __('Plugin settings (in user preferences)') . '</a>';
                            }

                            break;
                        case 'self':
                            if ($self) {
                                if (!$check || dcCore::app()->auth->isSuperAdmin() || dcCore::app()->auth->check(dcCore::app()->plugins->moduleInfo($id, 'permissions'), dcCore::app()->blog->id)) {
                                    $settings_urls[] = '<a class="module-config" href="' .
                                    dcCore::app()->adminurl->get('admin.plugin.' . $id) . $sv .
                                    '">' . __('Plugin settings') . '</a>';
                                }
                                // No need to use default index.php
                                $index = false;
                            }

                            break;
                        case 'other':
                            if (!$check || dcCore::app()->auth->isSuperAdmin() || dcCore::app()->auth->check(dcCore::app()->plugins->moduleInfo($id, 'permissions'), dcCore::app()->blog->id)) {
                                $settings_urls[] = '<a class="module-config" href="' .
                                $sv .
                                '">' . __('Plugin settings') . '</a>';
                            }

                            break;
                    }
                }
            }
            if ($index && $self) {
                if (!$check || dcCore::app()->auth->isSuperAdmin() || dcCore::app()->auth->check(dcCore::app()->plugins->moduleInfo($id, 'permissions'), dcCore::app()->blog->id)) {
                    $settings_urls[] = '<a class="module-config" href="' .
                    dcCore::app()->adminurl->get('admin.plugin.' . $id) .
                    '">' . __('Plugin main page') . '</a>';
                }
            }
        }

        return $settings_urls;
    }

    /**
     * Get action buttons to add to modules list.
     *
     * @param    dcModuleDefine     $define     Module info
     * @param    array              $actions    Actions keys
     *
     * @return   array    Array of actions buttons
     */
    protected function getActions(dcModuleDefine $define, array $actions): array
    {
        $submits = [];
        $id      = $define->getId();

        // mark module state
        if ($define->get('state') != dcModuleDefine::STATE_ENABLED) {
            $submits[] = '<input type="hidden" name="disabled[' . Html::escapeHTML($id) . ']" value="1" />';
        }

        # Use loop to keep requested order
        foreach ($actions as $action) {
            switch ($action) {
                # Deactivate
                case 'activate':
                    // do not allow activation of duplciate modules already activated
                    $multi = !self::$allow_multi_install && count($this->modules->getDefines(['id' => $id, 'state' => dcModuleDefine::STATE_ENABLED])) > 0;
                    if (dcCore::app()->auth->isSuperAdmin() && $define->get('root_writable') && empty($define->getMissing()) && !$multi) {
                        $submits[] = '<input type="submit" name="activate[' . Html::escapeHTML($id) . ']" value="' . __('Activate') . '" />';
                    }

                    break;

                    # Activate
                case 'deactivate':
                    if (dcCore::app()->auth->isSuperAdmin() && $define->get('root_writable') && empty($define->getUsing())) {
                        $submits[] = '<input type="submit" name="deactivate[' . Html::escapeHTML($id) . ']" value="' . __('Deactivate') . '" class="reset" />';
                    }

                    break;

                    # Delete
                case 'delete':
                    if (dcCore::app()->auth->isSuperAdmin() && $this->isDeletablePath($define->get('root')) && empty($define->getUsing())) {
                        $dev       = !preg_match('!^' . $this->path_pattern . '!', $define->get('root')) && defined('DC_DEV') && DC_DEV ? ' debug' : '';
                        $submits[] = '<input type="submit" class="delete ' . $dev . '" name="delete[' . Html::escapeHTML($id) . ']" value="' . __('Delete') . '" />';
                    }

                    break;

                    # Clone
                case 'clone':
                    if (dcCore::app()->auth->isSuperAdmin() && $this->path_writable) {
                        $submits[] = '<input type="submit" class="button clone" name="clone[' . Html::escapeHTML($id) . ']" value="' . __('Clone') . '" />';
                    }

                    break;

                    # Install (from store)
                case 'install':
                    if (dcCore::app()->auth->isSuperAdmin() && $this->path_writable) {
                        $submits[] = '<input type="submit" name="install[' . Html::escapeHTML($id) . ']" value="' . __('Install') . '" />';
                    }

                    break;

                    # Update (from store)
                case 'update':
                    if (dcCore::app()->auth->isSuperAdmin() && $this->path_writable && !$define->updLocked()) {
                        $submits[] = '<input type="submit" name="update[' . Html::escapeHTML($id) . ']" value="' . __('Update') . '" />';
                    }

                    break;

                    # Behavior
                case 'behavior':

                    # --BEHAVIOR-- adminModulesListGetActions -- adminModulesList, dcModuleDefine
                    $tmp = dcCore::app()->callBehavior('adminModulesListGetActionsV2', $this, $define);

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
     * @param   array   $actions            Actions keys
     * @param   bool    $with_selection     Limit action to selected modules
     *
     * @return  array  Array of actions buttons
     */
    protected function getGlobalActions(array $actions, bool $with_selection = false): array
    {
        $submits = [];

        # Use loop to keep requested order
        foreach ($actions as $action) {
            switch ($action) {
                # Deactivate
                case 'activate':
                    if (dcCore::app()->auth->isSuperAdmin() && $this->path_writable) {
                        $submits[] = '<input type="submit" name="activate" value="' . (
                            $with_selection ?
                            __('Activate selected plugins') :
                            __('Activate all plugins from this list')
                        ) . '" />';
                    }

                    break;

                    # Activate
                case 'deactivate':
                    if (dcCore::app()->auth->isSuperAdmin() && $this->path_writable) {
                        $submits[] = '<input type="submit" name="deactivate" value="' . (
                            $with_selection ?
                            __('Deactivate selected plugins') :
                            __('Deactivate all plugins from this list')
                        ) . '" />';
                    }

                    break;

                    # Update (from store)
                case 'update':
                    if (dcCore::app()->auth->isSuperAdmin() && $this->path_writable) {
                        $submits[] = '<input type="submit" name="update" value="' . (
                            $with_selection ?
                            __('Update selected plugins') :
                            __('Update all plugins from this list')
                        ) . '" />';
                    }

                    break;

                    # Behavior
                case 'behavior':

                    # --BEHAVIOR-- adminModulesListGetGlobalActions -- adminModulesList, bool
                    $tmp = dcCore::app()->callBehavior('adminModulesListGetGlobalActions', $this, $with_selection);

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
     * Set a notice on success through dcPage::addSuccessNotice
     *
     * @throws    Exception    Module not find or command failed
     */
    public function doActions()
    {
        if (empty($_POST) || !empty($_REQUEST['conf'])
                          || !$this->isWritablePath()) {
            return;
        }

        $modules = !empty($_POST['modules']) && is_array($_POST['modules']) ? array_values($_POST['modules']) : [];

        if (dcCore::app()->auth->isSuperAdmin() && !empty($_POST['delete'])) {
            if (is_array($_POST['delete'])) {
                $modules = array_keys($_POST['delete']);
            }

            $failed = false;
            $count  = 0;
            foreach ($modules as $id) {
                $disabled = !empty($_POST['disabled'][$id]);
                $define   = $this->modules->getDefine($id, ['state' => ($disabled ? '!' : '') . dcModuleDefine::STATE_ENABLED]);
                // module is not defined
                if (!$define->isDefined()) {
                    throw new Exception(__('No such plugin.'));
                }
                if (!$this->isDeletablePath($define->get('root'))) {
                    $failed = true;

                    continue;
                }

                # --BEHAVIOR-- moduleBeforeDelete -- dcModuleDefine
                dcCore::app()->callBehavior('pluginBeforeDeleteV2', $define);

                $this->modules->deleteModule($define->getId(), $disabled);

                # --BEHAVIOR-- moduleAfterDelete -- dcModuleDefine
                dcCore::app()->callBehavior('pluginAfterDeleteV2', $define);

                $count++;
            }

            if (!$count && $failed) {
                throw new Exception(__("You don't have permissions to delete this plugin."));
            } elseif ($failed) {
                dcPage::addWarningNotice(__('Some plugins have not been delete.'));
            } else {
                dcPage::addSuccessNotice(
                    __('Plugin has been successfully deleted.', 'Plugins have been successuflly deleted.', $count)
                );
            }
            Http::redirect($this->getURL());
        } elseif (dcCore::app()->auth->isSuperAdmin() && !empty($_POST['install'])) {
            if (is_array($_POST['install'])) {
                $modules = array_keys($_POST['install']);
            }

            $count = 0;
            foreach ($this->store->getDefines() as $define) {
                if (!in_array($define->getId(), $modules)) {
                    continue;
                }

                $dest = $this->getPath() . DIRECTORY_SEPARATOR . basename($define->get('file'));

                # --BEHAVIOR-- moduleBeforeAdd -- dcModuleDefine
                dcCore::app()->callBehavior('pluginBeforeAddV2', $define);

                $this->store->process($define->get('file'), $dest);

                # --BEHAVIOR-- moduleAfterAdd -- dcModuleDefine
                dcCore::app()->callBehavior('pluginAfterAddV2', $define);

                $count++;
            }

            if (!$count) {
                throw new Exception(__('No such plugin.'));
            }

            dcPage::addSuccessNotice(
                __('Plugin has been successfully installed.', 'Plugins have been successfully installed.', $count)
            );
            Http::redirect($this->getURL());
        } elseif (dcCore::app()->auth->isSuperAdmin() && !empty($_POST['activate'])) {
            if (is_array($_POST['activate'])) {
                $modules = array_keys($_POST['activate']);
            }

            $count = 0;
            foreach ($modules as $id) {
                $define = $this->modules->getDefine($id, ['state' => '!' . dcModuleDefine::STATE_ENABLED]);
                if (!$define->isDefined()) {
                    continue;
                }

                # --BEHAVIOR-- moduleBeforeActivate -- string
                dcCore::app()->callBehavior('pluginBeforeActivate', $define->getId());

                $this->modules->activateModule($define->getId());

                # --BEHAVIOR-- moduleAfterActivate -- string
                dcCore::app()->callBehavior('pluginAfterActivate', $define->getId());

                $count++;
            }

            if (!$count) {
                throw new Exception(__('No such plugin.'));
            }

            dcPage::addSuccessNotice(
                __('Plugin has been successfully activated.', 'Plugins have been successuflly activated.', $count)
            );
            Http::redirect($this->getURL());
        } elseif (dcCore::app()->auth->isSuperAdmin() && !empty($_POST['deactivate'])) {
            if (is_array($_POST['deactivate'])) {
                $modules = array_keys($_POST['deactivate']);
            }

            $failed = false;
            $count  = 0;
            foreach ($modules as $id) {
                $define = $this->modules->getDefine($id);
                if (!$define->isDefined() || $define->get('state') == dcModuleDefine::STATE_HARD_DISABLED) {
                    continue;
                }

                if (!$define->get('root_writable')) {
                    $failed = true;

                    continue;
                }

                # --BEHAVIOR-- moduleBeforeDeactivate -- dcModuleDefine
                dcCore::app()->callBehavior('pluginBeforeDeactivateV2', $define);

                $this->modules->deactivateModule($define->getId());

                # --BEHAVIOR-- moduleAfterDeactivate -- dcModuleDefine
                dcCore::app()->callBehavior('pluginAfterDeactivateV2', $define);

                $count++;
            }

            if (!$count) {
                throw new Exception(__('No such plugin.'));
            }

            if ($failed) {
                dcPage::addWarningNotice(__('Some plugins have not been deactivated.'));
            } else {
                dcPage::addSuccessNotice(
                    __('Plugin has been successfully deactivated.', 'Plugins have been successuflly deactivated.', $count)
                );
            }
            Http::redirect($this->getURL());
        } elseif (dcCore::app()->auth->isSuperAdmin() && !empty($_POST['update'])) {
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
                        @file_put_contents($define->get('root') . DIRECTORY_SEPARATOR . dcModules::MODULE_FILE_DISABLED, '');
                    }
                }

                # --BEHAVIOR-- moduleBeforeUpdate -- dcModuleDefine
                dcCore::app()->callBehavior('pluginBeforeUpdateV2', $define);

                $this->store->process($define->get('file'), $dest);

                # --BEHAVIOR-- moduleAfterUpdate -- dcModuleDefine
                dcCore::app()->callBehavior('pluginAfterUpdateV2', $define);

                $count++;
            }

            $tab = $count == count($defines) ? '#plugins' : '#update';   // @phpstan-ignore-line

            if ($count) {
                dcPage::addSuccessNotice(
                    __('Plugin has been successfully updated.', 'Plugins have been successfully updated.', $count)
                );
            } elseif (!empty($locked)) {
                dcPage::addWarningNotice(
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
            if (empty($_POST['your_pwd']) || !dcCore::app()->auth->checkPassword($_POST['your_pwd'])) {
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
            dcCore::app()->callBehavior('pluginBeforeAdd', null);

            $ret_code = $this->store->install($dest);

            # --BEHAVIOR-- moduleAfterAdd --
            dcCore::app()->callBehavior('pluginAfterAdd', null);

            dcPage::addSuccessNotice(
                $ret_code === dcModules::PACKAGE_UPDATED ?
                __('The plugin has been successfully updated.') :
                __('The plugin has been successfully installed.')
            );
            Http::redirect($this->getURL() . '#plugins');
        } else {
            # --BEHAVIOR-- adminModulesListDoActions -- adminModulesList, array<int,string>, string
            dcCore::app()->callBehavior('adminModulesListDoActions', $this, $modules, 'plugin');
        }
    }

    /**
     * Display tab for manual installation.
     *
     * @return    mixed self instance or null
     */
    public function displayManualForm()
    {
        if (!dcCore::app()->auth->isSuperAdmin() || !$this->isWritablePath()) {
            return;
        }

        # 'Upload module' form
        echo
        '<form method="post" action="' . $this->getURL() . '" id="uploadpkg" enctype="multipart/form-data" class="fieldset">' .
        '<h4>' . __('Upload a zip file') . '</h4>' .
        '<p class="field"><label for="pkg_file" class="classic required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Zip file path:') . '</label> ' .
        '<input type="file" name="pkg_file" id="pkg_file" required /></p>' .
        '<p class="field"><label for="your_pwd1" class="classic required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Your password:') . '</label> ' .
        form::password(
            ['your_pwd', 'your_pwd1'],
            20,
            255,
            [
                'extra_html'   => 'required placeholder="' . __('Password') . '"',
                'autocomplete' => 'current-password',
            ]
        ) . '</p>' .
        '<p><input type="submit" name="upload_pkg" value="' . __('Upload') . '" />' .
        dcCore::app()->formNonce() . '</p>' .
            '</form>';

        # 'Fetch module' form
        echo
        '<form method="post" action="' . $this->getURL() . '" id="fetchpkg" class="fieldset">' .
        '<h4>' . __('Download a zip file') . '</h4>' .
        '<p class="field"><label for="pkg_url" class="classic required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Zip file URL:') . '</label> ' .
        form::field('pkg_url', 40, 255, [
            'extra_html' => 'required placeholder="' . __('URL') . '"',
        ]) .
        '</p>' .
        '<p class="field"><label for="your_pwd2" class="classic required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Your password:') . '</label> ' .
        form::password(
            ['your_pwd', 'your_pwd2'],
            20,
            255,
            [
                'extra_html'   => 'required placeholder="' . __('Password') . '"',
                'autocomplete' => 'current-password',
            ]
        ) . '</p>' .
        '<p><input type="submit" name="fetch_pkg" value="' . __('Download') . '" />' .
        dcCore::app()->formNonce() . '</p>' .
            '</form>';

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

        $define = $this->modules->getDefine($id, ['state' => dcModuleDefine::STATE_ENABLED]);
        if (!$define->isDefined()) {
            dcCore::app()->error->add(__('Unknown plugin ID'));

            return false;
        }

        self::fillSanitizeModule($define);
        $class = $define->get('namespace') . Autoloader::NS_SEP . dcModules::MODULE_CLASS_CONFIG;
        $class = empty($define->get('namespace')) || !class_exists($class) ? '' : $class;
        $file  = Path::real($define->get('root') . DIRECTORY_SEPARATOR . dcModules::MODULE_FILE_CONFIG);

        if (empty($class) && !file_exists($file)) {
            dcCore::app()->error->add(__('This plugin has no configuration file.'));

            return false;
        }

        if (!dcCore::app()->auth->isSuperAdmin()
            && !dcCore::app()->auth->check(dcCore::app()->plugins->moduleInfo($id, 'permissions'), dcCore::app()->blog->id)
        ) {
            dcCore::app()->error->add(__('Insufficient permissions'));

            return false;
        }

        $this->config_define  = $define;
        $this->config_class   = $class;
        $this->config_file    = $file;
        $this->config_content = '';

        if (!defined('DC_CONTEXT_MODULE')) {
            define('DC_CONTEXT_MODULE', true);
        }

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
        if (empty($this->config_class) && !$this->config_file) {
            return;
        }
        $this->setRedir($this->getURL() . '#plugins');

        ob_start();

        if (!empty($this->config_class) && $this->config_class::init() && $this->config_class::process()) {
            $this->config_class::render();

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
            $this->config_content = ob_get_contents();
        }

        ob_end_clean();

        return !empty($this->config_content);
    }

    /**
     * Display module configuration form.
     *
     * @note Required previously gathered content
     *
     * @return    adminModulesList self instance
     */
    public function displayConfiguration(): adminModulesList
    {
        if (($this->config_define instanceof dcModuleDefine) && (!empty($this->config_class) || !empty($this->config_file))) {
            if (!$this->config_define->get('standalone_config')) {
                echo
                '<form id="module_config" action="' . $this->getURL('conf=1') . '" method="post" enctype="multipart/form-data">' .
                '<h3>' . sprintf(__('Configure "%s"'), Html::escapeHTML($this->config_define->get('name'))) . '</h3>' .
                '<p><a class="back" href="' . $this->getRedir() . '">' . __('Back') . '</a></p>';
            }

            echo $this->config_content;

            if (!$this->config_define->get('standalone_config')) {
                echo
                '<p class="clear"><input type="submit" name="save" value="' . __('Save') . '" />' .
                form::hidden('module', $this->config_define->getId()) .
                form::hidden('redir', $this->getRedir()) .
                dcCore::app()->formNonce() . '</p>' .
                    '</form>';
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
        return preg_replace('/[^A-Za-z0-9\@\#+_-]/', '', strtolower($str));
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
    private static function hasFileOrClass(string $id, string $class, string $file): bool
    {
        // by class name
        $ns    = dcCore::app()->plugins->moduleInfo($id, 'namespace');
        $class = $ns . Autoloader::NS_SEP . $class;
        if (!empty($ns) && class_exists($class)) {
            $has = $class::init();
            // by file name
        } else {
            $root = dcCore::app()->plugins->moduleInfo($id, 'root');
            $has  = !empty($root) && file_exists(Path::real($root . DIRECTORY_SEPARATOR . $file));
        }

        return $has;
    }
}

/**
 * @ingroup DC_CORE
 * @brief Helper to manage list of themes.
 * @since 2.6
 */
class adminThemesList extends adminModulesList
{
    /**
     * Constructor.
     *
     * Note that this creates dcStore instance.
     *
     * @param    dcModules    $modules        dcModules instance
     * @param    string       $modules_root   Modules root directories
     * @param    string       $xml_url        URL of modules feed from repository
     * @param    null|bool    $force          Force query repository
     */
    public function __construct(dcModules $modules, string $modules_root, string $xml_url, ?bool $force = false)
    {
        parent::__construct($modules, $modules_root, $xml_url, $force);
        $this->page_url = dcCore::app()->adminurl->get('admin.blog.theme');
    }

    /**
     * Display themes list
     *
     * @param      array  $cols       The cols
     * @param      array  $actions    The actions
     * @param      bool   $nav_limit  The navigation limit
     */
    public function displayModules(array $cols = ['name', 'config', 'version', 'desc'], array $actions = [], bool $nav_limit = false): adminThemesList
    {
        echo
        '<form action="' . $this->getURL() . '" method="post" class="modules-form-actions">' .
        '<div id="' . Html::escapeHTML($this->list_id) . '" class="modules' . (in_array('expander', $cols) ? ' expandable' : '') . ' one-box">';

        $sort_field = $this->getSort();

        # Sort modules by id
        if ($this->getSearch() === null) {
            uasort($this->defines, fn ($a, $b) => $a->get($sort_field) <=> $b->get($sort_field));
        }

        $res   = '';
        $count = 0;
        foreach ($this->defines as $define) {
            $id = $define->getId();

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

            $current = dcCore::app()->blog->settings->system->theme == $id && $this->modules->moduleExists($id);
            $distrib = $define->get('distributed') ? ' dc-box' : '';

            $git = ((defined('DC_DEV') && DC_DEV) || (defined('DC_DEBUG') && DC_DEBUG)) && file_exists($define->get('root') . DIRECTORY_SEPARATOR . '.git');

            $line = '<div class="box ' . ($current ? 'medium current-theme' : 'theme') . $distrib . ($git ? ' module-git' : '') . '">';

            if (in_array('name', $cols) && !$current) {
                $line .= '<h4 class="module-name">';

                if (in_array('checkbox', $cols)) {
                    $line .= '<label for="' . Html::escapeHTML($this->list_id) . '_modules_' . Html::escapeHTML($id) . '">' .
                    form::checkbox(['modules[' . $count . ']', Html::escapeHTML($this->list_id) . '_modules_' . Html::escapeHTML($id)], Html::escapeHTML($id)) .
                    Html::escapeHTML($define->get('name')) .
                        '</label>';
                } else {
                    $line .= form::hidden(['modules[' . $count . ']'], Html::escapeHTML($id)) .
                    Html::escapeHTML($define->get('name'));
                }

                $line .= dcCore::app()->formNonce() .
                '</h4>';
            }

            # Display score only for debug purpose
            if (in_array('score', $cols) && $this->getSearch() !== null && defined('DC_DEBUG') && DC_DEBUG) {
                $line .= '<p class="module-score debug">' . sprintf(__('Score: %s'), $define->get('score')) . '</p>';
            }

            if (in_array('sshot', $cols)) {
                # Screenshot from url
                if (preg_match('#^http(s)?://#', $define->get('sshot'))) {
                    $sshot = $define->get('sshot');
                }
                # Screenshot from installed module
                elseif (file_exists(dcCore::app()->blog->themes_path . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . 'screenshot.jpg')) {
                    $sshot = $this->getURL('shot=' . rawurlencode($id));
                }
                # Default screenshot
                else {
                    $sshot = 'images/noscreenshot.png';
                }

                $line .= '<div class="module-sshot"><img src="' . $sshot . '" loading="lazy" alt="' .
                sprintf(__('%s screenshot.'), Html::escapeHTML($define->get('name'))) . '" /></div>';
            }

            $line .= $current ? '' : '<details><summary>' . __('Details') . '</summary>';
            $line .= '<div class="module-infos">';

            if (in_array('name', $cols) && $current) {
                $line .= '<h4 class="module-name">';

                if (in_array('checkbox', $cols)) {
                    $line .= '<label for="' . Html::escapeHTML($this->list_id) . '_modules_' . Html::escapeHTML($id) . '">' .
                    form::checkbox(['modules[' . $count . ']', Html::escapeHTML($this->list_id) . '_modules_' . Html::escapeHTML($id)], Html::escapeHTML($id)) .
                    Html::escapeHTML($define->get('name')) .
                        '</label>';
                } else {
                    $line .= form::hidden(['modules[' . $count . ']'], Html::escapeHTML($id)) .
                    Html::escapeHTML($define->get('name'));
                }

                $line .= '</h4>';
            }

            $line .= '<p>';

            if (in_array('desc', $cols)) {
                $line .= '<span class="module-desc">' . Html::escapeHTML(__($define->get('desc'))) . '</span> ';
            }

            if (in_array('author', $cols)) {
                $line .= '<span class="module-author">' . sprintf(__('by %s'), Html::escapeHTML($define->get('author'))) . '</span> ';
            }

            if (in_array('version', $cols)) {
                $line .= '<span class="module-version">' . sprintf(__('version %s'), Html::escapeHTML($define->get('version'))) . '</span> ';
            }

            if (in_array('current_version', $cols)) {
                $line .= '<span class="module-current-version">' . sprintf(__('(current version %s)'), Html::escapeHTML($define->get('current_version'))) . '</span> ';
            }

            if (in_array('parent', $cols) && !empty($define->get('parent'))) {
                if ($this->modules->moduleExists($define->get('parent'))) {
                    $line .= '<span class="module-parent-ok">' . sprintf(__('(built on "%s")'), Html::escapeHTML($define->get('parent'))) . '</span> ';
                } else {
                    $line .= '<span class="module-parent-missing">' . sprintf(__('(requires "%s")'), Html::escapeHTML($define->get('parent'))) . '</span> ';
                }
            }

            if (in_array('repository', $cols) && DC_ALLOW_REPOSITORIES) {
                $line .= '<span class="module-repository">' . (!empty($define->get('repository')) ? __('Third-party repository') : __('Official repository')) . '</span> ';
            }

            if ($define->updLocked()) {
                $line .= '<span class="module-locked">' . __('update locked') . '</span> ';
            }

            $has_details = in_array('details', $cols) && !empty($define->get('details'));
            $has_support = in_array('support', $cols) && !empty($define->get('support'));
            if ($has_details || $has_support) {
                $line .= '<span class="mod-more">';

                if ($has_details) {
                    $line .= '<a class="module-details" href="' . $define->get('details') . '">' . __('Details') . '</a>';
                }

                if ($has_support) {
                    $line .= ' - <a class="module-support" href="' . $define->get('support') . '">' . __('Support') . '</a>';
                }

                $line .= '</span>';
            }

            $line .= '</p>' .
                '</div>';
            $line .= '<div class="module-actions">';

            # Plugins actions
            if ($current) {
                # _GET actions
                if (file_exists(Path::real(dcCore::app()->blog->themes_path . DIRECTORY_SEPARATOR . $id) . DIRECTORY_SEPARATOR . 'style.css')) {
                    $theme_url = preg_match('#^http(s)?://#', (string) dcCore::app()->blog->settings->system->themes_url) ?
                    Http::concatURL(dcCore::app()->blog->settings->system->themes_url, '/' . $id) :
                    Http::concatURL(dcCore::app()->blog->url, dcCore::app()->blog->settings->system->themes_url . '/' . $id);
                    $line .= '<p><a href="' . $theme_url . '/style.css">' . __('View stylesheet') . '</a></p>';
                }

                $line .= '<div class="current-actions">';

                // by class name
                $class = $define->get('namespace') . Autoloader::NS_SEP . dcModules::MODULE_CLASS_CONFIG;
                if (!empty($define->get('namespace')) && class_exists($class)) {
                    $config = $class::init();
                    // by file name
                } else {
                    $config = file_exists(Path::real(dcCore::app()->blog->themes_path . DIRECTORY_SEPARATOR . $id) . DIRECTORY_SEPARATOR . dcModules::MODULE_FILE_CONFIG);
                }

                if ($config) {
                    $line .= '<p><a href="' . $this->getURL('module=' . $id . '&amp;conf=1', false) . '" class="button submit">' . __('Configure theme') . '</a></p>';
                }

                # --BEHAVIOR-- adminCurrentThemeDetails -- string, dcModuleDefine
                $line .= dcCore::app()->callBehavior('adminCurrentThemeDetailsV2', $define->getId(), $define);

                $line .= '</div>';
            }

            # _POST actions
            if (!empty($actions)) {
                $line .= '<p class="module-post-actions">' . implode(' ', $this->getActions($define, $actions)) . '</p>';
            }

            $line .= '</div>';
            $line .= $current ? '' : '</details>';

            $line .= '</div>';

            $count++;

            $res = $current ? $line . $res : $res . $line;
        }

        echo
            $res .
            '</div>';

        if (!$count && $this->getSearch() === null) {
            echo
            '<p class="message">' . __('No themes matched your search.') . '</p>';
        } elseif ((in_array('checkbox', $cols) || $count > 1) && !empty($actions) && dcCore::app()->auth->isSuperAdmin()) {
            $buttons = $this->getGlobalActions($actions, in_array('checkbox', $cols));

            if (!empty($buttons)) {
                if (in_array('checkbox', $cols)) {
                    echo
                        '<p class="checkboxes-helpers"></p>';
                }
                echo '<div>' . implode(' ', $buttons) . '</div>';
            }
        }

        echo
            '</form>';

        return $this;
    }

    /**
     * Gets the actions.
     *
     * @param      dcModuleDefine   $define   The module define
     * @param      array            $actions  The actions
     *
     * @return     array  The actions.
     */
    protected function getActions(dcModuleDefine $define, array $actions): array
    {
        $submits = [];
        $id      = $define->getId();

        // mark module state
        if ($define->get('state') != dcModuleDefine::STATE_ENABLED) {
            $submits[] = '<input type="hidden" name="disabled[' . Html::escapeHTML($id) . ']" value="1" />';
        }

        if ($id != dcCore::app()->blog->settings->system->theme) {
            # Select theme to use on curent blog
            if (in_array('select', $actions)) {
                $submits[] = '<input type="submit" name="select[' . Html::escapeHTML($id) . ']" value="' . __('Use this one') . '" />';
            }
        } else {
            // Currently selected theme
            if ($pos = array_search('delete', $actions, true)) {
                // Remove 'delete' action
                unset($actions[$pos]);
            }
            if ($pos = array_search('deactivate', $actions, true)) {
                // Remove 'deactivate' action
                unset($actions[$pos]);
            }
        }

        if ($define->get('distributed') && ($pos = array_search('delete', $actions, true))) {
            // Remove 'delete' action for officially distributed themes
            unset($actions[$pos]);
        }

        return array_merge(
            $submits,
            parent::getActions($define, $actions)
        );
    }

    /**
     * Gets the global actions.
     *
     * @param      array   $actions         The actions
     * @param      bool    $with_selection  The with selection
     *
     * @return     array   The global actions.
     */
    protected function getGlobalActions(array $actions, bool $with_selection = false): array
    {
        $submits = [];

        foreach ($actions as $action) {
            switch ($action) {
                # Update (from store)
                case 'update':

                    if (dcCore::app()->auth->isSuperAdmin() && $this->path_writable) {
                        $submits[] = '<input type="submit" name="update" value="' . (
                            $with_selection ?
                            __('Update selected themes') :
                            __('Update all themes from this list')
                        ) . '" />' . dcCore::app()->formNonce();
                    }

                    break;

                    # Behavior
                case 'behavior':

                    # --BEHAVIOR-- adminModulesListGetGlobalActions -- adminModulesList
                    $tmp = dcCore::app()->callBehavior('adminModulesListGetGlobalActions', $this);

                    if (!empty($tmp)) {
                        $submits[] = $tmp;
                    }

                    break;
            }
        }

        return $submits;
    }

    /**
     * Does actions.
     *
     * @throws     Exception
     */
    public function doActions()
    {
        if (empty($_POST) || !empty($_REQUEST['conf'])) {
            return;
        }

        $modules = !empty($_POST['modules']) && is_array($_POST['modules']) ? array_values($_POST['modules']) : [];

        if (!empty($_POST['select'])) {
            # Can select only one theme at a time!
            if (is_array($_POST['select'])) {
                $modules = array_keys($_POST['select']);
                $define  = $this->modules->getDefine($modules[0]);

                if (!$define->isDefined()) {
                    throw new Exception(__('No such theme.'));
                }

                dcCore::app()->blog->settings->system->put('theme', $define->getId());
                dcCore::app()->blog->triggerBlog();

                dcPage::addSuccessNotice(sprintf(__('Theme %s has been successfully selected.'), Html::escapeHTML($define->get('name'))));
                Http::redirect($this->getURL() . '#themes');
            }
        } else {
            if (!$this->isWritablePath()) {
                return;
            }

            if (dcCore::app()->auth->isSuperAdmin() && !empty($_POST['activate'])) {
                if (is_array($_POST['activate'])) {
                    $modules = array_keys($_POST['activate']);
                }

                $count = 0;
                foreach ($modules as $id) {
                    $define = $this->modules->getDefine($id);
                    if (!$define->isDefined() || $define->get('state') == dcModuleDefine::STATE_ENABLED) {
                        continue;
                    }

                    # --BEHAVIOR-- themeBeforeActivate -- string
                    dcCore::app()->callBehavior('themeBeforeActivate', $define->getId());

                    $this->modules->activateModule($define->getId());

                    # --BEHAVIOR-- themeAfterActivate -- string
                    dcCore::app()->callBehavior('themeAfterActivate', $define->getId());

                    $count++;
                }

                if (!$count) {
                    throw new Exception(__('No such theme.'));
                }

                dcPage::addSuccessNotice(
                    __('Theme has been successfully activated.', 'Themes have been successuflly activated.', $count)
                );
                Http::redirect($this->getURL());
            } elseif (dcCore::app()->auth->isSuperAdmin() && !empty($_POST['deactivate'])) {
                if (is_array($_POST['deactivate'])) {
                    $modules = array_keys($_POST['deactivate']);
                }

                $failed = false;
                $count  = 0;
                foreach ($modules as $id) {
                    $define = $this->modules->getDefine($id);
                    if (!$define->isDefined() || $define->get('state') == dcModuleDefine::STATE_HARD_DISABLED) {
                        continue;
                    }

                    if (!$define->get('root_writable')) {
                        $failed = true;

                        continue;
                    }

                    # --BEHAVIOR-- themeBeforeDeactivate -- dcModuleDefine
                    dcCore::app()->callBehavior('themeBeforeDeactivateV2', $define);

                    $this->modules->deactivateModule($define->getId());

                    # --BEHAVIOR-- themeAfterDeactivate -- dcModuleDefine
                    dcCore::app()->callBehavior('themeAfterDeactivateV2', $define);

                    $count++;
                }

                if (!$count) {
                    throw new Exception(__('No such theme.'));
                }

                if ($failed) {
                    dcPage::addWarningNotice(__('Some themes have not been deactivated.'));
                } else {
                    dcPage::addSuccessNotice(
                        __('Theme has been successfully deactivated.', 'Themes have been successuflly deactivated.', $count)
                    );
                }
                Http::redirect($this->getURL());
            } elseif (dcCore::app()->auth->isSuperAdmin() && !empty($_POST['clone'])) {
                if (is_array($_POST['clone'])) {
                    $modules = array_keys($_POST['clone']);
                }

                $count = 0;
                foreach ($modules as $id) {
                    $define = $this->modules->getDefine($id);
                    if (!$define->isDefined() || $define->get('state') != dcModuleDefine::STATE_ENABLED) {
                        continue;
                    }

                    # --BEHAVIOR-- themeBeforeClone -- string
                    dcCore::app()->callBehavior('themeBeforeClone', $define->getId());

                    $this->modules->cloneModule($define->getId());

                    # --BEHAVIOR-- themeAfterClone -- string
                    dcCore::app()->callBehavior('themeAfterClone', $define->getId());

                    $count++;
                }

                if (!$count) {
                    throw new Exception(__('No such theme.'));
                }

                dcPage::addSuccessNotice(
                    __('Theme has been successfully cloned.', 'Themes have been successuflly cloned.', $count)
                );
                Http::redirect($this->getURL());
            } elseif (dcCore::app()->auth->isSuperAdmin() && !empty($_POST['delete'])) {
                if (is_array($_POST['delete'])) {
                    $modules = array_keys($_POST['delete']);
                }

                $failed = false;
                $count  = 0;
                foreach ($modules as $id) {
                    $disabled = !empty($_POST['disabled'][$id]);
                    ;
                    $define = $this->modules->getDefine($id, ['state' => ($disabled ? '!' : '') . dcModuleDefine::STATE_ENABLED]);
                    if (!$define->isDefined()) {
                        continue;
                    }
                    if (!$this->isDeletablePath($define->get('root'))) {
                        $failed = true;

                        continue;
                    }

                    # --BEHAVIOR-- themeBeforeDelete -- dcModuleDefine
                    dcCore::app()->callBehavior('themeBeforeDeleteV2', $define);

                    $this->modules->deleteModule($define->getId(), $disabled);

                    # --BEHAVIOR-- themeAfterDelete -- dcModuleDefine
                    dcCore::app()->callBehavior('themeAfterDeleteV2', $define);

                    $count++;
                }

                if (!$count && $failed) {
                    throw new Exception(__("You don't have permissions to delete this theme."));
                } elseif (!$count) {
                    throw new Exception(__('No such theme.'));
                } elseif ($failed) {
                    dcPage::addWarningNotice(__('Some themes have not been delete.'));
                } else {
                    dcPage::addSuccessNotice(
                        __('Theme has been successfully deleted.', 'Themes have been successuflly deleted.', $count)
                    );
                }
                Http::redirect($this->getURL());
            } elseif (dcCore::app()->auth->isSuperAdmin() && !empty($_POST['install'])) {
                if (is_array($_POST['install'])) {
                    $modules = array_keys($_POST['install']);
                }

                $count = 0;
                foreach ($this->store->getDefines() as $define) {
                    if (!in_array($define->getId(), $modules)) {
                        continue;
                    }

                    $dest = $this->getPath() . DIRECTORY_SEPARATOR . basename($define->get('file'));

                    # --BEHAVIOR-- themeBeforeAdd -- dcModuleDefine
                    dcCore::app()->callBehavior('themeBeforeAddV2', $define);

                    $this->store->process($define->get('file'), $dest);

                    # --BEHAVIOR-- themeAfterAdd -- dcModuleDefine
                    dcCore::app()->callBehavior('themeAfterAddV2', $define);

                    $count++;
                }

                if (!$count) {
                    throw new Exception(__('No such theme.'));
                }

                dcPage::addSuccessNotice(
                    __('Theme has been successfully installed.', 'Themes have been successfully installed.', $count)
                );
                Http::redirect($this->getURL());
            } elseif (dcCore::app()->auth->isSuperAdmin() && !empty($_POST['update'])) {
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

                    $dest = implode(DIRECTORY_SEPARATOR, [Path::dirWithSym($define->get('root')), '..', basename($define->get('file'))]);

                    # --BEHAVIOR-- themeBeforeUpdate -- dcModuleDefine
                    dcCore::app()->callBehavior('themeBeforeUpdateV2', $define);

                    $this->store->process($define->get('file'), $dest);

                    # --BEHAVIOR-- themeAfterUpdate -- dcModuleDefine
                    dcCore::app()->callBehavior('themeAfterUpdateV2', $define);

                    $count++;
                }

                $tab = $count == count($defines) ? '#themes' : '#update';   // @phpstan-ignore-line

                if ($count) {
                    dcPage::addSuccessNotice(
                        __('Theme has been successfully updated.', 'Themes have been successfully updated.', $count)
                    );
                } elseif (!empty($locked)) {
                    dcPage::addWarningNotice(
                        sprintf(__('Following themes updates are locked: %s'), implode(', ', $locked))
                    );
                } else {
                    throw new Exception(__('No such theme.'));
                }
                Http::redirect($this->getURL() . $tab);
            }

            # Manual actions
            elseif (!empty($_POST['upload_pkg']) && !empty($_FILES['pkg_file'])
                || !empty($_POST['fetch_pkg'])   && !empty($_POST['pkg_url'])) {
                if (empty($_POST['your_pwd']) || !dcCore::app()->auth->checkPassword($_POST['your_pwd'])) {
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

                # --BEHAVIOR-- themeBeforeAdd --
                dcCore::app()->callBehavior('themeBeforeAdd', null);

                $ret_code = $this->store->install($dest);

                # --BEHAVIOR-- themeAfterAdd --
                dcCore::app()->callBehavior('themeAfterAdd', null);

                dcPage::addSuccessNotice(
                    $ret_code == dcModules::PACKAGE_UPDATED ?
                    __('The theme has been successfully updated.') :
                    __('The theme has been successfully installed.')
                );
                Http::redirect($this->getURL() . '#themes');
            } else {
                # --BEHAVIOR-- adminModulesListDoActions -- adminModulesList, array<int,string>, string
                dcCore::app()->callBehavior('adminModulesListDoActions', $this, $modules, 'theme');
            }
        }
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
        if (empty($this->config_class) && !$this->config_file) {
            return;
        }
        $this->setRedir($this->getURL() . '#themes');

        ob_start();

        if (!empty($this->config_class) && $this->config_class::init() && $this->config_class::process()) {
            $this->config_class::render();

            return null;
        }

        return $this->config_file;
    }
}
