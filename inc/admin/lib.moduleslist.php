<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_ADMIN_CONTEXT')) {return;}

/**
 * @brief Helper for admin list of modules.
 * @since 2.6

 * Provides an object to parse XML feed of modules from a repository.
 */
class adminModulesList
{
    public $core; /**< @var    object    dcCore instance */
    public $modules; /**< @var    object    dcModules instance */
    public $store; /**< @var    object    dcStore instance */

    public static $allow_multi_install = false; /**< @var    boolean    Work with multiple root directories */
    public static $distributed_modules = array(); /**< @var    array    List of modules distributed with Dotclear */

    protected $list_id = 'unknow'; /**< @var    string    Current list ID */
    protected $data    = array(); /**< @var    array    Current modules */

    protected $config_module  = ''; /**< @var    string    Module ID to configure */
    protected $config_file    = ''; /**< @var    string    Module path to configure */
    protected $config_content = ''; /**< @var    string    Module configuration page content */

    protected $path          = false; /**< @var    string    Modules root directory */
    protected $path_writable = false; /**< @var    boolean    Indicate if modules root directory is writable */
    protected $path_pattern  = false; /**< @var    string    Directory pattern to work on */

    protected $page_url   = ''; /**< @var    string    Page URL */
    protected $page_qs    = '?'; /**< @var    string    Page query string */
    protected $page_tab   = ''; /**< @var    string    Page tab */
    protected $page_redir = ''; /**< @var    string    Page redirection */

    public static $nav_indexes = 'abcdefghijklmnopqrstuvwxyz0123456789'; /**< @var    string    Index list */
    protected $nav_list        = array(); /**< @var    array    Index list with special index */
    protected $nav_special     = 'other'; /**< @var    string    Text for other special index */

    protected $sort_field = 'sname'; /**< @var    string    Field used to sort modules */
    protected $sort_asc   = true; /**< @var    boolean    Sort order asc */

    /**
     * Constructor.
     *
     * Note that this creates dcStore instance.
     *
     * @param    object    $modules        dcModules instance
     * @param    string    $modules_root    Modules root directories
     * @param    string    $xml_url        URL of modules feed from repository
     */
    public function __construct(dcModules $modules, $modules_root, $xml_url)
    {
        $this->core    = $modules->core;
        $this->modules = $modules;
        $this->store   = new dcStore($modules, $xml_url);

        $this->page_url = $this->core->adminurl->get('admin.plugins');

        $this->setPath($modules_root);
        $this->setIndex(__('other'));
    }

    /**
     * Begin a new list.
     *
     * @param    string    $id        New list ID
     * @return    adminModulesList self instance
     */
    public function setList($id)
    {
        $this->data     = array();
        $this->page_tab = '';
        $this->list_id  = $id;

        return $this;
    }

    /**
     * Get list ID.
     *
     * @return    List ID
     */
    public function getList()
    {
        return $this->list_id;
    }

    /// @name Modules root directory methods
    //@{
    /**
     * Set path info.
     *
     * @param    string    $root        Modules root directories
     * @return    adminModulesList self instance
     */
    protected function setPath($root)
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
     * Get modules root directory.
     *
     * @return    Path to work on
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Check if modules root directory is writable.
     *
     * @return    True if directory is writable
     */
    public function isWritablePath()
    {
        return $this->path_writable;
    }

    /**
     * Check if root directory of a module is deletable.
     *
     * @param    string    $root        Module root directory
     * @return    True if directory is delatable
     */
    public function isDeletablePath($root)
    {
        return $this->path_writable
        && (preg_match('!^' . $this->path_pattern . '!', $root) || defined('DC_DEV') && DC_DEV)
        && $this->core->auth->isSuperAdmin();
    }
    //@}

    /// @name Page methods
    //@{
    /**
     * Set page base URL.
     *
     * @param    string    $url        Page base URL
     * @return    adminModulesList self instance
     */
    public function setURL($url)
    {
        $this->page_qs  = strpos('?', $url) ? '&amp;' : '?';
        $this->page_url = $url;

        return $this;
    }

    /**
     * Get page URL.
     *
     * @param    string|array    $queries    Additionnal query string
     * @param    booleany    $with_tab        Add current tab to URL end
     * @return    Clean page URL
     */
    public function getURL($queries = '', $with_tab = true)
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
     * @return    adminModulesList self instance
     */
    public function setTab($tab)
    {
        $this->page_tab = $tab;

        return $this;
    }

    /**
     * Get page tab.
     *
     * @return    Page tab
     */
    public function getTab()
    {
        return $this->page_tab;
    }

    /**
     * Set page redirection.
     *
     * @param    string    $default        Default redirection
     * @return    adminModulesList self instance
     */
    public function setRedir($default = '')
    {
        $this->page_redir = empty($_REQUEST['redir']) ? $default : $_REQUEST['redir'];

        return $this;
    }

    /**
     * Get page redirection.
     *
     * @return    Page redirection
     */
    public function getRedir()
    {
        return empty($this->page_redir) ? $this->getURL() : $this->page_redir;
    }
    //@}

