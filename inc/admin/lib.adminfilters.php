<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @since 2.20
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

/**
 * @brief Generic class for admin list filters form
 */
class adminGenericFilter
{
    /** @var dcCore core instance */
    protected $core;

    /** @var string Filter form type (main id) */
    protected $type;

    /** @var array Filters objects */
    protected $filters = [];

    /** @var boolean Show filter indicator */
    protected $show = false;

    /** @var boolean Has user preferences */
    protected $has_user_pref = false;

    /**
     * Constructs a new instance.
     *
     * @param dcCore $core  core instance
     * @param string $type  The filter form main id
     */
    public function __construct(dcCore $core, string $type)
    {
        $this->core = &$core;
        $this->type = $type;

        $this->parseOptions();
    }

    /**
     * Get user defined filter options (sortby, order, nb)
     *
     * @param   string   $option     The option
     *
     * @return  mixed                User option
     */
    public function userOptions(?string $option = null)
    {
        return adminUserPref::getUserFilters($this->type, $option);
    }

    /**
     * Parse _GET user pref options (sortby, order, nb)
     */
    protected function parseOptions()
    {
        $options = adminUserPref::getUserFilters($this->type);
        if (!empty($options)) {
            $this->has_user_pref = true;
        }

        if (!empty($options[1])) {
            $this->filters['sortby'] = new dcAdminFilter('sortby', $this->userOptions('sortby'));
            $this->filters['sortby']->options($options[1]);

            if (!empty($_GET['sortby'])
                && in_array($_GET['sortby'], $options[1], true)
                && $_GET['sortby'] != $this->userOptions('sortby')
            ) {
                $this->show(true);
                $this->filters['sortby']->value($_GET['sortby']);
            }
        }
        if (!empty($options[3])) {
            $this->filters['order'] = new dcAdminFilter('order', $this->userOptions('order'));
            $this->filters['order']->options(dcAdminCombos::getOrderCombo());

            if (!empty($_GET['order'])
                && in_array($_GET['order'], dcAdminCombos::getOrderCombo(), true)
                && $_GET['order'] != $this->userOptions('order')
            ) {
                $this->show(true);
                $this->filters['order']->value($_GET['order']);
            }
        }
        if (!empty($options[4])) {
            $this->filters['nb'] = new dcAdminFilter('nb', $this->userOptions('nb'));
            $this->filters['nb']->title($options[4][0]);

            if (!empty($_GET['nb'])
                && (integer) $_GET['nb'] > 0
                && (integer) $_GET['nb'] != $this->userOptions('nb')
            ) {
                $this->show(true);
                $this->filters['nb']->value((integer) $_GET['nb']);
            }
        }
    }

    /**
     * Get filters key/value pairs
     *
     * @param  boolean $escape  Escape widlcard %
     * @param  boolean $ui_only Limit to filters with ui
     *
     * @return array            The filters
     */
    public function values($escape = false, $ui_only = false)
    {
        $res = [];
        foreach ($this->filters as $id => $filter) {
            if ($ui_only) {
                if (in_array($id, ['sortby', 'order', 'nb']) || $filter->html != '') {
                    $res[$id] = $filter->value;
                }
            } else {
                $res[$id] = $filter->value;
            }
        }

        return $escape ? preg_replace('/%/', '%%', $res) : $res;
    }

    /**
     * Get a filter value
     *
     * @param  string $id The filter id
     * @param  string $id The filter value if not exists
     *
     * @return mixed      The filter value
     */
    public function value(string $id, $undefined = null)
    {
        return isset($this->filters[$id]) ? $this->filters[$id]->value : $undefined;
    }

    /**
     * Magic get filter value
     *
     * @param  string   $id     The filter id
     *
     * @return mixed            The filter value
     */
    public function __get($id)
    {
        return $this->value($id);
    }

