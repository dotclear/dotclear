<?php
/**
 * Error hanlder interface.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

interface ErrorInterface
{
    /**
     * Adds an error to stack.
     *
     * @param string    $msg            Error message
     */
    public function add(string $msg): void;

    /**
     * Returns the value of <var>flag</var> property. True if errors stack is not empty
     *
     * @return bool
     */
    public function flag(): bool;

    /**
     * Return number of stacked errors
     *
     * @return     int
     */
    public function count(): int;

    /**
     * Returns errors stack as HTML and reset it.
     *
     * @param   bool    $reset  True if error stack should be reset
     *
     * @return string
     */
    public function toHTML(bool $reset = true): string;
}