    /// @name Search methods
    //@{
    /**
     * Get search query.
     *
     * @return    Search query
     */
    public function getSearch()
    {
        $query = !empty($_REQUEST['m_search']) ? trim($_REQUEST['m_search']) : null;
        return strlen($query) > 2 ? $query : null;
    }

    /**
     * Display searh form.
     *
     * @return    adminModulesList self instance
     */
    public function displaySearch()
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
                count($this->data), html::escapeHTML($query)
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
     * @return    adminModulesList self instance
     */
    public function setIndex($str)
    {
        $this->nav_special = (string) $str;
        $this->nav_list    = array_merge(str_split(self::$nav_indexes), array($this->nav_special));

        return $this;
    }

    /**
     * Get index from query.
     *
     * @return    Query index or default one
     */
    public function getIndex()
    {
        return isset($_REQUEST['m_nav']) && in_array($_REQUEST['m_nav'], $this->nav_list) ? $_REQUEST['m_nav'] : $this->nav_list[0];
    }

    /**
     * Display navigation by index menu.
     *
     * @return    adminModulesList self instance
     */
    public function displayIndex()
    {
        if (empty($this->data) || $this->getSearch() !== null) {
            return $this;
        }

        # Fetch modules required field
        $indexes = array();
        foreach ($this->data as $id => $module) {
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

        $buttons = array();
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
     * @return    adminModulesList self instance
     */
    public function setSort($field, $asc = true)
    {
        $this->sort_field = $field;
        $this->sort_asc   = (boolean) $asc;

        return $this;
    }

    /**
     * Get sort field from query.
     *
     * @return    Query sort field or default one
     */
    public function getSort()
    {
        return !empty($_REQUEST['m_sort']) ? $_REQUEST['m_sort'] : $this->sort_field;
    }

    /**
     * Display sort field form.
     *
     * @note    This method is not implemented yet
     * @return    adminModulesList self instance
     */
    public function displaySort()
    {
        //

        return $this;
    }
    //@}

    /// @name Modules methods
    //@{
    /**
     * Set modules and sanitize them.
     *
     * @return    adminModulesList self instance
     */
    public function setModules($modules)
    {
        $this->data = array();
        if (!empty($modules) && is_array($modules)) {
            foreach ($modules as $id => $module) {
                $this->data[$id] = self::sanitizeModule($id, $module);
            }
        }
        return $this;
    }

    /**
     * Get modules currently set.
     *
     * @return    Array of modules
     */
    public function getModules()
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
     * @return    Array of the module informations
     */
    public static function sanitizeModule($id, $module)
    {
        $label = empty($module['label']) ? $id : $module['label'];
        $name  = __(empty($module['name']) ? $label : $module['name']);

        return array_merge(
            # Default values
            array(
                'desc'              => '',
                'author'            => '',
                'version'           => 0,
                'current_version'   => 0,
                'root'              => '',
                'root_writable'     => false,
                'permissions'       => null,
                'parent'            => null,
                'priority'          => 1000,
                'standalone_config' => false,
                'support'           => '',
                'section'           => '',
                'tags'              => '',
                'details'           => '',
                'sshot'             => '',
                'score'             => 0,
                'type'              => null,
                'require'           => array(),
                'settings'          => array()
            ),
            # Module's values
            $module,
            # Clean up values
            array(
                'id'    => $id,
                'sid'   => self::sanitizeString($id),
                'label' => $label,
                'name'  => $name,
                'sname' => self::sanitizeString($name)
            )
        );
    }

    /**
     * Check if a module is part of the distribution.
     *
     * @param    string    $id        Module root directory
     * @return    True if module is part of the distribution
     */
    public static function isDistributedModule($id)
    {
        $distributed_modules = self::$distributed_modules;

        return is_array($distributed_modules) && in_array($id, $distributed_modules);
    }

    /**
     * Sort modules list by specific field.
     *
     * @param    string    $module        Array of modules
     * @param    string    $field        Field to sort from
     * @param    bollean    $asc        Sort asc if true, else decs
     * @return    Array of sorted modules
     */
    public static function sortModules($modules, $field, $asc = true)
    {
        $origin = $sorter = array();

        foreach ($modules as $id => $module) {
            $origin[] = $module;
            $sorter[] = isset($module[$field]) ? $module[$field] : $field;
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
     * @param    array    $cols        List of colones (module field) to display
     * @param    array    $actions    List of predefined actions to show on form
     * @param    boolean    $nav_limit    Limit list to previously selected index
     * @return    adminModulesList self instance
     */
    public function displayModules($cols = array('name', 'version', 'desc'), $actions = array(), $nav_limit = false)
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

        if (in_array('distrib', $cols)) {
            echo
                '<th' . (in_array('desc', $cols) ? '' : ' class="maximal"') . '></th>';
        }

        if (!empty($actions) && $this->core->auth->isSuperAdmin()) {
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

            echo
            '<tr class="line" id="' . html::escapeHTML($this->list_id) . '_m_' . html::escapeHTML($id) . '"' .
                (in_array('desc', $cols) ? ' title="' . html::escapeHTML(__($module['desc'])) . '" ' : '') .
                '>';

            $tds = 0;

            if (in_array('checkbox', $cols)) {
                $tds++;
                echo
                '<td class="module-icon nowrap">' .
                form::checkbox(array('modules[' . $count . ']', html::escapeHTML($this->list_id) . '_modules_' . html::escapeHTML($id)), html::escapeHTML($id)) .
                    '</td>';
            }

            if (in_array('icon', $cols)) {
                $tds++;
                echo
                '<td class="module-icon nowrap">' . sprintf(
                    '<img alt="%1$s" title="%1$s" src="%2$s" />',
                    html::escapeHTML($id), file_exists($module['root'] . '/icon.png') ?
                    dcPage::getPF($id . '/icon.png') : 'images/module.png'
                ) . '</td>';
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
                form::hidden(array('modules[' . $count . ']'), html::escapeHTML($id));
            }
            echo
            $this->core->formNonce() .
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
                if (isset($module['cannot_disable']) && $module['enabled']) {
                    echo
                    '<br/><span class="info">' .
                    sprintf(__('This module cannot be disabled nor deleted, since the following modules are also enabled : %s'),
                        join(',', $module['cannot_disable'])) .
                        '</span>';
                }
                if (isset($module['cannot_enable']) && !$module['enabled']) {
                    echo
                    '<br/><span class="info">' .
                    __('This module cannot be enabled, because of the following reasons :') .
                        '<ul>';
                    foreach ($module['cannot_enable'] as $m => $reason) {
                        echo '<li>' . $reason . '</li>';
                    }
                    echo '</ul>' .
                        '</span>';
                }
                echo '</td>';

            }

            if (in_array('distrib', $cols)) {
                $tds++;
                echo
                    '<td class="module-distrib">' . (self::isDistributedModule($id) ?
                    '<img src="images/dotclear_pw.png" alt="' .
                    __('Plugin from official distribution') . '" title="' .
                    __('Plugin from official distribution') . '" />'
                    : '') . '</td>';
            }

            if (!empty($actions) && $this->core->auth->isSuperAdmin()) {
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

                    $more = array();
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

                $config = !empty($module['root']) && file_exists(path::real($module['root'] . '/_config.php'));
                $index  = !empty($module['root']) && file_exists(path::real($module['root'] . '/index.php'));

                if ($config || $index || !empty($module['section']) || !empty($module['tags']) || !empty($module['settings'])) {
                    echo
                        '<div><ul class="mod-more">';

                    if ($index && $module['enabled']) {
                        echo '<li><a href="' . $this->core->adminurl->get('admin.plugin.' . $id) . '">' . __('Manage plugin') . '</a></li>';
                    }

                    $settings = $this->getSettingsUrls($this->core, $id);
                    if (!empty($settings) && $module['enabled']) {
                        echo '<li>' . implode(' - ', $settings) . '</li>';
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
        } elseif ((in_array('checkbox', $cols) || $count > 1) && !empty($actions) && $this->core->auth->isSuperAdmin()) {
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
     * @param object $core
     * @param string $id module ID
     * @param boolean $check check permission
     * @param boolean $self include self URL (→ plugin index.php URL)
     * @return Array of settings URLs
     */
    public static function getSettingsUrls($core, $id, $check = false, $self = true)
    {
        $st = array();

        $mr       = $core->plugins->moduleRoot($id);
        $config   = !empty($mr) && file_exists(path::real($mr . '/_config.php'));
        $settings = $core->plugins->moduleInfo($id, 'settings');
        if ($config || !empty($settings)) {
            if ($config) {
                if (!$check ||
                    $core->auth->isSuperAdmin() ||
                    $core->auth->check($core->plugins->moduleInfo($id, 'permissions'), $core->blog->id)) {
                    $params = array('module' => $id, 'conf' => '1');
                    if (!$core->plugins->moduleInfo($id, 'standalone_config') && !$self) {
                        $params['redir'] = $core->adminurl->get('admin.plugin.' . $id);
                    }
                    $st[] = '<a class="module-config" href="' .
                    $core->adminurl->get('admin.plugins', $params) .
                    '">' . __('Configure plugin') . '</a>';
                }
            }
            if (is_array($settings)) {
                foreach ($settings as $sk => $sv) {
                    switch ($sk) {
                        case 'blog':
                            if (!$check ||
                                $core->auth->isSuperAdmin() ||
                                $core->auth->check('admin', $core->blog->id)) {
                                $st[] = '<a class="module-config" href="' .
                                $core->adminurl->get('admin.blog.pref') . $sv .
                                '">' . __('Plugin settings (in blog parameters)') . '</a>';
                            }
                            break;
                        case 'pref':
                            if (!$check ||
                                $core->auth->isSuperAdmin() ||
                                $core->auth->check('usage,contentadmin', $core->blog->id)) {
                                $st[] = '<a class="module-config" href="' .
                                $core->adminurl->get('admin.user.preferences') . $sv .
                                '">' . __('Plugin settings (in user preferences)') . '</a>';
                            }
                            break;
                        case 'self':
                            if ($self) {
                                if (!$check ||
                                    $core->auth->isSuperAdmin() ||
                                    $core->auth->check($core->plugins->moduleInfo($id, 'permissions'), $core->blog->id)) {
                                    $st[] = '<a class="module-config" href="' .
                                    $core->adminurl->get('admin.plugin.' . $id) . $sv .
                                    '">' . __('Plugin settings') . '</a>';
                                }
                            }
                            break;
                    }
                }
            }
        }

        return $st;
    }

    /**
     * Get action buttons to add to modules list.
     *
     * @param    string    $id            Module ID
     * @param    array    $module        Module info
     * @param    array    $actions    Actions keys
     * @return    Array of actions buttons
     */
    protected function getActions($id, $module, $actions)
    {
        $submits = array();

        # Use loop to keep requested order
        foreach ($actions as $action) {
            switch ($action) {

                # Deactivate
                case 'activate':if ($this->core->auth->isSuperAdmin() && $module['root_writable'] && !isset($module['cannot_enable'])) {
                        $submits[] =
                        '<input type="submit" name="activate[' . html::escapeHTML($id) . ']" value="' . __('Activate') . '" />';
                    }break;

                # Activate
                case 'deactivate':if ($this->core->auth->isSuperAdmin() && $module['root_writable'] && !isset($module['cannot_disable'])) {
                        $submits[] =
                        '<input type="submit" name="deactivate[' . html::escapeHTML($id) . ']" value="' . __('Deactivate') . '" class="reset" />';
                    }break;

                # Delete
                case 'delete':if ($this->core->auth->isSuperAdmin() && $this->isDeletablePath($module['root']) && !isset($module['cannot_disable'])) {
                        $dev       = !preg_match('!^' . $this->path_pattern . '!', $module['root']) && defined('DC_DEV') && DC_DEV ? ' debug' : '';
                        $submits[] =
                        '<input type="submit" class="delete ' . $dev . '" name="delete[' . html::escapeHTML($id) . ']" value="' . __('Delete') . '" />';
                    }break;

                # Install (from store)
                case 'install':if ($this->core->auth->isSuperAdmin() && $this->path_writable) {
                        $submits[] =
                        '<input type="submit" name="install[' . html::escapeHTML($id) . ']" value="' . __('Install') . '" />';
                    }break;

                # Update (from store)
                case 'update':if ($this->core->auth->isSuperAdmin() && $this->path_writable) {
                        $submits[] =
                        '<input type="submit" name="update[' . html::escapeHTML($id) . ']" value="' . __('Update') . '" />';
                    }break;

                # Behavior
                case 'behavior':

                    # --BEHAVIOR-- adminModulesListGetActions
                    $tmp = $this->core->callBehavior('adminModulesListGetActions', $this, $id, $module);

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
     * @param    array    $actions          Actions keys
     * @param boolean   $with_selection Limit action to selected modules
     * @return    Array of actions buttons
     */
    protected function getGlobalActions($actions, $with_selection = false)
    {
        $submits = array();

        # Use loop to keep requested order
        foreach ($actions as $action) {
            switch ($action) {

                # Deactivate
                case 'activate':if ($this->core->auth->isSuperAdmin() && $this->path_writable) {
                        $submits[] =
                            '<input type="submit" name="activate" value="' . ($with_selection ?
                            __('Activate selected plugins') :
                            __('Activate all plugins from this list')
                        ) . '" />';
                    }break;

                # Activate
                case 'deactivate':if ($this->core->auth->isSuperAdmin() && $this->path_writable) {
                        $submits[] =
                            '<input type="submit" name="deactivate" value="' . ($with_selection ?
                            __('Deactivate selected plugins') :
                            __('Deactivate all plugins from this list')
                        ) . '" />';
                    }break;

                # Update (from store)
                case 'update':if ($this->core->auth->isSuperAdmin() && $this->path_writable) {
                        $submits[] =
                            '<input type="submit" name="update" value="' . ($with_selection ?
                            __('Update selected plugins') :
                            __('Update all plugins from this list')
                        ) . '" />';
                    }break;

                # Behavior
                case 'behavior':

                    # --BEHAVIOR-- adminModulesListGetGlobalActions
                    $tmp = $this->core->callBehavior('adminModulesListGetGlobalActions', $this, $with_selection);

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
     * @note    Set a notice on success through dcPage::addSuccessNotice
     * @throw    Exception    Module not find or command failed
     * @return    Null
     */
    public function doActions()
    {
        if (empty($_POST) || !empty($_REQUEST['conf'])
            || !$this->isWritablePath()) {
            return;
        }

        $modules = !empty($_POST['modules']) && is_array($_POST['modules']) ? array_values($_POST['modules']) : array();

        if ($this->core->auth->isSuperAdmin() && !empty($_POST['delete'])) {

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
                    $this->core->callBehavior('pluginBeforeDelete', $module);

                    $this->modules->deleteModule($id);

                    # --BEHAVIOR-- moduleAfterDelete
                    $this->core->callBehavior('pluginAfterDelete', $module);
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
        } elseif ($this->core->auth->isSuperAdmin() && !empty($_POST['install'])) {

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
                $this->core->callBehavior('pluginBeforeAdd', $module);

                $this->store->process($module['file'], $dest);

                # --BEHAVIOR-- moduleAfterAdd
                $this->core->callBehavior('pluginAfterAdd', $module);

                $count++;
            }

            dcPage::addSuccessNotice(
                __('Plugin has been successfully installed.', 'Plugins have been successuflly installed.', $count)
            );
            http::redirect($this->getURL());
        } elseif ($this->core->auth->isSuperAdmin() && !empty($_POST['activate'])) {

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
                $this->core->callBehavior('pluginBeforeActivate', $id);

                $this->modules->activateModule($id);

                # --BEHAVIOR-- moduleAfterActivate
                $this->core->callBehavior('pluginAfterActivate', $id);

                $count++;
            }

            dcPage::addSuccessNotice(
                __('Plugin has been successfully activated.', 'Plugins have been successuflly activated.', $count)
            );
            http::redirect($this->getURL());
        } elseif ($this->core->auth->isSuperAdmin() && !empty($_POST['deactivate'])) {

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
                $this->core->callBehavior('pluginBeforeDeactivate', $module);

                $this->modules->deactivateModule($id);

                # --BEHAVIOR-- moduleAfterDeactivate
                $this->core->callBehavior('pluginAfterDeactivate', $module);

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
        } elseif ($this->core->auth->isSuperAdmin() && !empty($_POST['update'])) {

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
                        @file_put_contents($module['root'] . '/_disabled', '');
                    }
                }

                # --BEHAVIOR-- moduleBeforeUpdate
                $this->core->callBehavior('pluginBeforeUpdate', $module);

                $this->store->process($module['file'], $dest);

                # --BEHAVIOR-- moduleAfterUpdate
                $this->core->callBehavior('pluginAfterUpdate', $module);

                $count++;
            }

            $tab = $count && $count == count($list) ? '#plugins' : '#update';

            dcPage::addSuccessNotice(
                __('Plugin has been successfully updated.', 'Plugins have been successuflly updated.', $count)
            );
            http::redirect($this->getURL() . $tab);
        }

        # Manual actions
        elseif (!empty($_POST['upload_pkg']) && !empty($_FILES['pkg_file'])
            || !empty($_POST['fetch_pkg']) && !empty($_POST['pkg_url'])) {
            if (empty($_POST['your_pwd']) || !$this->core->auth->checkPassword($_POST['your_pwd'])) {
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
            $this->core->callBehavior('pluginBeforeAdd', null);

            $ret_code = $this->store->install($dest);

            # --BEHAVIOR-- moduleAfterAdd
            $this->core->callBehavior('pluginAfterAdd', null);

            dcPage::addSuccessNotice($ret_code == 2 ?
                __('Plugin has been successfully updated.') :
                __('Plugin has been successfully installed.')
            );
            http::redirect($this->getURL() . '#plugins');
        } else {

            # --BEHAVIOR-- adminModulesListDoActions
            $this->core->callBehavior('adminModulesListDoActions', $this, $modules, 'plugin');

        }

        return;
    }

    /**
     * Display tab for manual installation.
     *
     * @return    adminModulesList self instance
     */
    public function displayManualForm()
    {
        if (!$this->core->auth->isSuperAdmin() || !$this->isWritablePath()) {
            return;
        }

        # 'Upload module' form
        echo
        '<form method="post" action="' . $this->getURL() . '" id="uploadpkg" enctype="multipart/form-data" class="fieldset">' .
        '<h4>' . __('Upload a zip file') . '</h4>' .
        '<p class="field"><label for="pkg_file" class="classic required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Zip file path:') . '</label> ' .
        '<input type="file" name="pkg_file" id="pkg_file" required /></p>' .
        '<p class="field"><label for="your_pwd1" class="classic required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Your password:') . '</label> ' .
        form::password(array('your_pwd', 'your_pwd1'), 20, 255,
            array(
                'extra_html'   => 'required placeholder="' . __('Password') . '"',
                'autocomplete' => 'current-password'
            )
        ) . '</p>' .
        '<p><input type="submit" name="upload_pkg" value="' . __('Upload') . '" />' .
        $this->core->formNonce() . '</p>' .
            '</form>';

        # 'Fetch module' form
        echo
        '<form method="post" action="' . $this->getURL() . '" id="fetchpkg" class="fieldset">' .
        '<h4>' . __('Download a zip file') . '</h4>' .
        '<p class="field"><label for="pkg_url" class="classic required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Zip file URL:') . '</label> ' .
        form::field('pkg_url', 40, 255, array(
            'extra_html' => 'required placeholder="' . __('URL') . '"'
        )) .
        '</p>' .
        '<p class="field"><label for="your_pwd2" class="classic required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Your password:') . '</label> ' .
        form::password(array('your_pwd', 'your_pwd2'), 20, 255,
            array(
                'extra_html'   => 'required placeholder="' . __('Password') . '"',
                'autocomplete' => 'current-password'
            )
        ) . '</p>' .
        '<p><input type="submit" name="fetch_pkg" value="' . __('Download') . '" />' .
        $this->core->formNonce() . '</p>' .
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
     * @return    True if config set
     */
    public function setConfiguration($id = null)
    {
        if (empty($_REQUEST['conf']) || empty($_REQUEST['module']) && !$id) {
            return false;
        }

        if (!empty($_REQUEST['module']) && empty($id)) {
            $id = $_REQUEST['module'];
        }

        if (!$this->modules->moduleExists($id)) {
            $this->core->error->add(__('Unknow plugin ID'));
            return false;
        }

        $module = $this->modules->getModules($id);
        $module = self::sanitizeModule($id, $module);
        $file   = path::real($module['root'] . '/_config.php');

        if (!file_exists($file)) {
            $this->core->error->add(__('This plugin has no configuration file.'));
            return false;
        }

        $this->config_module  = $module;
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
     * @return Full path of config file or null
     */
    public function includeConfiguration()
    {
        if (!$this->config_file) {
            return;
        }
        $this->setRedir($this->getURL() . '#plugins');

        ob_start();

        return $this->config_file;
    }

    /**
     * Gather module configuration file content.
     *
     * @note Required previously file inclusion
     * @return True if content has been captured
     */
    public function getConfiguration()
    {
        if ($this->config_file) {
            $this->config_content = ob_get_contents();
        }

        ob_end_clean();

        return !empty($this->file_content);
    }

    /**
     * Display module configuration form.
     *
     * @note Required previously gathered content
     * @return    adminModulesList self instance
     */
    public function displayConfiguration()
    {
        if ($this->config_file) {

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
                $this->core->formNonce() . '</p>' .
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
     * @return    Sanitized string
     */
    public static function sanitizeString($str)
    {
        return preg_replace('/[^A-Za-z0-9\@\#+_-]/', '', strtolower($str));
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
     * @param    object    $modules        dcModules instance
     * @param    string    $modules_root    Modules root directories
     * @param    string    $xml_url        URL of modules feed from repository
     */
    public function __construct(dcModules $modules, $modules_root, $xml_url)
    {
        parent::__construct($modules, $modules_root, $xml_url);
        $this->page_url = $this->core->adminurl->get('admin.blog.theme');
    }

    public function displayModules($cols = array('name', 'config', 'version', 'desc'), $actions = array(), $nav_limit = false)
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

            $current = $this->core->blog->settings->system->theme == $id && $this->modules->moduleExists($id);
            $distrib = self::isDistributedModule($id) ? ' dc-box' : '';

            $line =
                '<div class="box ' . ($current ? 'medium current-theme' : 'theme') . $distrib . '">';

            if (in_array('name', $cols) && !$current) {
                $line .=
                    '<h4 class="module-name">';

                if (in_array('checkbox', $cols)) {
                    $line .=
                    '<label for="' . html::escapeHTML($this->list_id) . '_modules_' . html::escapeHTML($id) . '">' .
                    form::checkbox(array('modules[' . $count . ']', html::escapeHTML($this->list_id) . '_modules_' . html::escapeHTML($id)), html::escapeHTML($id)) .
                    html::escapeHTML($module['name']) .
                        '</label>';

                } else {
                    $line .=
                    form::hidden(array('modules[' . $count . ']'), html::escapeHTML($id)) .
                    html::escapeHTML($module['name']);
                }

                $line .=
                $this->core->formNonce() .
                    '</h4>';
            }

            # Display score only for debug purpose
            if (in_array('score', $cols) && $this->getSearch() !== null && defined('DC_DEBUG') && DC_DEBUG) {
                $line .=
                '<p class="module-score debug">' . sprintf(__('Score: %s'), $module['score']) . '</p>';
            }

            if (in_array('sshot', $cols)) {
                # Screenshot from url
                if (preg_match('#^http(s)?://#', $module['sshot'])) {
                    $sshot = $module['sshot'];
                }
                # Screenshot from installed module
                elseif (file_exists($this->core->blog->themes_path . '/' . $id . '/screenshot.jpg')) {
                    $sshot = $this->getURL('shot=' . rawurlencode($id));
                }
                # Default screenshot
                else {
                    $sshot = 'images/noscreenshot.png';
                }

                $line .=
                '<div class="module-sshot"><img src="' . $sshot . '" alt="' .
                sprintf(__('%s screenshot.'), html::escapeHTML($module['name'])) . '" /></div>';
            }

            $line .=
                '<div class="module-infos toggle-bloc">';

            if (in_array('name', $cols) && $current) {
                $line .=
                    '<h4 class="module-name">';

                if (in_array('checkbox', $cols)) {
                    $line .=
                    '<label for="' . html::escapeHTML($this->list_id) . '_modules_' . html::escapeHTML($id) . '">' .
                    form::checkbox(array('modules[' . $count . ']', html::escapeHTML($this->list_id) . '_modules_' . html::escapeHTML($id)), html::escapeHTML($id)) .
                    html::escapeHTML($module['name']) .
                        '</label>';
                } else {
                    $line .=
                    form::hidden(array('modules[' . $count . ']'), html::escapeHTML($id)) .
                    html::escapeHTML($module['name']);
                }

                $line .=
                    '</h4>';
            }

            $line .=
                '<p>';

            if (in_array('desc', $cols)) {
                $line .=
                '<span class="module-desc">' . html::escapeHTML(__($module['desc'])) . '</span> ';
            }

            if (in_array('author', $cols)) {
                $line .=
                '<span class="module-author">' . sprintf(__('by %s'), html::escapeHTML($module['author'])) . '</span> ';
            }

            if (in_array('version', $cols)) {
                $line .=
                '<span class="module-version">' . sprintf(__('version %s'), html::escapeHTML($module['version'])) . '</span> ';
            }

            if (in_array('current_version', $cols)) {
                $line .=
                '<span class="module-current-version">' . sprintf(__('(current version %s)'), html::escapeHTML($module['current_version'])) . '</span> ';
            }

            if (in_array('parent', $cols) && !empty($module['parent'])) {
                if ($this->modules->moduleExists($module['parent'])) {
                    $line .=
                    '<span class="module-parent-ok">' . sprintf(__('(built on "%s")'), html::escapeHTML($module['parent'])) . '</span> ';
                } else {
                    $line .=
                    '<span class="module-parent-missing">' . sprintf(__('(requires "%s")'), html::escapeHTML($module['parent'])) . '</span> ';
                }
            }

            $has_details = in_array('details', $cols) && !empty($module['details']);
            $has_support = in_array('support', $cols) && !empty($module['support']);
            if ($has_details || $has_support) {
                $line .=
                    '<span class="mod-more">';

                if ($has_details) {
                    $line .=
                    '<a class="module-details" href="' . $module['details'] . '">' . __('Details') . '</a>';
                }

                if ($has_support) {
                    $line .=
                    ' - <a class="module-support" href="' . $module['support'] . '">' . __('Support') . '</a>';
                }

                $line .=
                    '</span>';
            }

            $line .=
                '</p>' .
                '</div>';

            $line .=
                '<div class="module-actions toggle-bloc">';

            # Plugins actions
            if ($current) {

                # _GET actions
                if (file_exists(path::real($this->core->blog->themes_path . '/' . $id) . '/style.css')) {
                    $theme_url = preg_match('#^http(s)?://#', $this->core->blog->settings->system->themes_url) ?
                    http::concatURL($this->core->blog->settings->system->themes_url, '/' . $id) :
                    http::concatURL($this->core->blog->url, $this->core->blog->settings->system->themes_url . '/' . $id);
                    $line .=
                    '<p><a href="' . $theme_url . '/style.css">' . __('View stylesheet') . '</a></p>';
                }

                $line .= '<div class="current-actions">';

                if (file_exists(path::real($this->core->blog->themes_path . '/' . $id) . '/_config.php')) {
                    $line .=
                    '<p><a href="' . $this->getURL('module=' . $id . '&amp;conf=1', false) . '" class="button submit">' . __('Configure theme') . '</a></p>';
                }

                # --BEHAVIOR-- adminCurrentThemeDetails
                $line .=
                $this->core->callBehavior('adminCurrentThemeDetails', $this->core, $id, $module);

                $line .= '</div>';
            }

            # _POST actions
            if (!empty($actions)) {
                $line .=
                '<p>' . implode(' ', $this->getActions($id, $module, $actions)) . '</p>';
            }

            $line .=
                '</div>';

            $line .=
                '</div>';

            $count++;

            $res = $current ? $line . $res : $res . $line;
        }

        echo
            $res .
            '</div>';

        if (!$count && $this->getSearch() === null) {
            echo
            '<p class="message">' . __('No themes matched your search.') . '</p>';
        } elseif ((in_array('checkbox', $cols) || $count > 1) && !empty($actions) && $this->core->auth->isSuperAdmin()) {
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

    protected function getActions($id, $module, $actions)
    {
        $submits = array();

        $this->core->blog->settings->addNamespace('system');
        if ($id != $this->core->blog->settings->system->theme) {

            # Select theme to use on curent blog
            if (in_array('select', $actions)) {
                $submits[] =
                '<input type="submit" name="select[' . html::escapeHTML($id) . ']" value="' . __('Use this one') . '" />';
            }
        }

        return array_merge(
            $submits,
            parent::getActions($id, $module, $actions)
        );
    }

    protected function getGlobalActions($actions, $with_selection = false)
    {
        $submits = array();

        foreach ($actions as $action) {
            switch ($action) {

                # Update (from store)
                case 'update':if ($this->core->auth->isSuperAdmin() && $this->path_writable) {
                        $submits[] =
                        '<input type="submit" name="update" value="' . ($with_selection ?
                            __('Update selected themes') :
                            __('Update all themes from this list')
                        ) . '" />' . $this->core->formNonce();
                    }break;

                # Behavior
                case 'behavior':

                    # --BEHAVIOR-- adminModulesListGetGlobalActions
                    $tmp = $this->core->callBehavior('adminModulesListGetGlobalActions', $this);

                    if (!empty($tmp)) {
                        $submits[] = $tmp;
                    }
                    break;
            }
        }

        return $submits;
    }

    public function doActions()
    {
        if (empty($_POST) || !empty($_REQUEST['conf'])) {
            return;
        }

        $modules = !empty($_POST['modules']) && is_array($_POST['modules']) ? array_values($_POST['modules']) : array();

        if (!empty($_POST['select'])) {

            # Can select only one theme at a time!
            if (is_array($_POST['select'])) {
                $modules = array_keys($_POST['select']);
                $id      = $modules[0];

                if (!$this->modules->moduleExists($id)) {
                    throw new Exception(__('No such theme.'));
                }

                $this->core->blog->settings->addNamespace('system');
                $this->core->blog->settings->system->put('theme', $id);
                $this->core->blog->triggerBlog();

                dcPage::addSuccessNotice(__('Theme has been successfully selected.'));
                http::redirect($this->getURL() . '#themes');
            }
        } else {
            if (!$this->isWritablePath()) {
                return;
            }

            if ($this->core->auth->isSuperAdmin() && !empty($_POST['activate'])) {

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
                    $this->core->callBehavior('themeBeforeActivate', $id);

                    $this->modules->activateModule($id);

                    # --BEHAVIOR-- themeAfterActivate
                    $this->core->callBehavior('themeAfterActivate', $id);

                    $count++;
                }

                dcPage::addSuccessNotice(
                    __('Theme has been successfully activated.', 'Themes have been successuflly activated.', $count)
                );
                http::redirect($this->getURL());
            } elseif ($this->core->auth->isSuperAdmin() && !empty($_POST['deactivate'])) {

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
                    $this->core->callBehavior('themeBeforeDeactivate', $module);

                    $this->modules->deactivateModule($id);

                    # --BEHAVIOR-- themeAfterDeactivate
                    $this->core->callBehavior('themeAfterDeactivate', $module);

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
            } elseif ($this->core->auth->isSuperAdmin() && !empty($_POST['delete'])) {

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
                        $this->core->callBehavior('themeBeforeDelete', $module);

                        $this->modules->deleteModule($id);

                        # --BEHAVIOR-- themeAfterDelete
                        $this->core->callBehavior('themeAfterDelete', $module);
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
            } elseif ($this->core->auth->isSuperAdmin() && !empty($_POST['install'])) {

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
                    $this->core->callBehavior('themeBeforeAdd', $module);

                    $this->store->process($module['file'], $dest);

                    # --BEHAVIOR-- themeAfterAdd
                    $this->core->callBehavior('themeAfterAdd', $module);

                    $count++;
                }

                dcPage::addSuccessNotice(
                    __('Theme has been successfully installed.', 'Themes have been successuflly installed.', $count)
                );
                http::redirect($this->getURL());
            } elseif ($this->core->auth->isSuperAdmin() && !empty($_POST['update'])) {

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
                    $this->core->callBehavior('themeBeforeUpdate', $module);

                    $this->store->process($module['file'], $dest);

                    # --BEHAVIOR-- themeAfterUpdate
                    $this->core->callBehavior('themeAfterUpdate', $module);

                    $count++;
                }

                $tab = $count && $count == count($list) ? '#themes' : '#update';

                dcPage::addSuccessNotice(
                    __('Theme has been successfully updated.', 'Themes have been successuflly updated.', $count)
                );
                http::redirect($this->getURL() . $tab);
            }

            # Manual actions
            elseif (!empty($_POST['upload_pkg']) && !empty($_FILES['pkg_file'])
                || !empty($_POST['fetch_pkg']) && !empty($_POST['pkg_url'])) {
                if (empty($_POST['your_pwd']) || !$this->core->auth->checkPassword($_POST['your_pwd'])) {
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
                $this->core->callBehavior('themeBeforeAdd', null);

                $ret_code = $this->store->install($dest);

                # --BEHAVIOR-- themeAfterAdd
                $this->core->callBehavior('themeAfterAdd', null);

                dcPage::addSuccessNotice($ret_code == 2 ?
                    __('Theme has been successfully updated.') :
                    __('Theme has been successfully installed.')
                );
                http::redirect($this->getURL() . '#themes');
            } else {

                # --BEHAVIOR-- adminModulesListDoActions
                $this->core->callBehavior('adminModulesListDoActions', $this, $modules, 'theme');

            }
        }

        return;
    }
}
