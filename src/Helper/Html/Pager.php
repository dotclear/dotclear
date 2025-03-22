<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Html;

/**
 * @class Pager
 * @brief Implements a pager helper to browse any type of results
 */
class Pager
{
    /**
     * Current page index
     */
    protected int $env;

    /**
     * Total number of elements
     */
    protected int $nb_elements;

    /**
     * Number of elements per page
     */
    protected int $nb_per_page;

    /**
     * Number of pages per group
     */
    protected int $nb_pages_per_group;

    /**
     * Total number of pages
     */
    protected int $nb_pages;

    /**
     * Total number of groups
     */
    protected int $nb_groups;

    /**
     * Current group index
     */
    protected int $env_group;

    /**
     * First page index of current group
     */
    protected int $index_group_start;

    /**
     * Last page index of current group
     */
    protected int $index_group_end;

    /**
     * Page URI
     */
    protected ?string $page_url = null;

    /**
     * First element index of current page
     */
    public int $index_start;

    /**
     * Last element index of current page
     */
    public int $index_end;

    /**
     * Base URI
     */
    public ?string $base_url = null;

    /**
     * URI fragment
     */
    public string $fragment = '';

    /**
     * GET param name for current page
     */
    public string $var_page = 'page';

    /**
     * Current page format (HTML)
     */
    public string $html_cur_page = '<strong>%s</strong>';

    /**
     * Link separator
     */
    public string $html_link_sep = '-';

    /**
     * Previous HTML code
     */
    public string $html_prev = '&#171;prev.';

    /**
     * Next HTML code
     */
    public string $html_next = 'next&#187;';

    /**
     * Next group HTML code
     */
    public string $html_prev_grp = '...';

    /**
     * Previous group HTML code
     */
    public string $html_next_grp = '...';

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
                $htmlLinks .= '<a href="' . sprintf((string) $this->page_url, $i) . '">' . $i . '</a>';
            }

            if ($i !== $this->index_group_end) {
                $htmlLinks .= $this->html_link_sep;
            }
        }

        # Previous page
        if ($this->env !== 1) {
            $htmlPrev = '<a href="' . sprintf((string) $this->page_url, $this->env - 1) . '">' . $this->html_prev . '</a>&nbsp;';
        }

        # Next page
        if ($this->env !== $this->nb_pages) {
            $htmlNext = '&nbsp;<a href="' . sprintf((string) $this->page_url, $this->env + 1) . '">' . $this->html_next . '</a>';
        }

        # Previous group
        if ($this->env_group != 1) {
            $htmlPrevGrp = '&nbsp;<a href="' . sprintf((string) $this->page_url, $this->index_group_start - $this->nb_pages_per_group) . '">' . $this->html_prev_grp . '</a>&nbsp;';
        }

        # Next group
        if ($this->env_group !== $this->nb_groups) {
            $htmlNextGrp = '&nbsp;<a href="' . sprintf((string) $this->page_url, $this->index_group_end + 1) . '">' . $this->html_next_grp . '</a>&nbsp;';
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
    protected function setURL(): void
    {
        if ($this->base_url !== null) {
            $this->page_url = $this->base_url;

            return;
        }

        $url = (string) $_SERVER['REQUEST_URI'];

        # Removing session information
        if (session_id()) {
            $url = (string) preg_replace('/' . preg_quote(session_name() . '=' . session_id(), '/') . '([&]?)/', '', $url);
            $url = (string) preg_replace('/&$/', '', $url);
        }

        # Escape page_url for sprintf
        $url = str_replace('%', '%%', $url);

        # Changing page ref
        if (preg_match('/[?&]' . $this->var_page . '=\d+/', $url)) {
            $url = (string) preg_replace('/([?&]' . $this->var_page . '=)\d+/', '$1%1$d', $url);
        } elseif (preg_match('/[\?]/', $url)) {
            $url .= '&' . $this->var_page . '=%1$d';
        } else {
            $url .= '?' . $this->var_page . '=%1$d';
        }

        # Cope with uri fragment (limit to HTML id attribute)
        if (preg_match('/^[A-Za-z0-9-_]+$/', $this->fragment)) {
            $url .= '#' . $this->fragment;
        }

        $this->page_url = $url;
    }

    /**
     * Return current properties values (for debug purpose)
     *
     * @return     array<mixed>
     */
    public function debug(): array
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
