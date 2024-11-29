<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Interface\Core\FormaterInterface;
use Dotclear\Interface\Core\PluginsInterface;

/**
 * @brief   Text formater handler.
 *
 * @since   2.28, Text formater features have been grouped in this class
 */
class Formater implements FormaterInterface
{
    /**
     * Stack of registered content formaters.
     *
     * @var     array<string,array<string,callable>>    $stack
     */
    private $stack = [];

    /**
     * Stack of registered content formaters' name.
     *
     * @var     array<string,string>    $names
     */
    private $names = [];

    /**
     * Constructor.
     *
     * @param   PluginsInterface   $plugins   The plugins instance
     */
    public function __construct(
        protected PluginsInterface $plugins
    ) {
    }

    public function addEditorFormater(string $editor_id, string $name, ?callable $func): void
    {
        if (is_callable($func)) {
            $this->stack[$editor_id][$name] = $func;
        }
    }

    public function addFormaterName(string $format, string $name): void
    {
        $this->names[$format] = $name;
    }

    public function getFormaterName(string $format): string
    {
        return $this->names[$format] ?? $format;
    }

    /**
     * Gets the editors.
     *
     * @return  array<string, string>   The editors.
     */
    public function getEditors(): array
    {
        $res = [];

        foreach (array_keys($this->stack) as $editor_id) {
            $res[$editor_id] = $this->plugins->getDefine($editor_id)->get('name');
        }

        return $res;
    }

    /**
     * Gets the formater.
     *
     * @param      string  $editor_id  The editor identifier
     *
     * @return  array<string>   The formaters.
     */
    public function getFormater(string $editor_id): array
    {
        $res = [];

        if (isset($this->stack[$editor_id])) {
            $res = array_keys($this->stack[$editor_id]);
        }

        return $res;
    }

    /**
     * Gets the formaters.
     *
     * @return     array<string, array<string>>  The formaters.
     */
    public function getFormaters(): array
    {
        $res = [];

        foreach ($this->stack as $editor => $formaters) {
            $res[$editor] = array_keys($formaters);
        }

        return $res;
    }

    public function callEditorFormater(string $editor_id, string $name, string $str): string
    {
        $res = null;
        if (isset($this->stack[$editor_id]) && isset($this->stack[$editor_id][$name])) {
            $res = call_user_func($this->stack[$editor_id][$name], $str);
        } else {
            // Fallback with another editor if possible
            foreach ($this->stack as $editor => $formaters) {
                if (array_key_exists($name, $formaters)) {
                    $res = call_user_func($this->stack[$editor][$name], $str);

                    break;
                }
            }
        }

        return is_string($res) ? $res : $str;
    }
}
