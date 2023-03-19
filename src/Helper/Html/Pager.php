<?php
/**
 * @class Pager
 * @brief Implements a pager helper to browse any type of results
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Helper\Html;

class Pager
{
    /**
     * Current page index
     *
     * @var int
     */
    protected $env;

    /**
     * Total number of elements
     *
     * @var int
     */
    protected $nb_elements;

    /**
     * Number of elements per page
     *
     * @var int
     */
    protected $nb_per_page;

    /**
     * Number of pages per group
     *
     * @var int
     */
    protected $nb_pages_per_group;

    /**
     * Total number of pages
     *
     * @var int
     */
    protected $nb_pages;

    /**
     * Total number of grourps
     *
     * @var int
     */
    protected $nb_groups;

    /**
     * Current group index
     *
     * @var int
     */
    protected $env_group;

    /**
     * First page index of current group
     *
     * @var int
     */
    protected $index_group_start;

    /**
     * Last page index of current group
     *
     * @var int
     */
    protected $index_group_end;

    /**
     * Page URI
     *
     * @var string|null
     */
    protected $page_url = null;

    /**
     * First element index of current page
     *
     * @var int
     */
    public $index_start;

    /**
     * Last element index of current page
     *
     * @var int
     */
    public $index_end;

    /**
     * Base URI
     *
     * @var string|null
     */
    public $base_url = null;

    /**
     * GET param name for current page
     *
     * @var string
     */
    public $var_page = 'page';

    /**
     * Current page format (HTML)
     *
     * @var string
     */
    public $html_cur_page = '<strong>%s</strong>';

    /**
     * Link separator
     *
     * @var string
     */
    public $html_link_sep = '-';

    /**
     * Previous HTML code
     *
     * @var string
     */
    public $html_prev = '&#171;prev.';

    /**
     * Next HTML code
     *
     * @var string
     */
    public $html_next = 'next&#187;';

    /**
     * Next group HTML code
     *
     * @var string
     */
    public $html_prev_grp = '...';

    /**
     * Previous group HTML code
     *
     * @var string
     */
    public $html_next_grp = '...';

    /**
     * Constructor
     *
     * @param int    $current_page          Current page index
     * @param int    $nb_elements           Total number of elements
     * @param int    $nb_per_page           Number of items per page
     * @param int    $nb_pages_per_group    Number of pages per group
     */
    public function __construct(int $current_page, int $nb_elements, int $nb_per_page = 10, int $nb_pages_per_group = 10)
    {
        $this->env                = abs($current_page);
        $this->nb_elements        = abs($nb_elements);
        $this->nb_per_page        = abs($nb_per_page);
        $this->nb_pages_per_group = abs($nb_pages_per_group);

        // Pages count
        $this->nb_pages = (int) ceil($this->nb_elements / $this->nb_per_page);

        // Fix env value
        if ($this->env > $this->nb_pages || $this->env < 1) {
            $this->env = 1;
        }

        // Groups count
        $this->nb_groups = (int) ceil($this->nb_pages / $this->nb_pages_per_group);

        // Page first element index
        $this->index_start = ($this->env - 1) * $this->nb_per_page;

        // Page last element index
        $this->index_end = $this->index_start + $this->nb_per_page - 1;
        if ($this->index_end >= $this->nb_elements) {
            $this->index_end = $this->nb_elements - 1;
        }

        // Current group
        $this->env_group = (int) ceil($this->env / $this->nb_pages_per_group);

        // Group first page index
        $this->index_group_start = ($this->env_group - 1) * $this->nb_pages_per_group + 1;

        // Group last page index
        $this->index_group_end = $this->index_group_start + $this->nb_pages_per_group - 1;
        if ($this->index_group_end > $this->nb_pages) {
            $this->index_group_end = $this->nb_pages;
        }
    }

    /**
     * Pager Links
     *
     * Returns pager links
     *
     * @return string
     */
    public function getLinks(): string
    {
        $htmlLinks   = '';
        $htmlPrev    = '';
        $htmlNext    = '';
        $htmlPrevGrp = '';
        $htmlNextGrp = '';

        $this->setURL();

        for ($i = $this->index_group_start; $i <= $this->index_group_end; $i++) {
            if ($i === $this->env) {
                $htmlLinks .= sprintf($this->html_cur_page, $i);
            } else {
                $htmlLinks .= '<a href="' . sprintf($this->page_url, $i) . '">' . $i . '</a>';
            }

            if ($i !== $this->index_group_end) {
                $htmlLinks .= $this->html_link_sep;
            }
        }

        # Previous page
        if ($this->env !== 1) {
            $htmlPrev = '<a href="' . sprintf($this->page_url, $this->env - 1) . '">' . $this->html_prev . '</a>&nbsp;';
        }

        # Next page
        if ($this->env !== $this->nb_pages) {
            $htmlNext = '&nbsp;<a href="' . sprintf($this->page_url, $this->env + 1) . '">' . $this->html_next . '</a>';
        }

        # Previous group
        if ($this->env_group != 1) {
            $htmlPrevGrp = '&nbsp;<a href="' . sprintf($this->page_url, $this->index_group_start - $this->nb_pages_per_group) . '">' . $this->html_prev_grp . '</a>&nbsp;';
        }

        # Next group
        if ($this->env_group != $this->nb_groups) {
            $htmlNextGrp = '&nbsp;<a href="' . sprintf($this->page_url, $this->index_group_end + 1) . '">' . $this->html_next_grp . '</a>&nbsp;';
        }

        $res = $htmlPrev .
            $htmlPrevGrp .
            $htmlLinks .
            $htmlNextGrp .
            $htmlNext;

        return $this->nb_elements > 0 ? $res : '';
    }

    /**
     * Sets the page URI
     */
    protected function setURL()
    {
        if ($this->base_url !== null) {
            $this->page_url = $this->base_url;

            return;
        }

        $url = (string) $_SERVER['REQUEST_URI'];

        # Removing session information
        if (session_id()) {
            $url = preg_replace('/' . preg_quote(session_name() . '=' . session_id(), '/') . '([&]?)/', '', $url);
            $url = preg_replace('/&$/', '', $url);
        }

        # Escape page_url for sprintf
        $url = str_replace('%', '%%', $url);

        # Changing page ref
        if (preg_match('/[?&]' . $this->var_page . '=\d+/', $url)) {
            $url = preg_replace('/([?&]' . $this->var_page . '=)\d+/', '$1%1$d', $url);
        } elseif (preg_match('/[\?]/', $url)) {
            $url .= '&' . $this->var_page . '=%1$d';
        } else {
            $url .= '?' . $this->var_page . '=%1$d';
        }

        $this->page_url = $url;
    }

    public function debug()
    {
        return [
            $this->nb_per_page,
            $this->nb_pages_per_group,
            $this->nb_elements,
            $this->nb_pages,
            $this->nb_groups,
            $this->env,
            $this->index_start,
            $this->index_end,
            $this->env_group,
            $this->index_group_start,
            $this->index_group_end,
            $this->page_url,
        ];
    }
}
