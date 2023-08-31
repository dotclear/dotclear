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

use Dotclear\Interface\Core\ErrorInterface;

class Error implements ErrorInterface
{
    /** @var    array<int,string>   Errors stack */
    protected $errors = [];

    /** @var    bool True if stack is not empty */
    protected $flag = false;

    public function add(string $msg): void
    {
        $this->flag     = true;
        $this->errors[] = $msg;
    }

    public function flag(): bool
    {
        return $this->flag;
    }

    /**
     * Resets errors stack.
     */
    private function reset(): void
    {
        $this->flag   = false;
        $this->errors = [];
    }

    public function count(): int
    {
        return count($this->errors);
    }

    public function toHTML(bool $reset = true): string
    {
        $res = '';

        if ($this->flag) {
            foreach ($this->errors as $msg) {
                $res .= Notices::error($msg, true, false, false);
            }
            if ($reset) {
                $this->reset();
            }
        }

        return $res;
    }
}
