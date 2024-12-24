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

use Dotclear\App;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;

class Menu
{
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
     * List of indexed links
     *
     * @var array<string,string>
     */
    protected $links = [];

    /**
     * Constructs a new instance.
     *
     * @param      string  $id         The menu identifier
     * @param      string  $title      The menu title
     */
    public function __construct(
        private string $id,
        public string $title
    ) {
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
            $this->links[$title] = $url;
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
            $this->links[$title] = $url;
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

        $lines = [];
        if (count($this->pinned)) {
            // 1. Pinned items (unsorted)
            foreach ($this->pinned as $item) {
                $lines[] = (new Text(null, $item));
            }
        }
        if (count($this->items)) {
            // 2. Unpinned items (sorted)
            $items = $this->items;
            App::lexical()->lexicalKeySort($items, App::lexical()::ADMIN_LOCALE);
            foreach ($items as $item) {
                $lines[] = (new Text(null, $item));
            }
        }

        return (new Div())
            ->id($this->id)
            ->items([
                $this->title ? (new Text('h3', $this->title)) : (new None()),
                (new Ul())
                    ->items($lines),
            ])
        ->render();
    }

    /**
     * Get a menu item HTML code
     *
     * @param      string       $title   The title
     * @param      string       $url     The url
     * @param      mixed        $img     The image(s), string (default) or array (0 : light, 1 : dark)
     * @param      mixed        $active  The active flag
     * @param      null|string  $id      The identifier
     * @param      null|string  $class   The class
     *
     * @return     string
     */
    protected function itemDef(string $title, string $url, $img, $active, ?string $id = null, ?string $class = null): string
    {
        // Menu link
        $link = (new Link())
            ->href($url)
            ->text(Helper::adminIcon($img) . $title);
        if ($id) {
            $link->id('menu-process-' . $id);
        }
        if ($active) {
            $link->extra('aria-current="page"');
        }

        // Menu list item
        $code = (new Li())
            ->items([
                $link,
            ]);
        if ($id) {
            $code->id('menu-item-' . $id);
        }
        $classes = [];
        if ($class) {
            $classes[] = $class;
        }
        if ($active) {
            $classes[] = 'active';
        }
        $classes = array_filter($classes);  // @phpstan-ignore-line
        if (count($classes)) {
            $code->class($classes);
        }

        return $code->render();
    }

    /**
     * Find a menuitem corresponding with a term (or including the term)
     *
     * @param      string             $term   The term
     * @param      bool               $exact  Should find the exact term? (case insensitive)
     *
     * @return     false|string
     */
    public function searchMenuitem(string $term, bool $exact): false|string
    {
        foreach ($this->links as $title => $link) {
            if ($exact && (strtolower($title) === strtolower($term))) {
                return $link;
            }
            if (str_contains(strtolower($title), strtolower($term))) {
                return $link;
            }
        }

        return false;
    }

    /**
     * Get list of menuitems in menu
     *
     * @return     array<int, string>
     */
    public function listMenus(): array
    {
        return array_keys($this->links);
    }
}
