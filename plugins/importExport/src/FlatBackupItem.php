<?php
/**
 * @brief importExport, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\importExport;

class FlatBackupItem
{
    public $__name;
    public $__line;
    private $__data = [];

    public function __construct($name, $data, $line)
    {
        $this->__name = $name;
        $this->__data = $data;
        $this->__line = $line;
    }

    public function f($name)
    {
        return iconv('UTF-8', 'UTF-8//IGNORE', (string) $this->__data[$name]);
    }

    public function __get($name)
    {
        return $this->f($name);
    }

    public function __set($n, $v)
    {
        $this->__data[$n] = $v;
    }

    public function exists($n)
    {
        return isset($this->__data[$n]);
    }

    public function drop(...$args)
    {
        foreach ($args as $n) {
            if (isset($this->__data[$n])) {
                unset($this->__data[$n]);
            }
        }
    }

    public function substitute($old, $new)
    {
        if (isset($this->__data[$old])) {
            $this->__data[$new] = $this->__data[$old];
            unset($this->__data[$old]);
        }
    }
}
