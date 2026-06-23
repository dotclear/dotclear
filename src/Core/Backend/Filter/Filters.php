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

use Dotclear\App;
use Dotclear\Core\Backend\UserPrefFilter;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Number;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Stack\Filter;

/**
 * @brief   Generic class for admin list filters form.
 *
 * @since   2.20
 */
class Filters
{
    /**
     * Filters objects.
     *
     * @var     array<string, Filter>    $filters
     */
    protected $filters = [];

    /**
     * Show filter indicator.
     *
     * @var     bool    $show
     */
    protected $show = false;

    /**
     * Has user preferences.
     *
     * @var     bool    $has_user_pref
     */
    protected $has_user_pref = false;

    /**
     * Constructs a new instance.
     *
     * @param   string  $type   The filter form main id
     */
    public function __construct(
        protected string $type
    ) {
        $this->parseOptions();
    }

    /**
     * Get user defined filter options (sortby, order, nb).
     *
     * @deprecated since 2.39 use userOptionSortby(), userOptionOrder() or userOptionNb() instead
     *
     * @param   string  $option     The option
     *
     * @return  string|int|array<array-key, mixed>|null   User option
     */
    public function userOptions(?string $option = null): string|int|array|null
    {
        return App::backend()->userPref()->getUserFilters($this->type, $option);
    }

    /**
     * Get user defined filter sortby option.
     */
    public function userOptionSortby(): string
    {
        return App::backend()->userPref()->getUserFilterSortBy($this->type) ?? '';
    }

    /**
     * Get user defined filter order option.
     */
    public function userOptionOrder(): string
    {
        return App::backend()->userPref()->getUserFilterOrder($this->type) ?? '';
    }

    /**
     * Get user defined filter nb option.
     */
    public function userOptionNb(): int
    {
        return App::backend()->userPref()->getUserFilterNb($this->type) ?? 0;
    }

    /**
     * Parse _GET user pref options (sortby, order, nb).
     */
    protected function parseOptions(): void
    {
        $filter = App::backend()->userPref()->getUserFilter($this->type, true);

        if ($filter instanceof UserPrefFilter) {
            $this->has_user_pref = true;

            // Cope with sortby
            if ($filter->getSortBy() !== null) {
                $this->filters['sortby'] = new Filter('sortby', $this->userOptionSortby());
                $this->filters['sortby']->options($filter->getOptions() ?? []);

                $sortby = isset($_GET['sortby']) && is_string($sortby = $_GET['sortby']) ? $sortby : '';
                if ($sortby !== ''
                    && $filter->findOption($sortby)
                    && $sortby !== $this->userOptionSortby()
                ) {
                    $this->show(true);
                    $this->filters['sortby']->value($sortby);
                }
            }

            // Cope with order
            if ($filter->getOrder() !== null) {
                $this->filters['order'] = new Filter('order', $this->userOptionOrder());
                $this->filters['order']->options(App::backend()->combos()->getOrderCombo());

                $order = isset($_GET['order']) && is_string($order = $_GET['order']) ? $order : '';
                if ($order !== ''
                    && in_array($order, App::backend()->combos()->getOrderCombo(), true)
                    && $order !== $this->userOptionOrder()
                ) {
                    $this->show(true);
                    $this->filters['order']->value($order);
                }
            }

            // Cope with number
            if ($filter->getNb() !== null) {
                $this->filters['nb'] = new Filter('nb', $this->userOptionNb());
                $this->filters['nb']->title($filter->getNbLabel() ?? '');

                $nb = isset($_GET['nb']) && is_numeric($nb = $_GET['nb']) ? (int) $nb : 0;
                if ($nb > 0
                    && $nb !== $this->userOptionNb()
                ) {
                    $this->show(true);
                    $this->filters['nb']->value($nb);
                }
            }
        }
    }

