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
     * Current modules
     *
     * @var        array
     */
    protected $data = [];

    /**
     * Module to configure
     *
     * @var        array
     */
    protected $config_module = [];
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
     * Page query string
     *
     * @var        string
     */
    protected $page_qs = '?';
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
     * @param    boolean      $force          Force query repository
     */
    public function __construct(dcModules $modules, string $modules_root, string $xml_url, bool $force = false)
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
        $this->data     = [];
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
        $this->page_qs  = strpos('?', $url) ? '&amp;' : '?';
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
            (!empty($queries) ? $this->page_qs : '') .
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

        if (empty($this->data) && $query === null) {
            return $this;
        }

        echo
        '<div class="modules-search">' .
        '<form action="' . $this->getURL() . '" method="get">' .
        '<p><label for="m_search" class="classic">' . __('Search in repository:') . '&nbsp;</label><br />' .
        form::field('m_search', 30, 255, html::escapeHTML($query)) .
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
                __('Found %d result for search "%s":', 'Found %d results for search "%s":', count($this->data)),
                count($this->data),
                html::escapeHTML($query)
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
        return isset($_REQUEST['m_nav']) && in_array($_REQUEST['m_nav'], $this->nav_list) ? $_REQUEST['m_nav'] : $this->nav_list[0];
    }

    /**
     * Display navigation by index menu.
     *
     * @return    adminModulesList self instance
     */
    public function displayIndex(): adminModulesList
    {
        if (empty($this->data) || $this->getSearch() !== null) {
            return $this;
        }

        # Fetch modules required field
        $indexes = [];
        foreach ($this->data as $module) {
            if (!isset($module[$this->sort_field])) {
                continue;
            }
            $char = substr($module[$this->sort_field], 0, 1);
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
        return !empty($_REQUEST['m_sort']) ? $_REQUEST['m_sort'] : $this->sort_field;
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
     * Set modules and sanitize them.
     *
     * @param   array   $modules
     *
     * @return    adminModulesList self instance
     */
    public function setModules(array $modules): adminModulesList
    {
        $this->data = [];
        if (!empty($modules)) {
            foreach ($modules as $id => $module) {
                $this->data[$id] = $this->doSanitizeModule($id, $module);
            }
        }

        return $this;
    }

    /**
     * Get modules currently set.
     *
     * @return    array        Array of modules
     */
    public function getModules(): array
    {
        return $this->data;
    }

    /**
     * Sanitize a module.
     *
     * This clean infos of a module by adding default keys
     * and clean some of them, sanitize module can safely
     * be used in lists.
     *
     * @param      dcModuleDefine   $define The module definition
     * @param      string           $id      The identifier
     * @param      array            $module  The module
     *
     * @return   array  Array of the module informations
     */
    private static function fillSanitizeModule(dcModuleDefine $define, string $id, array $module): array
    {
        if (is_array($module)) {
            foreach ($module as $k => $v) {
                $define->set($k, $v);
            }
        }

        return $define
            ->set('sid', self::sanitizeString($id))
            ->set('label', empty($module['label']) ? $id : $module['label'])
            ->set('name', __(empty($module['name']) ? $define->label : $module['name']))
            ->set('sname', self::sanitizeString($define->name))
            ->dump();
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
     * @param      string  $id      The identifier
     * @param      array   $module  The module
     *
     * @return   array  Array of the module informations
     */
    public static function sanitizeModule(string $id, array $module): array
    {
        $define = new dcModuleDefine($id);

        return self::fillSanitizeModule($define, $id, $module);
    }

    /**
     * Sanitize a module (dynamic version).
     *
     * This clean infos of a module by adding default keys
     * and clean some of them, sanitize module can safely
     * be used in lists.
     *
     * @param      string  $id      The identifier
     * @param      array   $module  The module
     *
     * @return   array  Array of the module informations
     */
    public function doSanitizeModule(string $id, array $module): array
    {
        $define = $this->modules->getDefine($id);

        return self::fillSanitizeModule($define, $id, $module);
    }

    /**
     * Check if a module is part of the distribution.
     *
     * @param    string    $id        Module root directory
     *
     * @return   bool  True if module is part of the distribution
     */
    public static function isDistributedModule(string $id): bool
    {
        return in_array($id, self::$distributed_modules);
    }

    /**
     * Sort modules list by specific field.
     *
     * @param    array     $modules      Array of modules
     * @param    string    $field        Field to sort from
     * @param    bool      $asc          Sort asc if true, else decs
     *
     * @return   array  Array of sorted modules
     */
    public static function sortModules(array $modules, string $field, bool $asc = true): array
    {
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
        '<table id="' . html::escapeHTML($this->list_id) . '" class="modules' . (in_array('expander', $cols) ? ' expandable' : '') . '">' .
        '<caption class="hidden">' . html::escapeHTML(__('Plugins list')) . '</caption><tr>';

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
        $modules = $this->getSearch() === null ?
        self::sortModules($this->data, $sort_field, $this->sort_asc) :
        $this->data;

        $count = 0;
        foreach ($modules as $id => $module) {
            # Show only requested modules
            if ($nav_limit && $this->getSearch() === null) {
                $char = substr($module[$sort_field], 0, 1);
                if (!in_array($char, $this->nav_list)) {
                    $char = $this->nav_special;
                }
                if ($this->getIndex() != $char) {
                    continue;
                }
            }
            $git = ((defined('DC_DEV') && DC_DEV) || (defined('DC_DEBUG') && DC_DEBUG)) && file_exists($module['root'] . '/.git');

            echo
            '<tr class="line' . ($git ? ' module-git' : '') . '" id="' . html::escapeHTML($this->list_id) . '_m_' . html::escapeHTML($id) . '"' .
                (in_array('desc', $cols) ? ' title="' . html::escapeHTML(__($module['desc'])) . '" ' : '') .
                '>';

            $tds = 0;

            if (in_array('checkbox', $cols)) {
                $tds++;
                echo
                '<td class="module-icon nowrap">' .
                form::checkbox(['modules[' . $count . ']', html::escapeHTML($this->list_id) . '_modules_' . html::escapeHTML($id)], html::escapeHTML($id)) .
                    '</td>';
            }

            if (in_array('icon', $cols)) {
                $tds++;
                $default_icon = false;

                if (file_exists($module['root'] . '/icon.svg')) {
                    $icon = dcPage::getPF($id . '/icon.svg');
                } elseif (file_exists($module['root'] . '/icon.png')) {
                    $icon = dcPage::getPF($id . '/icon.png');
                } else {
                    $icon         = 'images/module.svg';
                    $default_icon = true;
                }
                if (file_exists($module['root'] . '/icon-dark.svg')) {
                    $icon = [$icon, dcPage::getPF($id . '/icon-dark.svg')];
                } elseif (file_exists($module['root'] . '/icon-dark.png')) {
                    $icon = [$icon, dcPage::getPF($id . '/icon-dark.png')];
                } elseif ($default_icon) {
                    $icon = [$icon, 'images/module-dark.svg'];
                }

                echo
                '<td class="module-icon nowrap">' .
                dcAdminHelper::adminIcon($icon, false, html::escapeHTML($id), html::escapeHTML($id)) .
                '</td>';
            }

            $tds++;
            echo
            '<th class="module-name nowrap" scope="row">';
            if (in_array('checkbox', $cols)) {
                if (in_array('expander', $cols)) {
                    echo
                    html::escapeHTML($module['name']) . ($id != $module['name'] ? sprintf(__(' (%s)'), $id) : '');
                } else {
                    echo
                    '<label for="' . html::escapeHTML($this->list_id) . '_modules_' . html::escapeHTML($id) . '">' .
                    html::escapeHTML($module['name']) . ($id != $module['name'] ? sprintf(__(' (%s)'), $id) : '') .
                    '</label>';
                }
            } else {
                echo
                html::escapeHTML($module['name']) . ($id != $module['name'] ? sprintf(__(' (%s)'), $id) : '') .
                form::hidden(['modules[' . $count . ']'], html::escapeHTML($id));
            }
            echo
            dcCore::app()->formNonce() .
            '</td>';

            # Display score only for debug purpose
            if (in_array('score', $cols) && $this->getSearch() !== null && defined('DC_DEBUG') && DC_DEBUG) {
                $tds++;
                echo
                '<td class="module-version nowrap count"><span class="debug">' . $module['score'] . '</span></td>';
            }

            if (in_array('version', $cols)) {
                $tds++;
                echo
                '<td class="module-version nowrap count">' . html::escapeHTML($module['version']) . '</td>';
            }

            if (in_array('current_version', $cols)) {
                $tds++;
                echo
                '<td class="module-current-version nowrap count">' . html::escapeHTML($module['current_version']) . '</td>';
            }

            if (in_array('desc', $cols)) {
                $tds++;
                echo
                '<td class="module-desc maximal">' . html::escapeHTML(__($module['desc']));
                if (!empty($module['cannot_disable']) && $module['state'] == dcModuleDefine::STATE_ENABLED) {
                    echo
                    '<br/><span class="info">' .
                    sprintf(
                        __('This module cannot be disabled nor deleted, since the following modules are also enabled : %s'),
                        join(',', $module['cannot_disable'])
                    ) .
                        '</span>';
                }
                if (!empty($module['cannot_enable']) && $module['state'] != dcModuleDefine::STATE_ENABLED) {
                    echo
                    '<br/><span class="info">' .
                    __('This module cannot be enabled, because of the following reasons :') .
                        '<ul>';
                    foreach ($module['cannot_enable'] as $reason) {
                        echo '<li>' . $reason . '</li>';
                    }
                    echo '</ul>' .
                        '</span>';
                }
                echo '</td>';
            }

            if (in_array('repository', $cols) && DC_ALLOW_REPOSITORIES) {
                $tds++;
                echo
                '<td class="module-repository nowrap count">' . (!empty($module['repository']) ? __('Third-party repository') : __('Official repository')) . '</td>';
            }

            if (in_array('distrib', $cols)) {
                $tds++;
                echo
                    '<td class="module-distrib">' . (self::isDistributedModule($id) ?
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
                $buttons = $this->getActions($id, $module, $actions);

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

                if (!empty($module['author']) || !empty($module['details']) || !empty($module['support'])) {
                    echo
                        '<div><ul class="mod-more">';

                    if (!empty($module['author'])) {
                        echo
                        '<li class="module-author">' . __('Author:') . ' ' . html::escapeHTML($module['author']) . '</li>';
                    }

                    $more = [];
                    if (!empty($module['details'])) {
                        $more[] = '<a class="module-details" href="' . $module['details'] . '">' . __('Details') . '</a>';
                    }

                    if (!empty($module['support'])) {
                        $more[] = '<a class="module-support" href="' . $module['support'] . '">' . __('Support') . '</a>';
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
                 || !empty($module['section'])
                 || !empty($module['tags'])
                 || !empty($module['settings'])   && $module['state'] == dcModuleDefine::STATE_ENABLED
                 || !empty($module['repository']) && DC_DEBUG && DC_ALLOW_REPOSITORIES
                ) {
                    echo
                        '<div><ul class="mod-more">';

                    $settings = static::getSettingsUrls($id);
                    if (!empty($settings) && $module['state'] == dcModuleDefine::STATE_ENABLED) {
                        echo '<li>' . implode(' - ', $settings) . '</li>';
                    }

                    if (!empty($module['repository']) && DC_DEBUG && DC_ALLOW_REPOSITORIES) {
                        echo '<li class="modules-repository"><a href="' . $module['repository'] . '">' . __('Third-party repository') . '</a></li>';
                    }

                    if (!empty($module['section'])) {
                        echo
                        '<li class="module-section">' . __('Section:') . ' ' . html::escapeHTML($module['section']) . '</li>';
                    }

                    if (!empty($module['tags'])) {
                        echo
                        '<li class="module-tags">' . __('Tags:') . ' ' . html::escapeHTML($module['tags']) . '</li>';
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
     * @param    string    $id            Module ID
     * @param    array    $module        Module info
     * @param    array    $actions    Actions keys
     *
     * @return   array    Array of actions buttons
     */
    protected function getActions(string $id, array $module, array $actions): array
    {
        $submits = [];

        # Use loop to keep requested order
        foreach ($actions as $action) {
            switch ($action) {
                # Deactivate
                case 'activate':
                    if (dcCore::app()->auth->isSuperAdmin() && $module['root_writable'] && empty($module['cannot_enable'])) {
                        $submits[] = '<input type="submit" name="activate[' . html::escapeHTML($id) . ']" value="' . __('Activate') . '" />';
                    }

                    break;

                    # Activate
                case 'deactivate':
                    if (dcCore::app()->auth->isSuperAdmin() && $module['root_writable'] && empty($module['cannot_disable'])) {
                        $submits[] = '<input type="submit" name="deactivate[' . html::escapeHTML($id) . ']" value="' . __('Deactivate') . '" class="reset" />';
                    }

                    break;

                    # Delete
                case 'delete':
                    if (dcCore::app()->auth->isSuperAdmin() && $this->isDeletablePath($module['root']) && empty($module['cannot_disable'])) {
                        $dev       = !preg_match('!^' . $this->path_pattern . '!', $module['root']) && defined('DC_DEV') && DC_DEV ? ' debug' : '';
                        $submits[] = '<input type="submit" class="delete ' . $dev . '" name="delete[' . html::escapeHTML($id) . ']" value="' . __('Delete') . '" />';
                    }

                    break;

                    # Clone
                case 'clone':
                    if (dcCore::app()->auth->isSuperAdmin() && $this->path_writable) {
                        $submits[] = '<input type="submit" class="button clone" name="clone[' . html::escapeHTML($id) . ']" value="' . __('Clone') . '" />';
                    }

                    break;

                    # Install (from store)
                case 'install':
                    if (dcCore::app()->auth->isSuperAdmin() && $this->path_writable) {
                        $submits[] = '<input type="submit" name="install[' . html::escapeHTML($id) . ']" value="' . __('Install') . '" />';
                    }

                    break;

                    # Update (from store)
                case 'update':
                    if (dcCore::app()->auth->isSuperAdmin() && $this->path_writable) {
                        $submits[] = '<input type="submit" name="update[' . html::escapeHTML($id) . ']" value="' . __('Update') . '" />';
                    }

                    break;

                    # Behavior
                case 'behavior':

                    # --BEHAVIOR-- adminModulesListGetActions
                    $tmp = dcCore::app()->callBehavior('adminModulesListGetActions', $this, $id, $module);

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

                    # --BEHAVIOR-- adminModulesListGetGlobalActions
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

            $list = $this->modules->getDisabledModules();

            $failed = false;
            $count  = 0;
            foreach ($modules as $id) {
                if (!isset($list[$id])) {
                    if (!$this->modules->moduleExists($id)) {
                        throw new Exception(__('No such plugin.'));
                    }

                    $module       = $this->modules->getModules($id);
                    $module['id'] = $id;

                    if (!$this->isDeletablePath($module['root'])) {
                        $failed = true;

                        continue;
                    }

                    # --BEHAVIOR-- moduleBeforeDelete
                    dcCore::app()->callBehavior('pluginBeforeDelete', $module);

                    $this->modules->deleteModule($id);

                    # --BEHAVIOR-- moduleAfterDelete
                    dcCore::app()->callBehavior('pluginAfterDelete', $module);
                } else {
                    $this->modules->deleteModule($id, true);
                }

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
            http::redirect($this->getURL());
        } elseif (dcCore::app()->auth->isSuperAdmin() && !empty($_POST['install'])) {
            if (is_array($_POST['install'])) {
                $modules = array_keys($_POST['install']);
            }

            $list = $this->store->get();

            if (empty($list)) {
                throw new Exception(__('No such plugin.'));
            }

            $count = 0;
            foreach ($list as $id => $module) {
                if (!in_array($id, $modules)) {
                    continue;
                }

                $dest = $this->getPath() . '/' . basename($module['file']);

                # --BEHAVIOR-- moduleBeforeAdd
                dcCore::app()->callBehavior('pluginBeforeAdd', $module);

                $this->store->process($module['file'], $dest);

                # --BEHAVIOR-- moduleAfterAdd
                dcCore::app()->callBehavior('pluginAfterAdd', $module);

                $count++;
            }

            dcPage::addSuccessNotice(
                __('Plugin has been successfully installed.', 'Plugins have been successfully installed.', $count)
            );
            http::redirect($this->getURL());
        } elseif (dcCore::app()->auth->isSuperAdmin() && !empty($_POST['activate'])) {
            if (is_array($_POST['activate'])) {
                $modules = array_keys($_POST['activate']);
            }

            $list = $this->modules->getDisabledModules();
            if (empty($list)) {
                throw new Exception(__('No such plugin.'));
            }

            $count = 0;
            foreach ($list as $id => $module) {
                if (!in_array($id, $modules)) {
                    continue;
                }

                # --BEHAVIOR-- moduleBeforeActivate
                dcCore::app()->callBehavior('pluginBeforeActivate', $id);

                $this->modules->activateModule($id);

                # --BEHAVIOR-- moduleAfterActivate
                dcCore::app()->callBehavior('pluginAfterActivate', $id);

                $count++;
            }

            dcPage::addSuccessNotice(
                __('Plugin has been successfully activated.', 'Plugins have been successuflly activated.', $count)
            );
            http::redirect($this->getURL());
        } elseif (dcCore::app()->auth->isSuperAdmin() && !empty($_POST['deactivate'])) {
            if (is_array($_POST['deactivate'])) {
                $modules = array_keys($_POST['deactivate']);
            }

            $list = $this->modules->getModules();
            if (empty($list)) {
                throw new Exception(__('No such plugin.'));
            }

            $failed = false;
            $count  = 0;
            foreach ($list as $id => $module) {
                if (!in_array($id, $modules)) {
                    continue;
                }

                if (!$module['root_writable']) {
                    $failed = true;

                    continue;
                }

                $module[$id] = $id;

                # --BEHAVIOR-- moduleBeforeDeactivate
                dcCore::app()->callBehavior('pluginBeforeDeactivate', $module);

                $this->modules->deactivateModule($id);

                # --BEHAVIOR-- moduleAfterDeactivate
                dcCore::app()->callBehavior('pluginAfterDeactivate', $module);

                $count++;
            }

            if ($failed) {
                dcPage::addWarningNotice(__('Some plugins have not been deactivated.'));
            } else {
                dcPage::addSuccessNotice(
                    __('Plugin has been successfully deactivated.', 'Plugins have been successuflly deactivated.', $count)
                );
            }
            http::redirect($this->getURL());
        } elseif (dcCore::app()->auth->isSuperAdmin() && !empty($_POST['update'])) {
            if (is_array($_POST['update'])) {
                $modules = array_keys($_POST['update']);
            }

            $list = $this->store->get(true);
            if (empty($list)) {
                throw new Exception(__('No such plugin.'));
            }

            $count = 0;
            foreach ($list as $module) {
                if (!in_array($module['id'], $modules)) {
                    continue;
                }

                if (!self::$allow_multi_install) {
                    $dest = $module['root'] . '/../' . basename($module['file']);
                } else {
                    $dest = $this->getPath() . '/' . basename($module['file']);
                    if ($module['root'] != $dest) {
                        @file_put_contents($module['root'] . DIRECTORY_SEPARATOR . dcModules::MODULE_FILE_DISABLED, '');
                    }
                }

                # --BEHAVIOR-- moduleBeforeUpdate
                dcCore::app()->callBehavior('pluginBeforeUpdate', $module);

                $this->store->process($module['file'], $dest);

                # --BEHAVIOR-- moduleAfterUpdate
                dcCore::app()->callBehavior('pluginAfterUpdate', $module);

                $count++;
            }

            $tab = $count && $count == (is_countable($list) ? count($list) : 0) ? '#plugins' : '#update';   // @phpstan-ignore-line

            dcPage::addSuccessNotice(
                __('Plugin has been successfully updated.', 'Plugins have been successfully updated.', $count)
            );
            http::redirect($this->getURL() . $tab);
        }

        # Manual actions
        elseif (!empty($_POST['upload_pkg']) && !empty($_FILES['pkg_file'])
            || !empty($_POST['fetch_pkg'])   && !empty($_POST['pkg_url'])) {
            if (empty($_POST['your_pwd']) || !dcCore::app()->auth->checkPassword($_POST['your_pwd'])) {
                throw new Exception(__('Password verification failed'));
            }

            if (!empty($_POST['upload_pkg'])) {
                files::uploadStatus($_FILES['pkg_file']);

                $dest = $this->getPath() . '/' . $_FILES['pkg_file']['name'];
                if (!move_uploaded_file($_FILES['pkg_file']['tmp_name'], $dest)) {
                    throw new Exception(__('Unable to move uploaded file.'));
                }
            } else {
                $url  = urldecode($_POST['pkg_url']);
                $dest = $this->getPath() . '/' . basename($url);
                $this->store->download($url, $dest);
            }

            # --BEHAVIOR-- moduleBeforeAdd
            dcCore::app()->callBehavior('pluginBeforeAdd', null);

            $ret_code = $this->store->install($dest);

            # --BEHAVIOR-- moduleAfterAdd
            dcCore::app()->callBehavior('pluginAfterAdd', null);

            dcPage::addSuccessNotice(
                $ret_code === dcModules::PACKAGE_UPDATED ?
                __('The plugin has been successfully updated.') :
                __('The plugin has been successfully installed.')
            );
            http::redirect($this->getURL() . '#plugins');
        } else {
            # --BEHAVIOR-- adminModulesListDoActions
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

        if (!$this->modules->moduleExists($id)) {
            dcCore::app()->error->add(__('Unknown plugin ID'));

            return false;
        }

        $module = $this->modules->getModules($id);
        $module = $this->doSanitizeModule($id, $module);
        $class  = $module['namespace'] . Autoloader::NS_SEP . dcModules::MODULE_CLASS_CONFIG;
        $class  = empty($module['namespace']) || !class_exists($class) ? '' : $class;
        $file   = path::real($module['root'] . DIRECTORY_SEPARATOR . dcModules::MODULE_FILE_CONFIG);

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

        $this->config_module  = $module;
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
        if (!empty($this->config_class) || !empty($this->config_file)) {
            if (!$this->config_module['standalone_config']) {
                echo
                '<form id="module_config" action="' . $this->getURL('conf=1') . '" method="post" enctype="multipart/form-data">' .
                '<h3>' . sprintf(__('Configure "%s"'), html::escapeHTML($this->config_module['name'])) . '</h3>' .
                '<p><a class="back" href="' . $this->getRedir() . '">' . __('Back') . '</a></p>';
            }

            echo $this->config_content;

            if (!$this->config_module['standalone_config']) {
                echo
                '<p class="clear"><input type="submit" name="save" value="' . __('Save') . '" />' .
                form::hidden('module', $this->config_module['id']) .
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
            $root = dcCore::app()->plugins->moduleRoot($id);
            $has  = !empty($root) && file_exists(path::real($root . DIRECTORY_SEPARATOR . $file));
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
     * @param    boolean      $force          Force query repository
     */
    public function __construct(dcModules $modules, string $modules_root, string $xml_url, bool $force = false)
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
        '<div id="' . html::escapeHTML($this->list_id) . '" class="modules' . (in_array('expander', $cols) ? ' expandable' : '') . ' one-box">';

        $sort_field = $this->getSort();

        # Sort modules by id
        $modules = $this->getSearch() === null ?
        self::sortModules($this->data, $sort_field, $this->sort_asc) :
        $this->data;

        $res   = '';
        $count = 0;
        foreach ($modules as $id => $module) {
            # Show only requested modules
            if ($nav_limit && $this->getSearch() === null) {
                $char = substr($module[$sort_field], 0, 1);
                if (!in_array($char, $this->nav_list)) {
                    $char = $this->nav_special;
                }
                if ($this->getIndex() != $char) {
                    continue;
                }
            }

            $current = dcCore::app()->blog->settings->system->theme == $id && $this->modules->moduleExists($id);
            $distrib = self::isDistributedModule($id) ? ' dc-box' : '';

            $git = ((defined('DC_DEV') && DC_DEV) || (defined('DC_DEBUG') && DC_DEBUG)) && file_exists($module['root'] . '/.git');

            $line = '<div class="box ' . ($current ? 'medium current-theme' : 'theme') . $distrib . ($git ? ' module-git' : '') . '">';

            if (in_array('name', $cols) && !$current) {
                $line .= '<h4 class="module-name">';

                if (in_array('checkbox', $cols)) {
                    $line .= '<label for="' . html::escapeHTML($this->list_id) . '_modules_' . html::escapeHTML($id) . '">' .
                    form::checkbox(['modules[' . $count . ']', html::escapeHTML($this->list_id) . '_modules_' . html::escapeHTML($id)], html::escapeHTML($id)) .
                    html::escapeHTML($module['name']) .
                        '</label>';
                } else {
                    $line .= form::hidden(['modules[' . $count . ']'], html::escapeHTML($id)) .
                    html::escapeHTML($module['name']);
                }

                $line .= dcCore::app()->formNonce() .
                '</h4>';
            }

            # Display score only for debug purpose
            if (in_array('score', $cols) && $this->getSearch() !== null && defined('DC_DEBUG') && DC_DEBUG) {
                $line .= '<p class="module-score debug">' . sprintf(__('Score: %s'), $module['score']) . '</p>';
            }

            if (in_array('sshot', $cols)) {
                # Screenshot from url
                if (preg_match('#^http(s)?://#', $module['sshot'])) {
                    $sshot = $module['sshot'];
                }
                # Screenshot from installed module
                elseif (file_exists(dcCore::app()->blog->themes_path . '/' . $id . '/screenshot.jpg')) {
                    $sshot = $this->getURL('shot=' . rawurlencode($id));
                }
                # Default screenshot
                else {
                    $sshot = 'images/noscreenshot.png';
                }

                $line .= '<div class="module-sshot"><img src="' . $sshot . '" loading="lazy" alt="' .
                sprintf(__('%s screenshot.'), html::escapeHTML($module['name'])) . '" /></div>';
            }

            $line .= $current ? '' : '<details><summary>' . __('Details') . '</summary>';
            $line .= '<div class="module-infos">';

            if (in_array('name', $cols) && $current) {
                $line .= '<h4 class="module-name">';

                if (in_array('checkbox', $cols)) {
                    $line .= '<label for="' . html::escapeHTML($this->list_id) . '_modules_' . html::escapeHTML($id) . '">' .
                    form::checkbox(['modules[' . $count . ']', html::escapeHTML($this->list_id) . '_modules_' . html::escapeHTML($id)], html::escapeHTML($id)) .
                    html::escapeHTML($module['name']) .
                        '</label>';
                } else {
                    $line .= form::hidden(['modules[' . $count . ']'], html::escapeHTML($id)) .
                    html::escapeHTML($module['name']);
                }

                $line .= '</h4>';
            }

            $line .= '<p>';

            if (in_array('desc', $cols)) {
                $line .= '<span class="module-desc">' . html::escapeHTML(__($module['desc'])) . '</span> ';
            }

            if (in_array('author', $cols)) {
                $line .= '<span class="module-author">' . sprintf(__('by %s'), html::escapeHTML($module['author'])) . '</span> ';
            }

            if (in_array('version', $cols)) {
                $line .= '<span class="module-version">' . sprintf(__('version %s'), html::escapeHTML($module['version'])) . '</span> ';
            }

            if (in_array('current_version', $cols)) {
                $line .= '<span class="module-current-version">' . sprintf(__('(current version %s)'), html::escapeHTML($module['current_version'])) . '</span> ';
            }

            if (in_array('parent', $cols) && !empty($module['parent'])) {
                if ($this->modules->moduleExists($module['parent'])) {
                    $line .= '<span class="module-parent-ok">' . sprintf(__('(built on "%s")'), html::escapeHTML($module['parent'])) . '</span> ';
                } else {
                    $line .= '<span class="module-parent-missing">' . sprintf(__('(requires "%s")'), html::escapeHTML($module['parent'])) . '</span> ';
                }
            }

            if (in_array('repository', $cols) && DC_ALLOW_REPOSITORIES) {
                $line .= '<span class="module-repository">' . (!empty($module['repository']) ? __('Third-party repository') : __('Official repository')) . '</span> ';
            }

            $has_details = in_array('details', $cols) && !empty($module['details']);
            $has_support = in_array('support', $cols) && !empty($module['support']);
            if ($has_details || $has_support) {
                $line .= '<span class="mod-more">';

                if ($has_details) {
                    $line .= '<a class="module-details" href="' . $module['details'] . '">' . __('Details') . '</a>';
                }

                if ($has_support) {
                    $line .= ' - <a class="module-support" href="' . $module['support'] . '">' . __('Support') . '</a>';
                }

                $line .= '</span>';
            }

            $line .= '</p>' .
                '</div>';
            $line .= '<div class="module-actions">';

            # Plugins actions
            if ($current) {
                # _GET actions
                if (file_exists(path::real(dcCore::app()->blog->themes_path . '/' . $id) . '/style.css')) {
                    $theme_url = preg_match('#^http(s)?://#', (string) dcCore::app()->blog->settings->system->themes_url) ?
                    http::concatURL(dcCore::app()->blog->settings->system->themes_url, '/' . $id) :
                    http::concatURL(dcCore::app()->blog->url, dcCore::app()->blog->settings->system->themes_url . '/' . $id);
                    $line .= '<p><a href="' . $theme_url . '/style.css">' . __('View stylesheet') . '</a></p>';
                }

                $line .= '<div class="current-actions">';

                // by class name
                $class = $module['namespace'] . Autoloader::NS_SEP . dcModules::MODULE_CLASS_CONFIG;
                if (!empty($module['namespace']) && class_exists($class)) {
                    $config = $class::init();
                // by file name
                } else {
                    $config = file_exists(path::real(dcCore::app()->blog->themes_path . '/' . $id) . DIRECTORY_SEPARATOR . dcModules::MODULE_FILE_CONFIG);
                }

                if ($config) {
                    $line .= '<p><a href="' . $this->getURL('module=' . $id . '&amp;conf=1', false) . '" class="button submit">' . __('Configure theme') . '</a></p>';
                }

                # --BEHAVIOR-- adminCurrentThemeDetails
                $line .= dcCore::app()->callBehavior('adminCurrentThemeDetailsV2', $id, $module);

                $line .= '</div>';
            }

            # _POST actions
            if (!empty($actions)) {
                $line .= '<p class="module-post-actions">' . implode(' ', $this->getActions($id, $module, $actions)) . '</p>';
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
     * @param      string  $id       The identifier
     * @param      array   $module   The module
     * @param      array   $actions  The actions
     *
     * @return     array  The actions.
     */
    protected function getActions(string $id, array $module, array $actions): array
    {
        $submits = [];

        if ($id != dcCore::app()->blog->settings->system->theme) {
            # Select theme to use on curent blog
            if (in_array('select', $actions)) {
                $submits[] = '<input type="submit" name="select[' . html::escapeHTML($id) . ']" value="' . __('Use this one') . '" />';
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

        if (self::isDistributedModule($id) && ($pos = array_search('delete', $actions, true))) {
            // Remove 'delete' action for officially distributed themes
            unset($actions[$pos]);
        }

        return array_merge(
            $submits,
            parent::getActions($id, $module, $actions)
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

                    # --BEHAVIOR-- adminModulesListGetGlobalActions
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
                $id      = $modules[0];

                if (!$this->modules->moduleExists($id)) {
                    throw new Exception(__('No such theme.'));
                }

                dcCore::app()->blog->settings->system->put('theme', $id);
                dcCore::app()->blog->triggerBlog();

                $module = $this->modules->getModules($id);
                dcPage::addSuccessNotice(sprintf(__('Theme %s has been successfully selected.'), html::escapeHTML($module['name'])));
                http::redirect($this->getURL() . '#themes');
            }
        } else {
            if (!$this->isWritablePath()) {
                return;
            }

            if (dcCore::app()->auth->isSuperAdmin() && !empty($_POST['activate'])) {
                if (is_array($_POST['activate'])) {
                    $modules = array_keys($_POST['activate']);
                }

                $list = $this->modules->getDisabledModules();
                if (empty($list)) {
                    throw new Exception(__('No such theme.'));
                }

                $count = 0;
                foreach ($list as $id => $module) {
                    if (!in_array($id, $modules)) {
                        continue;
                    }

                    # --BEHAVIOR-- themeBeforeActivate
                    dcCore::app()->callBehavior('themeBeforeActivate', $id);

                    $this->modules->activateModule($id);

                    # --BEHAVIOR-- themeAfterActivate
                    dcCore::app()->callBehavior('themeAfterActivate', $id);

                    $count++;
                }

                dcPage::addSuccessNotice(
                    __('Theme has been successfully activated.', 'Themes have been successuflly activated.', $count)
                );
                http::redirect($this->getURL());
            } elseif (dcCore::app()->auth->isSuperAdmin() && !empty($_POST['deactivate'])) {
                if (is_array($_POST['deactivate'])) {
                    $modules = array_keys($_POST['deactivate']);
                }

                $list = $this->modules->getModules();
                if (empty($list)) {
                    throw new Exception(__('No such theme.'));
                }

                $failed = false;
                $count  = 0;
                foreach ($list as $id => $module) {
                    if (!in_array($id, $modules)) {
                        continue;
                    }

                    if (!$module['root_writable']) {
                        $failed = true;

                        continue;
                    }

                    $module[$id] = $id;

                    # --BEHAVIOR-- themeBeforeDeactivate
                    dcCore::app()->callBehavior('themeBeforeDeactivate', $module);

                    $this->modules->deactivateModule($id);

                    # --BEHAVIOR-- themeAfterDeactivate
                    dcCore::app()->callBehavior('themeAfterDeactivate', $module);

                    $count++;
                }

                if ($failed) {
                    dcPage::addWarningNotice(__('Some themes have not been deactivated.'));
                } else {
                    dcPage::addSuccessNotice(
                        __('Theme has been successfully deactivated.', 'Themes have been successuflly deactivated.', $count)
                    );
                }
                http::redirect($this->getURL());
            } elseif (dcCore::app()->auth->isSuperAdmin() && !empty($_POST['clone'])) {
                if (is_array($_POST['clone'])) {
                    $modules = array_keys($_POST['clone']);
                }

                $count = 0;
                foreach ($modules as $id) {
                    if (!$this->modules->moduleExists($id)) {
                        throw new Exception(__('No such theme.'));
                    }

                    # --BEHAVIOR-- themeBeforeClone
                    dcCore::app()->callBehavior('themeBeforeClone', $id);

                    $this->modules->cloneModule($id);

                    # --BEHAVIOR-- themeAfterClone
                    dcCore::app()->callBehavior('themeAfterClone', $id);

                    $count++;
                }

                dcPage::addSuccessNotice(
                    __('Theme has been successfully cloned.', 'Themes have been successuflly cloned.', $count)
                );
                http::redirect($this->getURL());
            } elseif (dcCore::app()->auth->isSuperAdmin() && !empty($_POST['delete'])) {
                if (is_array($_POST['delete'])) {
                    $modules = array_keys($_POST['delete']);
                }

                $list = $this->modules->getDisabledModules();

                $failed = false;
                $count  = 0;
                foreach ($modules as $id) {
                    if (!isset($list[$id])) {
                        if (!$this->modules->moduleExists($id)) {
                            throw new Exception(__('No such theme.'));
                        }

                        $module       = $this->modules->getModules($id);
                        $module['id'] = $id;

                        if (!$this->isDeletablePath($module['root'])) {
                            $failed = true;

                            continue;
                        }

                        # --BEHAVIOR-- themeBeforeDelete
                        dcCore::app()->callBehavior('themeBeforeDelete', $module);

                        $this->modules->deleteModule($id);

                        # --BEHAVIOR-- themeAfterDelete
                        dcCore::app()->callBehavior('themeAfterDelete', $module);
                    } else {
                        $this->modules->deleteModule($id, true);
                    }

                    $count++;
                }

                if (!$count && $failed) {
                    throw new Exception(__("You don't have permissions to delete this theme."));
                } elseif ($failed) {
                    dcPage::addWarningNotice(__('Some themes have not been delete.'));
                } else {
                    dcPage::addSuccessNotice(
                        __('Theme has been successfully deleted.', 'Themes have been successuflly deleted.', $count)
                    );
                }
                http::redirect($this->getURL());
            } elseif (dcCore::app()->auth->isSuperAdmin() && !empty($_POST['install'])) {
                if (is_array($_POST['install'])) {
                    $modules = array_keys($_POST['install']);
                }

                $list = $this->store->get();

                if (empty($list)) {
                    throw new Exception(__('No such theme.'));
                }

                $count = 0;
                foreach ($list as $id => $module) {
                    if (!in_array($id, $modules)) {
                        continue;
                    }

                    $dest = $this->getPath() . '/' . basename($module['file']);

                    # --BEHAVIOR-- themeBeforeAdd
                    dcCore::app()->callBehavior('themeBeforeAdd', $module);

                    $this->store->process($module['file'], $dest);

                    # --BEHAVIOR-- themeAfterAdd
                    dcCore::app()->callBehavior('themeAfterAdd', $module);

                    $count++;
                }

                dcPage::addSuccessNotice(
                    __('Theme has been successfully installed.', 'Themes have been successfully installed.', $count)
                );
                http::redirect($this->getURL());
            } elseif (dcCore::app()->auth->isSuperAdmin() && !empty($_POST['update'])) {
                if (is_array($_POST['update'])) {
                    $modules = array_keys($_POST['update']);
                }

                $list = $this->store->get(true);
                if (empty($list)) {
                    throw new Exception(__('No such theme.'));
                }

                $count = 0;
                foreach ($list as $module) {
                    if (!in_array($module['id'], $modules)) {
                        continue;
                    }

                    $dest = $module['root'] . '/../' . basename($module['file']);

                    # --BEHAVIOR-- themeBeforeUpdate
                    dcCore::app()->callBehavior('themeBeforeUpdate', $module);

                    $this->store->process($module['file'], $dest);

                    # --BEHAVIOR-- themeAfterUpdate
                    dcCore::app()->callBehavior('themeAfterUpdate', $module);

                    $count++;
                }

                $tab = $count && $count == (is_countable($list) ? count($list) : 0) ? '#themes' : '#update';    // @phpstan-ignore-line

                dcPage::addSuccessNotice(
                    __('Theme has been successfully updated.', 'Themes have been successfully updated.', $count)
                );
                http::redirect($this->getURL() . $tab);
            }

            # Manual actions
            elseif (!empty($_POST['upload_pkg']) && !empty($_FILES['pkg_file'])
                || !empty($_POST['fetch_pkg'])   && !empty($_POST['pkg_url'])) {
                if (empty($_POST['your_pwd']) || !dcCore::app()->auth->checkPassword($_POST['your_pwd'])) {
                    throw new Exception(__('Password verification failed'));
                }

                if (!empty($_POST['upload_pkg'])) {
                    files::uploadStatus($_FILES['pkg_file']);

                    $dest = $this->getPath() . '/' . $_FILES['pkg_file']['name'];
                    if (!move_uploaded_file($_FILES['pkg_file']['tmp_name'], $dest)) {
                        throw new Exception(__('Unable to move uploaded file.'));
                    }
                } else {
                    $url  = urldecode($_POST['pkg_url']);
                    $dest = $this->getPath() . '/' . basename($url);
                    $this->store->download($url, $dest);
                }

                # --BEHAVIOR-- themeBeforeAdd
                dcCore::app()->callBehavior('themeBeforeAdd', null);

                $ret_code = $this->store->install($dest);

                # --BEHAVIOR-- themeAfterAdd
                dcCore::app()->callBehavior('themeAfterAdd', null);

                dcPage::addSuccessNotice(
                    $ret_code == dcModules::PACKAGE_UPDATED ?
                    __('The theme has been successfully updated.') :
                    __('The theme has been successfully installed.')
                );
                http::redirect($this->getURL() . '#themes');
            } else {
                # --BEHAVIOR-- adminModulesListDoActions
                dcCore::app()->callBehavior('adminModulesListDoActions', $this, $modules, 'theme');
            }
        }
    }
}