    /**
     * Add filter(s)
     *
     * @param array|string|dcAdminFilter|null   $filter     The filter(s) array or id or object
     * @param mixed                             $value      The filter value if $filter is id
     *
     * @return mixed                                        The filter value
     */
    public function add($filter = null, $value = null)
    {
        # empty filter (ex: do not show form if there are no categories on a blog)
        if (null === $filter) {
            return null;
        }

        # multiple filters
        if (is_array($filter)) {
            foreach ($filter as $f) {
                $this->add($f);
            }

            return null;
        }

        # simple filter
        if (is_string($filter)) {
            $filter = new dcAdminFilter($filter, $value);
        }

        # not well formed filter or reserved id
        if (!($filter instanceof dcAdminFilter) || $filter->id == '') {
            return null;
        }

        # parse _GET values and create html forms
        $filter->parse();

        # set key/value pair
        $this->filters[$filter->id] = $filter;

        # has contents
        if ($filter->html != '' && $filter->form != 'none') {
            # not default value = show filters form
            $this->show($filter->value !== '');
        }

        return $filter->value;
    }

    /**
     * Remove a filter
     *
     * @param  string $id   The filter id
     *
     * @return boolean      The success
     */
    public function remove(string $id)
    {
        if (array_key_exists($id, $this->filters)) {
            unset($this->filters[$id]);

            return true;
        }

        return false;
    }

    /**
     * Get list query params
     *
     * @return array    The query params
     */
    public function params()
    {
        $filters = $this->values();

        $params = [
            'from'    => '',
            'where'   => '',
            'sql'     => '',
            'columns' => []
        ];

        if (!empty($filters['sortby']) && !empty($filters['order'])) {
            $params['order'] = $filters['sortby'] . ' ' . $filters['order'];
        }

        foreach ($this->filters as $filter) {
            if ($filter->value !== '') {
                $filters[0] = $filter->value;
                foreach ($filter->params as $p) {
                    if (is_callable($p[1])) {
                        $p[1] = call_user_func($p[1], $filters);
                    }

                    if (in_array($p[0], ['from', 'where', 'sql'])) {
                        $params[$p[0]] .= $p[1];
                    } elseif ($p[0] == 'columns' && is_array($p[1])) {
                        $params['columns'] = array_merge($params['columns'], $p[1]);
                    } else {
                        $params[$p[0]] = $p[1];
                    }
                }
            }
        }

        return $params;
    }

    /**
     * Show foldable filters form
     *
     * @param  boolean  $set    Force to show filter form
     *
     * @return boolean          Show filter form
     */
    public function show($set = false): bool
    {
        if ($set === true) {
            $this->show = true;
        }

        return $this->show;
    }

    /**
     * Get js filters foldable form control
     *
     * @param string $reset_url     The filter reset url
     */
    public function js(string $reset_url = '')
    {
        $var = '';
        if (!empty($reset_url)) {
            $var = dcPage::jsVars(['dotclear.filter_reset_url' => $reset_url]);
        }

        return $var . dcPage::jsFilterControl($this->show());
    }