    /**
     * Get filters key/value pairs.
     *
     * @param   bool    $escape     Escape widlcard %
     * @param   bool    $ui_only    Limit to filters with ui
     *
     * @return  array<string, mixed>     The filters
     */
    public function values(bool $escape = false, bool $ui_only = false): array
    {
        $res = [];
        foreach ($this->filters as $id => $filter) {
            if ($ui_only) {
                if (in_array($id, ['sortby', 'order', 'nb']) || $filter->getHtml() !== '') {
                    $res[$id] = $filter->getValue();
                }
            } else {
                $res[$id] = $filter->getValue();
            }
        }

        if ($escape) {
            foreach ($res as &$value) {
                if (is_string($value)) {
                    $value = str_replace('%', '%%', $value);
                }
            }
        }

        return $res;
    }

    /**
     * Get a filter value.
     *
     * @param   string          $id         The filter id
     * @param   null|string     $undefined  The filter value if not exists
     *
     * @return  mixed   The filter value
     */
    public function value(string $id, ?string $undefined = null)
    {
        return isset($this->filters[$id]) ? $this->filters[$id]->getValue() : $undefined;
    }

    /**
     * Magic get filter value.
     *
     * @param   string  $id     The filter id
     *
     * @return  mixed   The filter value
     */
    public function __get(string $id): mixed
    {
        return $this->value($id);
    }

    /**
     * Add filter(s).
     *
     * @param   array<int, string|Filter>|string|Filter|null    $filter     The filter(s) array or id or object
     * @param   mixed                                           $value      The filter value if $filter is id
     *
     * @return  mixed   The filter value
     */
    public function add($filter = null, $value = null)
    {
        # empty filter (ex: do not show form if there are no categories on a blog)
        if (!$filter) {
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
            $filter = new Filter($filter, $value);
        }

        # not well formed filter or reserved id
        if (!$filter->getId()) {
            return null;
        }

        # parse _GET values and create html forms
        $filter->parse();

        # set key/value pair
        $this->filters[$filter->getId()] = $filter;

        # has contents
        if ($filter->getHtml() !== '' && $filter->getFormType() !== 'none') {
            # not default value = show filters form
            $this->show($filter->getValue() !== '');
        }

        return $filter->getValue();
    }

    /**
     * Remove a filter.
     *
     * @param   string  $id     The filter id
     *
     * @return  bool    The success
     */
    public function remove(string $id): bool
    {
        if (array_key_exists($id, $this->filters)) {
            unset($this->filters[$id]);

            return true;
        }

        return false;
    }

    /**
     * Get list query params.
     *
     * @return  array<string, mixed>     The query params
     */
    public function params(): array
    {
        $filters = $this->values();

        /**
         * @var array{from: string, where: string, sql: string, columns: string[]}  $params
         */
        $params = [
            'from'    => '',
            'where'   => '',
            'sql'     => '',
            'columns' => [],
        ];

        if (!empty($filters['sortby'])
            && is_string($filters['sortby'])
            && !empty($filters['order'])
            && is_string($filters['order'])
        ) {
            $params['order'] = $filters['sortby'] . ' ' . $filters['order'];
        }

        foreach ($this->filters as $filter) {
            if ($filter->getValue() !== '') {
                $filters[0] = $filter->getValue();
                foreach ($filter->getParams() as $p) {
                    $key = is_string($p[0]) ? $p[0] : '';
                    if ($key !== '') {
                        if (is_callable($p[1])) {
                            $p[1] = call_user_func($p[1], $filters);
                        }

                        if (in_array($key, ['from', 'where', 'sql'])
                            && is_string($params[$key])
                            && is_string($p[1])
                        ) {
                            $params[$key] .= $p[1];
                        } elseif ($key === 'columns'
                            && is_array($params['columns'])
                            && is_array($p[1])
                        ) {
                            $params['columns'] = array_merge($params['columns'], $p[1]);
                        } else {
                            $params[$key] = $p[1];
                        }
                    }
                }
            }
        }

        return $params;
    }

    /**
     * Show foldable filters form.
     *
     * @param   bool    $set    Force to show filter form
     */
    public function show(bool $set = false): bool
    {
        if ($set) {
            $this->show = true;
        }

        return $this->show;
    }

    /**
     * Get js filters foldable form control.
     *
     * @param   string  $reset_url  The filter reset url
     */
    public function js(string $reset_url = ''): string
    {
        $var = $reset_url === '' ? '' : App::backend()->page()->jsJson('filter_reset_url', $reset_url);

        return $var . App::backend()->page()->jsFilterControl($this->show());
    }

