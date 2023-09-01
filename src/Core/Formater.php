<?php
/**
 * Text formater handler.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\App;
use Dotclear\Interface\Core\FormaterInterface;

class Formater implements FormaterInterface
{
    /** @var     array<string,array<string,callable>>    Stack of registered content formaters */
    private $stack = [];

    /** @var     array<string,string>   Stack of registered content formaters' name */
    private $names = [];

    public function addEditorFormater(string $editor_id, string $name, $func): void
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

    public function getEditors(): array
    {
        $res = [];

        foreach (array_keys($this->stack) as $editor_id) {
            $res[$editor_id] = App::plugins()->getDefine($editor_id)->get('name');
        }

        return $res;
    }

    public function getFormater(string $editor_id): array
    {
        $res = [];

        if (isset($this->stack[$editor_id])) {
            $res = array_keys($this->stack[$editor_id]);
        }

        return $res;
    }

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