    /**
     * Echo filter form
     *
     * @param  array|string     $adminurl   The registered adminurl
     * @param  string           $extra      The extra contents
     */
    public function display($adminurl, string $extra = '')
    {
        $tab = '';
        if (is_array($adminurl)) {
            $tab      = $adminurl[1];
            $adminurl = $adminurl[0];
        }

        echo
        '<form action="' . $this->core->adminurl->get($adminurl) . $tab . '" method="get" id="filters-form">' .
        '<h3 class="out-of-screen-if-js">' . __('Show filters and display options') . '</h3>' .

        '<div class="table">';

        $prime = true;
        $cols  = [];
        foreach ($this->filters as $filter) {
            if (in_array($filter->id, ['sortby', 'order', 'nb'])) {
                continue;
            }
            if ($filter->html != '') {
                $cols[$filter->prime ? 1 : 0][$filter->id] = sprintf('<p>%s</p>', $filter->html);
            }
        }
        sort($cols);
        foreach ($cols as $col) {
            echo sprintf(
                $prime ?
                    '<div class="cell"><h4>' . __('Filters') . '</h4>%s</div>' :
                    '<div class="cell filters-sibling-cell">%s</div>',
                implode('', $col)
            );
            $prime = false;
        }

        if (isset($this->filters['sortby']) || isset($this->filters['order']) || isset($this->filters['nb'])) {
            echo
            '<div class="cell filters-options">' .
            '<h4>' . __('Display options') . '</h4>';

            if (isset($this->filters['sortby'])) {
                $label = (new formLabel(__('Order by:'), formLabel::OUTSIDE_LABEL_BEFORE, 'sortby'))
                    ->class('ib');

                $select = (new formSelect('sortby'))
                    ->default($this->filters['sortby']->value)
                    ->items($this->filters['sortby']->options);

                echo sprintf(
                    '<p>%s</p>',
                    $label->render($select->render())
                );
            }
            if (isset($this->filters['order'])) {
                $label = (new formLabel(__('Sort:'), formLabel::OUTSIDE_LABEL_BEFORE, 'order'))
                    ->class('ib');

                $select = (new formSelect('order'))
                    ->default($this->filters['order']->value)
                    ->items($this->filters['order']->options);

                echo sprintf(
                    '<p>%s</p>',
                    $label->render($select->render())
                );
            }
            if (isset($this->filters['nb'])) {
                $label = (new formLabel($this->filters['nb']->title, formLabel::INSIDE_TEXT_AFTER, 'nb'))
                    ->class('classic');

                $number = (new formNumber('nb'))
                    ->min(0)
                    ->max(999)
                    ->value($this->filters['nb']->value);

                echo sprintf(
                    '<p><span class="label ib">' . __('Show') . '</span> %s</p>',
                    $label->render($number->render())
                );
            }

            if ($this->has_user_pref) {
                echo
                form::hidden('filters-options-id', $this->type) .
                '<p class="hidden-if-no-js"><a href="#" id="filter-options-save">' . __('Save current options') . '</a></p>';
            }
            echo
            '</div>';
        }

        echo
        '</div>' .
        '<p><input type="submit" value="' . __('Apply filters and display options') . '" />' .

        $extra .

        '<br class="clear" /></p>' . //Opera sucks
        '</form>';
    }
}

class adminPostFilter extends adminGenericFilter
{
    protected $post_type = 'post';

    public function __construct(dcCore $core, string $type = 'posts', string $post_type = '')
    {
        parent::__construct($core, $type);

        if (!empty($post_type) && array_key_exists($post_type, $core->getPostTypes())) {
            $this->post_type = $post_type;
            $this->add((new dcAdminFilter('post_type', $post_type))->param('post_type'));
        }

        $filters = new arrayObject([
            dcAdminFilters::getPageFilter(),
            $this->getPostUserFilter(),
            $this->getPostCategoriesFilter(),
            $this->getPostStatusFilter(),
            $this->getPostFormatFilter(),
            $this->getPostPasswordFilter(),
            $this->getPostSelectedFilter(),
            $this->getPostAttachmentFilter(),
            $this->getPostMonthFilter(),
            $this->getPostLangFilter(),
            $this->getPostCommentFilter(),
            $this->getPostTrackbackFilter()
        ]);

        # --BEHAVIOR-- adminPostFilter
        $core->callBehavior('adminPostFilter', $core, $filters);

        $filters = $filters->getArrayCopy();

        $this->add($filters);
    }

    /**
     * Posts users select
     */
    public function getPostUserFilter(): ?dcAdminFilter
    {
        $users = null;

        try {
            $users = $this->core->blog->getPostsUsers($this->post_type);
            if ($users->isEmpty()) {
                return null;
            }
        } catch (Exception $e) {
            $this->core->error->add($e->getMessage());

            return null;
        }

        $combo = dcAdminCombos::getUsersCombo($users);
        dcUtils::lexicalKeySort($combo);

        return (new dcAdminFilter('user_id'))
            ->param()
            ->title(__('Author:'))
            ->options(array_merge(
                ['-' => ''],
                $combo
            ))
            ->prime(true);
    }