    /**
     * Echo filter form.
     *
     * @param   list{0:string, 1:string}|string     $adminurl   The registered adminurl
     * @param   string                              $extra      The extra contents
     */
    public function display($adminurl, string $extra = ''): void
    {
        $tab = '';
        if (is_array($adminurl)) {
            $tab      = $adminurl[1];
            $adminurl = $adminurl[0];
        }

        $hiddens = [];
        foreach (App::backend()->url()->getParams($adminurl) as $key => $value) {
            if (is_scalar($value)) {
                $hiddens[] = (new Hidden($key, (string) $value));
            }
        }

        $prime   = true;
        $columns = [];
        foreach ($this->filters as $filter) {
            if (in_array($filter->getId(), ['sortby', 'order', 'nb'])) {
                continue;
            }
            if ($filter->getHtml() !== '') {
                $columns[$filter->getPrime() ? 1 : 0][$filter->getId()] = $filter->getHtml();
            }
        }

        sort($columns);
        $filters = [];
        foreach ($columns as $column) {
            $items = [];
            foreach ($column as $item) {
                $items[] = (new Note())->text($item);
            }
            $filters[] = (new Div())
                ->class(array_filter(['cell', $prime ? '' : 'filters-sibling-cell']))
                ->items([
                    $prime ?
                        (new Text('h4', __('Filters'))) :
                        (new None()),
                    ...$items,
                ]);
            $prime = false;
        }

        $options = (new None());
        if (isset($this->filters['sortby']) || isset($this->filters['order']) || isset($this->filters['nb'])) {
            $items = [];
            if (isset($this->filters['sortby'])) {
                $default = $this->filters['sortby']->getValue();
                $default = is_scalar($default) ? (string) $default : null;
                $items[] = (new Para())
                    ->items([
                        (new Label(__('Order by:'), Label::OL_TF, 'sortby'))
                            ->class('ib'),
                        (new Select('sortby'))
                            ->default($default)
                            ->items($this->filters['sortby']->getOptions()),
                    ]);
            }

            if (isset($this->filters['order'])) {
                $default = $this->filters['order']->getValue();
                $default = is_scalar($default) ? (string) $default : null;
                $items[] = (new Para())
                    ->items([
                        (new Label(__('Sort:'), Label::OL_TF, 'order'))
                            ->class('ib'),
                        (new Select('order'))
                            ->default($default)
                            ->items($this->filters['order']->getOptions()),
                    ]);
            }

            if (isset($this->filters['nb'])) {
                $default = $this->filters['nb']->getValue();
                $default = is_numeric($default) ? (int) $default : null;
                $items[] = (new Para())
                    ->items([
                        (new Number('nb', 0, 999, $default))
                            ->label((new Label(__('Show'), Label::IL_TF))
                                ->suffix($this->filters['nb']->getTitle())
                                ->class(['ib', 'classic'])),
                    ]);
            }

            if ($this->has_user_pref) {
                $items[] = (new Set())
                    ->items([
                        (new Para())
                            ->class('hidden-if-no-js')
                            ->items([
                                (new Link('filter-options-save'))
                                    ->href('#')
                                    ->text(__('Save current options')),
                            ]),
                        (new Hidden('filters-options-id', $this->type)),
                    ]);
            }

            $options = (new Div())
                ->class(['cell', 'filters-options'])
                ->items([
                    (new Text('h4', __('Display options'))),
                    ...$items,
                ]);
        }

        echo (new Form('filters-form'))
            ->method('get')
            ->action(App::backend()->url()->get($adminurl) . $tab)
            ->fields([
                (new Text('h3', __('Show filters and display options')))
                    ->class('out-of-screen-if-js'),
                (new Div())
                    ->class('table')
                    ->items([
                        ...$filters,
                        $options,
                    ]),
                (new Para())
                    ->items([
                        (new Submit('apply-filters-opts', __('Apply filters and display options'))),
                    ]),
                (new Text(null, $extra)),
                (new Set())
                    ->items($hiddens),
            ])
        ->render();
    }
}
