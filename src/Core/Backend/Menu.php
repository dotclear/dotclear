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
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;

class Menu
{
    /**
     * List of items pinned at top of menu
     *
     * @var MenuItem[]            $pinned
     */
    protected array $pinned = [];

    /**
     * List of unpinned items
     *
     * @var array<string, MenuItem>    $items
     */
    protected array $items = [];

    /**
     * List of indexed links
     *
     * @var array<string, string>    $links
     */
    protected array $links = [];

    /**
     * Constructs a new instance.
     *
     * @param      string  $id         The menu identifier
     * @param      string  $title      The menu title
     */
    public function __construct(private readonly string $id, public string $title)
    {
    }

    /**
     * Adds an item (add the item at the end of the menu).
     *
     * @param      string               $title   The title
     * @param      string               $url     The url
     * @param      string|string[]|Icon $img     The image(s)
     * @param      bool                 $active  The active flag
     * @param      bool                 $show    The show flag
     * @param      null|string          $id      The identifier
     * @param      null|string          $class   The class
     * @param      bool                 $pinned  The pinned flag
     */
    public function addItem(string $title, string $url, string|array|Icon $img, bool $active, bool $show = true, ?string $id = null, ?string $class = null, bool $pinned = false): void
    {
        if ($show) {
            $item = new MenuItem($title, $url, $img, $active, $id, $class);
            if ($pinned) {
                $this->pinned[] = $item;
            } else {
                $this->items[$title] = $item;
            }

            $this->links[$title] = $url;
        }
    }

    /**
     * Prepends an item (insert an item at the beginning of the menu).
     *
     * @param      string               $title   The title
     * @param      string               $url     The url
     * @param      string|string[]|Icon $img     The image(s)
     * @param      bool                 $active  The active flag
     * @param      bool                 $show    The show flag
     * @param      null|string          $id      The identifier
     * @param      null|string          $class   The class
     * @param      bool                 $pinned  The pinned flag
     */
    public function prependItem(string $title, string $url, string|array|Icon $img, bool $active, bool $show = true, ?string $id = null, ?string $class = null, bool $pinned = false): void
    {
        if ($show) {
            $item = new MenuItem($title, $url, $img, $active, $id, $class);
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
     */
    public function draw(): string
    {
        if (count($this->items) + count($this->pinned) === 0) {
            return '';
        }

        $lines = [];
        // 1. Pinned items (unsorted)
        foreach ($this->pinned as $item) {
            $lines[] = $item->getComponent();
        }

        if ($this->items !== []) {
            // 2. Unpinned items (sorted)
            $items = $this->items;
            App::lexical()->lexicalKeySort($items, App::lexical()::ADMIN_LOCALE);
            foreach ($items as $item) {
                $lines[] = $item->getComponent();
            }
        }

        return (new Div())
            ->id($this->id)
            ->items([
                $this->title !== '' ? (new Text('h3', $this->title)) : (new None()),
                (new Ul())
                    ->items($lines),
            ])
        ->render();
    }

    /**
     * Find a menuitem corresponding with a term (or including the term)
     *
     * @param      string             $term   The term
     * @param      bool               $exact  Should find the exact term? (case insensitive)
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
     * @return     string[]
     */
    public function listMenus(): array
    {
        return array_keys($this->links);
    }
}