    /**
     * Posts categories select
     */
    public function getPostCategoriesFilter(): ?dcAdminFilter
    {
        $categories = null;

        try {
            $categories = $this->core->blog->getCategories(['post_type' => $this->post_type]);
            if ($categories->isEmpty()) {
                return null;
            }
        } catch (Exception $e) {
            $this->core->error->add($e->getMessage());

            return null;
        }

        $combo = [
            '-'            => '',
            __('(No cat)') => 'NULL'
        ];
        while ($categories->fetch()) {
            $combo[
                str_repeat('&nbsp;', ($categories->level - 1) * 4) .
                html::escapeHTML($categories->cat_title) . ' (' . $categories->nb_post . ')'
            ] = $categories->cat_id;
        }

        return (new dcAdminFilter('cat_id'))
            ->param()
            ->title(__('Category:'))
            ->options($combo)
            ->prime(true);
    }

    /**
     * Posts status select
     */
    public function getPostStatusFilter(): dcAdminFilter
    {
        return (new dcAdminFilter('status'))
            ->param('post_status')
            ->title(__('Status:'))
            ->options(array_merge(
                ['-' => ''],
                dcAdminCombos::getPostStatusesCombo()
            ))
            ->prime(true);
    }

    /**
     * Posts format select
     */
    public function getPostFormatFilter(): dcAdminFilter
    {
        $core_formaters    = $this->core->getFormaters();
        $available_formats = [];
        foreach ($core_formaters as $editor => $formats) {
            foreach ($formats as $format) {
                $available_formats[$format] = $format;
            }
        }

        return (new dcAdminFilter('format'))
            ->param('where', ['adminPostFilter', 'getPostFormatParam'])
            ->title(__('Format:'))
            ->options(array_merge(
                ['-' => ''],
                $available_formats
            ))
            ->prime(true);
    }

    public static function getPostFormatParam($f)
    {
        return " AND post_format = '" . $f[0] . "' ";
    }

    /**
     * Posts password state select
     */
    public function getPostPasswordFilter(): dcAdminFilter
    {
        return (new dcAdminFilter('password'))
            ->param('where', ['adminPostFilter', 'getPostPasswordParam'])
            ->title(__('Password:'))
            ->options([
                '-'                    => '',
                __('With password')    => '1',
                __('Without password') => '0'
            ])
            ->prime(true);
    }

    public static function getPostPasswordParam($f)
    {
        return ' AND post_password IS ' . ($f[0] ? 'NOT ' : '') . 'NULL ';
    }

    /**
     * Posts selected state select
     */
    public function getPostSelectedFilter(): dcAdminFilter
    {
        return (new dcAdminFilter('selected'))
            ->param('post_selected')
            ->title(__('Selected:'))
            ->options([
                '-'                => '',
                __('Selected')     => '1',
                __('Not selected') => '0'
            ]);
    }

    /**
     * Posts attachment state select
     */
    public function getPostAttachmentFilter(): dcAdminFilter
    {
        return (new dcAdminFilter('attachment'))
            ->param('media')
            ->param('link_type', 'attachment')
            ->title(__('Attachments:'))
            ->options([
                '-'                       => '',
                __('With attachments')    => '1',
                __('Without attachments') => '0'
            ]);
    }

    /**
     * Posts by month select
     */
    public function getPostMonthFilter(): ?dcAdminFilter
    {
        $dates = null;

        try {
            $dates = $this->core->blog->getDates([
                'type'      => 'month',
                'post_type' => $this->post_type
            ]);
            if ($dates->isEmpty()) {
                return null;
            }
        } catch (Exception $e) {
            $this->core->error->add($e->getMessage());

            return null;
        }

        return (new dcAdminFilter('month'))
            ->param('post_month', ['adminPostFilter', 'getPostMonthParam'])
            ->param('post_year', ['adminPostFilter', 'getPostYearParam'])
            ->title(__('Month:'))
            ->options(array_merge(
                ['-' => ''],
                dcAdminCombos::getDatesCombo($dates)
            ));
    }

