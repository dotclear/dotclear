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

use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Set;

class Icon
{
    /**
     * Default image if light icon src is not provided
     */
    public const FALLBACK = 'images/menu/no-icon.svg';

    /**
     * @var Set|None $element HTML element (prepared on demand)
     */
    protected Set|None $element;

    /**
     * Constructs a new instance.
     *
     * May be used then for a menu item, a dashboard button, …
     *
     * @param      string           $light      Light mode icon src
     * @param      string           $dark       Dark mode icon src (will use light one if dark is not provded)
     * @param      string           $alt        Alternate text
     * @param      string           $title      Icon title
     * @param      string|string[]  $class      Class or list of classes
     */
    public function __construct(
        protected string $light,
        protected string $dark = '',
        protected string $alt = '',
        protected string $title = '',
        protected string|array $class = ''
    ) {
    }

    /**
     * Get icons src
     *
     * @return string|array{0: string, 1?: string}
     */
    public function getIcons(string $fallback = self::FALLBACK): string|array
    {
        // Get light mode icon src, using fallback if none
        $light_img = $this->light !== '' ? $this->light : $fallback;

        if ($this->dark === '') {
            return $light_img;
        }

        return [$light_img, $this->dark];
    }

    /**
     * Get the HTML element which then be used to render this icon
     *
     * @param   string      $fallback       Default src for icon if no src provided at contruction
     */
    public function getComponent(string $fallback = self::FALLBACK): Set|None
    {
        if (!isset($this->element)) {
            // Get light mode icon src, using fallback if none
            $light_img = $this->light !== '' ? $this->light : $fallback;

            // Get dark mode icon src, using light mode ont is none defined
            $dark_img = $this->dark;

            if ($light_img === '' && $dark_img === '') {
                $this->element = new None();
            } else {
                // Don't repeat alt in title
                $title = $this->title !== $this->alt ? $this->title : '';

                $classes = is_array($this->class) ? $this->class : [$this->class];

                $icons = [];

                if ($light_img !== '') {
                    // Cope with light mode icon
                    $icon = (new Img($light_img))
                        ->class(array_filter([$dark_img !== '' ? 'light-only' : '', ... $classes]))
                        ->alt($this->alt);

                    if ($title !== '') {
                        $icon->title($title);
                    }

                    $icons[] = $icon;
                }

                if ($dark_img !== '') {
                    // Cope with dark mode icon
                    $icon = (new Img($dark_img))
                        ->class(array_filter([$light_img !== '' ? 'dark-only' : '', ... $classes]))
                        ->alt($this->alt);

                    if ($title !== '') {
                        $icon->title($title);
                    }

                    $icons[] = $icon;
                }

                $this->element = (new Set())
                    ->items($icons);
            }
        }

        return $this->element;
    }
}
