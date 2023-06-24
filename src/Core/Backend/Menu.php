<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend;

use dcAdminHelper;
use dcUtils;

class Menu
{
    /**
     * Menu id
     */
    private string $id;

    /**
     * List of items pinned at top of menu
     *
     * @var array<int,string>
     */
    protected $pinned = [];

    /**
     * List of unpinned items
     *
     * @var array<string,string>
     */
    protected $items = [];

    /**
     * Menu title
     *
     * @var string
     */
    public $title;

    /**
     * Constructs a new instance.
     *
     * @param      string  $id         The identifier
     * @param      string  $title      The title
     */
    public function __construct(string $id, string $title)
    {
        $this->id    = $id;
        $this->title = $title;
    }

    /**
     * Adds an item.
     *
     * @param      string       $title   The title
     * @param      string       $url     The url
     * @param      mixed        $img     The image(s)
     * @param      mixed        $active  The active flag
     * @param      bool         $show    The show flag
     * @param      null|string  $id      The identifier
     * @param      null|string  $class   The class
     * @param      bool         $pinned  The pinned flag
     */
    public function addItem(string $title, string $url, $img, $active, bool $show = true, ?string $id = null, ?string $class = null, bool $pinned = false): void
    {
        if ($show) {
            $item = $this->itemDef($title, $url, $img, $active, $id, $class);
            if ($pinned) {
                $this->pinned[] = $item;
            } else {
                $this->items[$title] = $item;
            }
        }
    }

    /**
     * Prepends an item.
     *
     * @param      string       $title   The title
     * @param      string       $url     The url
     * @param      mixed        $img     The image(s)
     * @param      mixed        $active  The active flag
     * @param      bool         $show    The show flag
     * @param      null|string  $id      The identifier
     * @param      null|string  $class   The class
     * @param      bool         $pinned  The pinned flag
     */
    public function prependItem(string $title, string $url, $img, $active, bool $show = true, ?string $id = null, ?string $class = null, bool $pinned = false): void
    {
        if ($show) {
            $item = $this->itemDef($title, $url, $img, $active, $id, $class);
            if ($pinned) {
                array_unshift($this->pinned, $item);
            } else {
                $this->items[$title] = $item;
            }
        }
    }

    /**
     * Draw a menu
     *
     * @return     string
     */
    public function draw(): string
    {
        if (count($this->items) + count($this->pinned) === 0) {
            return '';
        }

        $res = '<div id="' . $this->id . '">' . ($this->title ? '<h3>' . $this->title . '</h3>' : '') . '<ul>' . "\n";

        // 1. Display pinned items (unsorted)
        foreach ($this->pinned as $item) {
            $res .= $item . "\n";
        }

        // 2. Display unpinned items (sorted)
        $items = $this->items;
        dcUtils::lexicalKeySort($items, dcUtils::ADMIN_LOCALE);
        foreach ($items as $item) {
            $res .= $item . "\n";
        }

        $res .= '</ul></div>' . "\n";

        return $res;
    }

    /**
     * Get a menu item HTML code
     *
     * @param      string       $title   The title
     * @param      string       $url     The url
     * @param      mixed        $img     The image(s)
     * @param      mixed        $active  The active flag
     * @param      null|string  $id      The identifier
     * @param      null|string  $class   The class
     *
     * @return     string
     */
    protected function itemDef(string $title, string $url, $img, $active, ?string $id = null, ?string $class = null): string
    {
        return
        '<li' . (($active || $class) ? ' class="' . (($active) ? 'active ' : '') . ($class ?? '') . '"' : '') . (($id) ? ' id="menu-item-' . $id . '"' : '') . '>' .
        '<a href="' . $url . '"' . ($active ? ' aria-current="page"' : '') . ($id ? 'id="menu-process-' . $id . '"' : '') . '>' . dcAdminHelper::adminIcon($img) . $title . '</a>' .
        '</li>' . "\n";
    }
}