    public static function getPostMonthParam($f)
    {
        return substr($f[0], 4, 2);
    }

    public static function getPostYearParam($f)
    {
        return substr($f[0], 0, 4);
    }

    /**
     * Posts lang select
     */
    public function getPostLangFilter(): ?dcAdminFilter
    {
        $langs = null;

        try {
            $langs = $this->core->blog->getLangs(['post_type' => $this->post_type]);
            if ($langs->isEmpty()) {
                return null;
            }
        } catch (Exception $e) {
            $this->core->error->add($e->getMessage());

            return null;
        }

        return (new dcAdminFilter('lang'))
            ->param('post_lang')
            ->title(__('Lang:'))
            ->options(array_merge(
                ['-' => ''],
                dcAdminCombos::getLangsCombo($langs, false)
            ));
    }

    /**
     * Posts comments state select
     */
    public function getPostCommentFilter(): dcAdminFilter
    {
        return (new dcAdminFilter('comment'))
            ->param('where', ['adminPostFilter', 'getPostCommentParam'])
            ->title(__('Comments:'))
            ->options([
                '-'          => '',
                __('Opened') => '1',
                __('Closed') => '0'
            ]);
    }

    public static function getPostCommentParam($f)
    {
        return " AND post_open_comment = '" . $f[0] . "' ";
    }

    /**
     * Posts trackbacks state select
     */
    public function getPostTrackbackFilter(): dcAdminFilter
    {
        return (new dcAdminFilter('trackback'))
            ->param('where', ['adminPostFilter', 'getPostYearParam'])
            ->title(__('Trackbacks:'))
            ->options([
                '-'          => '',
                __('Opened') => '1',
                __('Closed') => '0'
            ]);
    }

    public static function getPostTrackbackParam($f)
    {
        return " AND post_open_tb = '" . $f[0] . "' ";
    }
}

class adminCommentFilter extends adminGenericFilter
{
    public function __construct(dcCore $core)
    {
        parent::__construct($core, 'comments');

        $filters = new arrayObject([
            dcAdminFilters::getPageFilter(),
            $this->getCommentAuthorFilter(),
            $this->getCommentTypeFilter(),
            $this->getCommentStatusFilter(),
            $this->getCommentIpFilter(),
            dcAdminFilters::getInputFilter('email', __('Email:'), 'comment_email'),
            dcAdminFilters::getInputFilter('site', __('Web site:'), 'comment_site')
        ]);

        # --BEHAVIOR-- adminCommentFilter
        $core->callBehavior('adminCommentFilter', $core, $filters);

        $filters = $filters->getArrayCopy();

        $this->add($filters);
    }

    /**
     * Comment author select
     */
    public function getCommentAuthorFilter(): dcAdminFilter
    {
        return (new dcAdminFilter('author'))
            ->param('q_author')
            ->form('input')
            ->title(__('Author:'));
    }

    /**
     * Comment type select
     */
    public function getCommentTypeFilter(): dcAdminFilter
    {
        return (new dcAdminFilter('type'))
            ->param('comment_trackback', ['adminCommentFilter', 'getCommentTypeParam'])
            ->title(__('Type:'))
            ->options([
                '-'             => '',
                __('Comment')   => 'co',
                __('Trackback') => 'tb'
            ])
            ->prime(true);
    }

    public static function getCommentTypeParam($f)
    {
        return $f[0] == 'tb';
    }

    /**
     * Comment status select
     */
    public function getCommentStatusFilter(): dcAdminFilter
    {
        return (new dcAdminFilter('status'))
            ->param('comment_status')
            ->title(__('Status:'))
            ->options(array_merge(
                ['-' => ''],
                dcAdminCombos::getCommentStatusesCombo()
            ))
            ->prime(true);
    }

    /**
     * Common IP field
     */
    public function getCommentIpFilter(): ?dcAdminFilter
    {
        if (!$this->core->auth->check('contentadmin', $this->core->blog->id)) {
            return null;
        }

        return (new dcAdminFilter('ip'))
            ->param('comment_ip')
            ->form('input')
            ->title(__('IP address:'));
    }
}

