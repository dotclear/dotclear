<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\importExport;

use Dotclear\Helper\Text;

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
     * @param      array<string, string>    $__data   The data
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
        return Text::toUTF8((string) $this->__data[$name]) ?: (string) $this->__data[$name];
    }

    public function __get(string $name): mixed
    {
        return $this->f($name);
    }

    public function __set(string $n, string $v): void
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
