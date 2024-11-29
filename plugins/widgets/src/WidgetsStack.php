<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\widgets;

use Dotclear\Helper\Text;

/**
 * @brief   The widgets stack handler.
 * @ingroup widgets
 */
class WidgetsStack
{
    /**
     * Stack of known widgets.
     *
     * @var     array<mixed, WidgetsElement>   $widgets
     */
    private array $widgets = [];

    /**
     * Load widgets from settings.
     *
     * @param   mixed   $s  Settings
     *
     * @return  self
     */
    public static function load($s): self
    {
        if (!is_array($s)) {
            // Cope with old way to store widgets settings
            $o = @unserialize(base64_decode($s));

            if ($o instanceof self) {
                return $o;
            }
        } else {
            $o = $s;
        }

        return $o ? self::loadArray($o, Widgets::$widgets) : new self();
    }

    /**
     * Return encoded widgets.
     *
     * @return  array<array<string, mixed>>
     */
    public function store(): array
    {
        $serialized = [];
        foreach ($this->widgets as $pos => $w) {
            $serialized[] = ($w->serialize((int) $pos));
        }

        return $serialized;
    }

    /**
     * Create a new widget.
     *
     * @param   string  $id                 The identifier
     * @param   string  $name               The name
     * @param   mixed   $callback           The callback
     * @param   mixed   $append_callback    The append callback
     * @param   string  $desc               The description
     *
     * @return  WidgetsElement
     */
    public function create(string $id, string $name, $callback, $append_callback = null, string $desc = ''): WidgetsElement
    {
        $this->widgets[$id]                  = new WidgetsElement($id, $name, $callback, $desc);
        $this->widgets[$id]->append_callback = $append_callback;

        return $this->widgets[$id];
    }

    /**
     * Append a widget.
     *
     * @param   WidgetsElement  $widget     The widget
     */
    public function append(WidgetsElement $widget): void
    {
        if (is_callable($widget->append_callback)) {
            call_user_func($widget->append_callback, $widget);
        }
        $this->widgets[] = $widget;
    }

    /**
     * Determines if widgets list is empty.
     *
     * @return  bool    True if empty, False otherwise.
     */
    public function isEmpty(): bool
    {
        return count($this->widgets) == 0;
    }

    /**
     * Return list of widgets.
     *
     * @param   bool    $sorted     Sort the list
     *
     * @return  array<mixed, WidgetsElement>
     */
    public function elements(bool $sorted = false): array
    {
        if ($sorted) {
            uasort($this->widgets, function ($a, $b) {
                $c = Text::removeDiacritics(mb_strtolower($a->name()));
                $d = Text::removeDiacritics(mb_strtolower($b->name()));

                return $c <=> $d;
            });
        }

        return $this->widgets;
    }

    /**
     * Get a widget.
     *
     * @param   string  $id     The widget identifier
     *
     * @return  mixed
     */
    public function __get($id)
    {
        if (!isset($this->widgets[$id])) {
            return;
        }

        return $this->widgets[$id];
    }

    /**
     * Gets a widget.
     *
     * @param      string  $id     The widget identifier
     *
     * @return     mixed
     */
    public function get(string $id)
    {
        return $this->__get($id);
    }

    /**
     * Unset all widgets.
     */
    public function __wakeup()
    {
        foreach ($this->widgets as $i => $w) {
            if (!($w instanceof WidgetsElement)) {  // @phpstan-ignore-line
                unset($this->widgets[$i]);
            }
        }
    }

    /**
     * Loads an array of widgets.
     *
     * @param   array<array<string, mixed>>     $A
     * @param   WidgetsStack                    $widgets    The widgets
     *
     * @return  self
     */
    public static function loadArray(array $A, self $widgets): self
    {
        uasort($A, fn ($a, $b) => $a['order'] <=> $b['order']);

        $result = new self();
        foreach ($A as $v) {
            if (empty($v)) {
                continue;
            }
            if ($widgets->{$v['id']} != null) {
                $w = clone $widgets->{$v['id']};

                // Settings
                unset($v['id'], $v['order']);

                foreach ($v as $sid => $s) {
                    $w->{$sid} = $s;
                }

                $result->append($w);
            }
        }

        return $result;
    }
}