class adminUserFilter extends adminGenericFilter
{
    public function __construct(dcCore $core)
    {
        parent::__construct($core, 'users');

        $filters = new arrayObject([
            dcAdminFilters::getPageFilter(),
            dcAdminFilters::getSearchFilter()
        ]);

        # --BEHAVIOR-- adminUserFilter
        $core->callBehavior('adminUserFilter', $core, $filters);

        $filters = $filters->getArrayCopy();

        $this->add($filters);
    }
}

class adminBlogFilter extends adminGenericFilter
{
    public function __construct(dcCore $core)
    {
        parent::__construct($core, 'blogs');

        $filters = new arrayObject([
            dcAdminFilters::getPageFilter(),
            dcAdminFilters::getSearchFilter(),
            $this->getBlogStatusFilter()
        ]);

        # --BEHAVIOR-- adminBlogFilter
        $core->callBehavior('adminBlogFilter', $core, $filters);

        $filters = $filters->getArrayCopy();

        $this->add($filters);
    }

    /**
     * Blog status select
     */
    public function getBlogStatusFilter(): dcAdminFilter
    {
        return (new dcAdminFilter('status'))
            ->param('blog_status')
            ->title(__('Status:'))
            ->options(array_merge(
                ['-' => ''],
                dcAdminCombos::getBlogStatusesCombo()
            ))
            ->prime(true);
    }
}

class adminMediaFilter extends adminGenericFilter
{
    protected $post_type  = '';
    protected $post_title = '';

    public function __construct(dcCore $core, string $type = 'media')
    {
        parent::__construct($core, $type);

        $filters = new arrayObject([
            dcAdminFilters::getPageFilter(),
            dcAdminFilters::getSearchFilter(),

            $this->getPostIdFilter(),
            $this->getDirFilter(),
            $this->getFileModeFilter(),
            $this->getPluginIdFilter(),
            $this->getLinkTypeFilter(),
            $this->getPopupFilter(),
            $this->getSelectFilter()
        ]);

        # --BEHAVIOR-- adminBlogFilter
        $core->callBehavior('adminMediaFilter', $core, $filters);

        $filters = $filters->getArrayCopy();

        $this->add($filters);

        $this->legacyBehavior();
    }

    /**
     * Cope with old behavior
     */
    protected function legacyBehavior()
    {
        $values = new ArrayObject($this->values());

        $this->core->callBehavior('adminMediaURLParams', $values);

        foreach ($values->getArrayCopy() as $filter => $new_value) {
            if (isset($this->filters[$filter])) {
                $this->filters[$filter]->value($new_value);
            } else {
                $this->add($filter, $new_value);
            }
        }
    }

    protected function getPostIdFilter()
    {
        $post_id = !empty($_REQUEST['post_id']) ? (integer) $_REQUEST['post_id'] : null;
        if ($post_id) {
            $post = $this->core->blog->getPosts(['post_id' => $post_id, 'post_type' => '']);
            if ($post->isEmpty()) {
                $post_id = null;
            }
            // keep track of post_title_ and post_type without using filters
            $this->post_title = $post->post_title;
            $this->post_type  = $post->post_type;
        }

        return new dcAdminFilter('post_id', $post_id);
    }

    public function getPostTitle()
    {
        return $this->post_title;
    }

    public function getPostType()
    {
        return $this->post_type;
    }

    protected function getDirFilter()
    {
        $get = $_REQUEST['d'] ?? null;
        if ($get === null && isset($_SESSION['media_manager_dir'])) {
            // We get session information
            $get = $_SESSION['media_manager_dir'];
        }
        if ($get) {
            $_SESSION['media_manager_dir'] = $get;
        } else {
            unset($_SESSION['media_manager_dir']);
        }

        return new dcAdminFilter('d', $get);
    }

