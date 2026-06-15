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
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;

class MenuItem
{
    /**
     * @var Li $element HTML element (prepared on demand)
     */
    protected Li $element;

    /**
     * Constructs a new instance.
     *
     * @param      string               $title   The menu item title
     * @param      string               $url     The menu item url
     * @param      string|string[]      $img     The menu item image(s)
     * @param      bool                 $active  The menu item active flag (will add an 'active' class to the menu item)
     * @param      null|string          $id      The menu item identifier
     * @param      null|string          $class   The menu item class
     */
    public function __construct(
        protected string $title,
        protected string $url,
        protected string|array $img,
        protected bool $active,
        protected ?string $id,
        protected ?string $class,
    ) {
    }

    /**
     * Get the HTML element which then be used to render this menu item
     */
    public function getComponent(): Li
    {
        if (!isset($this->element)) {
            $link = (new Link())
                ->href($this->url)
                ->text(App::backend()->helper()->adminIcon($this->img) . $this->title);
            if ($this->id) {
                $link->id('menu-process-' . $this->id);
            }
            if ($this->active) {
                $link->extra('aria-current="page"');
            }

            // Menu list item
            $code = (new Li())
                ->items([
                    $link,
                ]);
            if ($this->id) {
                $code->id('menu-item-' . $this->id);
            }
            $classes = [];
            if ($this->class) {
                $classes[] = $this->class;
            }
            if ($this->active) {
                $classes[] = 'active';
            }
            if ($classes !== []) {
                $code->class($classes);
            }

            $this->element = $code;
        }

        return $this->element;
    }
}
