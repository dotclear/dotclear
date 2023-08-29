<?php
/**
 * Version handler.
 *
 * Handle id,version pairs through database.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use dcPlugins;

class Formater
{
    /** @var     array<string,array<string,callable>>    Stack of registered content formaters */
    private $stack = [];

    /** @var     array<string,string>   Stack of registered content formaters' name */
    private $names = [];

    /**
     * Conetsrutor grab all it needs.
     *
     * @param   dcPlugins   $plugins    The plugins instance
     */
    public function __construct(
        private dcPlugins $plugins
    ) {

    }

    /**
     * Adds a new text formater.
     *
     * Which will call the function <var>$func</var> to
     * transform text. The function must be a valid callback and takes one
     * argument: the string to transform. It returns the transformed string.
     *
     * @param   string      $editor_id  The editor identifier (dcLegacyEditor, dcCKEditor, ...)
     * @param   string      $name       The formater name
     * @param   callable    $func       The function to use, must be a valid and callable callback
     */
    public function addEditorFormater(string $editor_id, string $name, $func): void
    {
        if (is_callable($func)) {
            $this->stack[$editor_id][$name] = $func;
        }
    }

    /**
     * Adds a formater name.
     *
     * @param   string  $format     The format
     * @param   string  $name       The name
     */
    public function addFormaterName(string $format, string $name): void
    {
        $this->names[$format] = $name;
    }

    /**
     * Gets the formater name.
     *
     * @param   string  $format     The format
     *
     * @return  string  The formater name.
     */
    public function getFormaterName(string $format): string
    {
        return $this->names[$format] ?? $format;
    }

    /**
     * Gets the editors list.
     *
     * @return  array   The editors.
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
     * Gets the formaters.
     *
     * return formaters for an editor if editor is active
     * return empty() array if editor is not active.
     * It can happens when a user choose an editor and admin deactivate that editor later
     *
     * @param   string  $editor_id  The editor identifier (dcLegacyEditor, dcCKEditor, ...)
     *
     * @return  array   The formaters.
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
     * @return  array   The formaters.
     */
    public function getFormaters(): array
    {
        $res = [];

        foreach ($this->stack as $editor => $formaters) {
            $res[$editor] = array_keys($formaters);
        }

        return $res;
    }

    /**
     * Call editor formater.
     *
     * If <var>$name</var> is a valid formater, it returns <var>$str</var>
     * transformed using that formater.
     *
     * @param   string  $editor_id  The editor identifier (dcLegacyEditor, dcCKEditor, ...)
     * @param   string  $name       The formater name
     * @param   string  $str        The string to transform
     *
     * @return  string
     */
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