    protected function getFileModeFilter()
    {
        if (!empty($_GET['file_mode'])) {
            $_SESSION['media_file_mode'] = $_GET['file_mode'] == 'grid' ? 'grid' : 'list';
        }
        $get = !empty($_SESSION['media_file_mode']) ? $_SESSION['media_file_mode'] : 'grid';

        return new dcAdminFilter('file_mode', $get);
    }

    protected function getPluginIdFilter()
    {
        $get = isset($_REQUEST['plugin_id']) ? html::sanitizeURL($_REQUEST['plugin_id']) : '';

        return new dcAdminFilter('plugin_id', $get);
    }

    protected function getLinkTypeFilter()
    {
        $get = !empty($_REQUEST['link_type']) ? html::escapeHTML($_REQUEST['link_type']) : null;

        return new dcAdminFilter('link_type', $get);
    }

    protected function getPopupFilter()
    {
        $get = (integer) !empty($_REQUEST['popup']);

        return new dcAdminFilter('popup', $get);
    }

    protected function getSelectFilter()
    {
        // 0 : none, 1 : single media, >1 : multiple media
        $get = !empty($_REQUEST['select']) ? (integer) $_REQUEST['select'] : 0;

        return new dcAdminFilter('select', $get);
    }
}

/**
 * @brief Admin filter
 *
 * Dotclear utility class that provides reuseable list filter
 * Should be used with adminGenericFilter
 */
class dcAdminFilter
{
    /** @var array The filter properties */
    protected $properties = [
        'id'      => '',
        'value'   => null,
        'form'    => 'none',
        'prime'   => false,
        'title'   => '',
        'options' => [],
        'html'    => '',
        'params'  => []
    ];

