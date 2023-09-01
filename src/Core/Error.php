<?php
/**
 * Error handler.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Core\Backend\Notices; // deprecated
use Dotclear\Interface\Core\ErrorInterface;

class Error implements ErrorInterface
{
    /** @var    array<int,string>   Errors stack */
    protected $stack = [];

    /** @var    bool True if stack is not empty */
    protected $flag = false;

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
        $res = '';

        if ($this->flag) {
            foreach ($this->stack as $msg) {
                $res .= Notices::error($msg, true, false, false);
            }
            if ($reset) {
                $this->reset();
            }
        }

        return $res;
    }
}
