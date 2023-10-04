<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\importExport;

/**
 * @brief   The module flat backup item handler.
 * @ingroup importExport
 */
class FlatBackupItem
{
    public string $__name;
    public int $__line;

    /**
     * @var array<string, mixed>
     */
    private array $__data = [];

    /**
     * Constructs a new instance.
     *
     * @param      string                   $name   The name
     * @param      array<string, mixed>     $data   The data
     * @param      int                      $line   The line
     */
    public function __construct(string $name, array $data, int $line)
    {
        $this->__name = $name;
        $this->__data = $data;
        $this->__line = $line;
    }

    public function f(string $name): string
    {
        return iconv('UTF-8', 'UTF-8//IGNORE', (string) $this->__data[$name]);
    }

    public function __get(string $name): mixed
    {
        return $this->f($name);
    }

    public function __set(string $n, mixed $v): void
    {
        $this->__data[$n] = $v;
    }

    public function exists(string $n): bool
    {
        return isset($this->__data[$n]);
    }

    /**
     * Drop data
     *
     * @param      string  ...$args  The arguments
     */
    public function drop(...$args): void
    {
        foreach ($args as $n) {
            if (isset($this->__data[$n])) {
                unset($this->__data[$n]);
            }
        }
    }

    public function substitute(string $old, string $new): void
    {
        if (isset($this->__data[$old])) {
            $this->__data[$new] = $this->__data[$old];
            unset($this->__data[$old]);
        }
    }
}