    /**
     * Constructs a new filter.
     *
     * @param string    $id     The filter id
     * @param mixed     $value  The filter value
     */
    public function __construct(string $id, $value = null)
    {
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $id)) {
            throw new Exception('not a valid id');
        }
        $this->properties['id']    = $id;
        $this->properties['value'] = $value;
    }

    /**
     * Magic isset filter properties
     *
     * @param  string  $property    The property
     *
     * @return boolean              Is set
     */
    public function __isset(string $property)
    {
        return isset($this->properties[$property]);
    }

    /**
     * Magic get
     *
     * @param  string $property     The property
     *
     * @return mixed  Property
     */
    public function __get($property)
    {
        return $this->get($property);
    }

    /**
     * Get a filter property
     *
     * @param  string $property     The property
     *
     * @return mixed                The value
     */
    public function get(string $property)
    {
        return $this->properties[$property] ?? null;
    }

    /**
     * Magic set
     *
     * @param string $property  The property
     * @param mixed  $value     The value
     *
     * @return dcAdminFilter    The filter instance
     */
    public function __set($property, $value)
    {
        return $this->set($property, $value);
    }

    /**
     * Set a property value
     *
     * @param string $property  The property
     * @param mixed  $value     The value
     *
     * @return dcAdminFilter    The filter instance
     */
    public function set($property, $value)
    {
        if (isset($this->properties[$property]) && method_exists($this, $property)) {
            return call_user_func([$this, $property], $value);
        }

        return $this;
    }

    /**
     * Set filter form type
     *
     * @param string $type      The type
     *
     * @return dcAdminFilter    The filter instance
     */
    public function form(string $type)
    {
        if (in_array($type, ['none', 'input', 'select', 'html'])) {
            $this->properties['form'] = $type;
        }

        return $this;
    }

    /**
     * Set filter form title
     *
     * @param string $title     The title
     *
     * @return dcAdminFilter    The filter instance
     */
    public function title(string $title)
    {
        $this->properties['title'] = $title;

        return $this;
    }

    /**
     * Set filter form options
     *
     * If filter form is a select box, this is the select options
     *
     * @param array     $options    The options
     * @param boolean   $set_form   Auto set form type
     *
     * @return dcAdminFilter        The filter instance
     */
    public function options(array $options, $set_form = true)
    {
        $this->properties['options'] = $options;
        if ($set_form) {
            $this->form('select');
        }

        return $this;
    }

    /**
     * Set filter value
     *
     * @param mixed $value      The value
     *
     * @return dcAdminFilter    The filter instance
     */
    public function value($value)
    {
        $this->properties['value'] = $value;

        return $this;
    }

    /**
     * Set filter column in form
     *
     * @param boolean $prime    First column
     *
     * @return dcAdminFilter    The filter instance
     */
    public function prime(bool $prime)
    {
        $this->properties['prime'] = $prime;

        return $this;
    }

    /**
     * Set filter html contents
     *
     * @param string    $contents   The contents
     * @param boolean   $set_form   Auto set form type
     *
     * @return dcAdminFilter        The filter instance
     */
    public function html(string $contents, $set_form = true)
    {
        $this->properties['html'] = $contents;
        if ($set_form) {
            $this->form('html');
        }

        return $this;
    }

    /**
     * Set filter param (list query param)
     *
     * @param  string|null           $name  The param name
     * @param  mixed                 $value The param value
     *
     * @return dcAdminFilter         The filter instance
     */
    public function param($name = null, $value = null)
    {
        # filter id as param name
        if ($name === null) {
            $name = $this->properties['id'];
        }
        # filter value as param value
        if (null === $value) {
            $value = function ($f) { return $f[0]; };
        }
        $this->properties['params'][] = [$name, $value];

        return $this;
    }

    /**
     * Parse the filter properties
     *
     * Only input and select forms are parsed
     */
    public function parse()
    {
        # form select
        if ($this->form == 'select') {
            # _GET value
            if ($this->value === null) {
                $get = $_GET[$this->id] ?? '';
                if ($get === '' || !in_array($get, $this->options, true)) {
                    $get = '';
                }
                $this->value($get);
            }
            # HTML field
            $select = (new formSelect($this->id))
                ->default($this->value)
                ->items($this->options);

            $label = (new formLabel($this->title, 2, $this->id))
                ->class('ib');

            $this->html($label->render($select->render()), false);

        # form input
        } elseif ($this->form == 'input') {
            # _GET value
            if ($this->value === null) {
                $this->value(!empty($_GET[$this->id]) ? $_GET[$this->id] : '');
            }
            # HTML field
            $input = (new formInput($this->id))
                ->size(20)
                ->maxlength(255)
                ->value($this->value);

            $label = (new formLabel($this->title, 2, $this->id))
                ->class('ib');

            $this->html($label->render($input->render()), false);
        }
    }
}

/**
 * @brief Admin list filters library
 *
 * Dotclear utility class that provides reuseable list filters
 * Returned null or dcAdminFilter instance
 * Should be used with adminGenericFilter
 */
class dcAdminFilters
{
    /**
     * Common default input field
     */
    public static function getInputFilter(string $id, string $title, ?string $param = null): dcAdminFilter
    {
        return (new dcAdminFilter($id))
            ->param($param ?: $id)
            ->form('input')
            ->title($title);
    }

    /**
     * Common default select field
     */
    public static function getSelectFilter(string $id, string $title, array $options, ?string $param = null): ?dcAdminFilter
    {
        if (empty($options)) {
            return null;
        }

        return (new dcAdminFilter($id))
            ->param($param ?: $id)
            ->title($title)
            ->options($options);
    }

    /**
     * Common page filter (no field)
     */
    public static function getPageFilter(string $id = 'page'): dcAdminFilter
    {
        return (new dcAdminFilter($id))
            ->value(!empty($_GET[$id]) ? max(1, (integer) $_GET[$id]) : 1)
            ->param('limit', function ($f) { return [(($f[0] - 1) * $f['nb']), $f['nb']]; });
    }

    /**
     * Common search field
     */
    public static function getSearchFilter(): dcAdminFilter
    {
        return (new dcAdminFilter('q'))
            ->param('q', function ($f) { return $f['q']; })
            ->form('input')
            ->title(__('Search:'))
            ->prime(true);
    }
}
