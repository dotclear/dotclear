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
    /**
     * Constructs a new instance.
     *
     * @param      string                   $__name   The name
     * @param      array<string, mixed>     $__data   The data
     * @param      int                      $__line   The line
     */
    public function __construct(
        public string $__name,
        private array $__data,
        public int $__line
    ) {
    }

    public function f(string $name): string
    {
        $ret = iconv('UTF-8', 'UTF-8//IGNORE', (string) $this->__data[$name]);

        return $ret === false ? (string) $this->__data[$name] : $ret;
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
