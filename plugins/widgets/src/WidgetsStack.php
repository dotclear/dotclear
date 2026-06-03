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
     * @var     WidgetsElement[]   $widgets
     */
    private array $widgets = [];

    /**
     * Load widgets from settings.
     *
     * @param   mixed   $settings  Settings
     */
    public static function load($settings): self
    {
        if (!is_array($settings)) {
            // Cope with old way to store widgets settings (serialized and base64 encoded)
            $settings = is_string($settings) ? $settings : '';

            $settings = @unserialize(base64_decode($settings));
            if ($settings instanceof self) {
                return $settings;
            }
        }

        $list = [];
        if (is_array($settings)) {
            // Sanitize given array
            foreach ($settings as $item) {
                $values = [];
                if (is_array($item)) {
                    foreach ($item as $key => $value) {
                        if (is_string($key)) {
                            $values[$key] = $value;
                        }
                    }
                    $list[] = $values;
                }
            }
        }

        return $list !== [] ? self::loadArray($list, Widgets::$widgets) : new self();
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
     * @param   string                                      $id                 The identifier
     * @param   string                                      $name               The name
     * @param   null|callable(WidgetsElement,int=):string   $callback           The callback
     * @param   null|callable(WidgetsElement):void          $append_callback    The append callback
     * @param   string                                      $desc               The description
     * @param   string                                      $plugin_id          The module ID providing this widget
     */
    public function create(string $id, string $name, ?callable $callback, ?callable $append_callback = null, string $desc = '', string $plugin_id = ''): WidgetsElement
    {
        $this->widgets[$id]                  = new WidgetsElement($id, $name, $callback, $desc, $plugin_id);
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
        return count($this->widgets) === 0;
    }

    /**
     * Return list of widgets.
     *
     * @param   bool    $sorted     Sort the list
     *
     * @return  WidgetsElement[]
     */
    public function elements(bool $sorted = false): array
    {
        if ($sorted) {
            uasort($this->widgets, function ($a, $b): int {
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
     */
    public function __get(string $id): mixed
    {
        if (!isset($this->widgets[$id])) {
            return null;
        }

        return $this->widgets[$id];
    }

    /**
     * Gets a widget.
     *
     * @param      string  $id     The widget identifier
     */
    public function get(string $id): mixed
    {
        return $this->__get($id);
    }

    /**
     * Unset all widgets.
     */
    public function __wakeup()
    {
        foreach ($this->widgets as $i => $w) {
            if (!($w instanceof WidgetsElement)) {  // @phpstan-ignore-line Settings may be old so it's necessary to cleanup after unserialize
                unset($this->widgets[$i]);
            }
        }
    }

    /**
     * Loads an array of widgets.
     *
     * @param   array<array<string, mixed>>      $list       List of widgets
     * @param   WidgetsStack                     $widgets    The widgets stack
     */
    public static function loadArray(array $list, self $widgets): self
    {
        uasort($list, fn ($a, $b): int => $a['order'] <=> $b['order']);

        $result = new self();

        foreach ($list as $item) {
            if (empty($item)) {
                continue;
            }

            $id = is_string($id = $item['id']) ? $id : '';
            if ($id !== '' && $widgets->{$id} instanceof WidgetsElement) {
                $widget = clone $widgets->{$id};

                // Settings
                foreach ($item as $setting_id => $setting_value) {
                    if ($setting_id !== 'id' && $setting_id !== 'order') {
                        $widget->{$setting_id} = $setting_value;
                    }
                }

                $result->append($widget);
            }
        }

        return $result;
    }
}
