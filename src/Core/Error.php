<?php

/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\App;   // deprecated see toHTML() method below
use Dotclear\Interface\Core\ErrorInterface;

/**
 * @brief   Error handler.
 *
 * @since   2.28, container services have been added to constructor
 * @since   2.36, constructor arguments has been replaced by Core instance
 */
class Error implements ErrorInterface
{
    /**
     * Errors stack.
     *
     * @var     string[]   $stack
     */
    protected $stack = [];

    /**
     * True if stack is not empty
     *
     * @var     bool    $flag
     */
    protected $flag = false;

    /**
     * Constructs a new instance.
     *
     * @param   Core    $core   The core container
     */
    public function __construct(
        protected Core $core
    ) {
    }

    public function add(string $msg): void
    {
        $this->flag    = true;
        $this->stack[] = $msg;
    }

    public function flag(): bool
    {
        return $this->flag;
    }

    public function reset(): void
    {
        $this->flag  = false;
        $this->stack = [];
    }

    public function count(): int
    {
        return count($this->stack);
    }

    public function dump(): array
    {
        return $this->stack;
    }

    /**
     * @deprecated since 2.28, use your own parser instead.
     */
    public function toHTML(bool $reset = true): string
    {
        $this->core->deprecated()->set('', '2.28');

        $res = '';

        if ($this->flag) {
            foreach ($this->stack as $msg) {
                $res .= App::backend()->notices()->error($msg, true, false, false);
            }
            if ($reset) {
                $this->reset();
            }
        }

        return $res;
    }
}
