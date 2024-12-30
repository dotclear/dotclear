<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

/**
 * @brief   Error handler interface.
 *
 * @since   2.28
 */
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
     */
    public function flag(): bool;

    /**
     * Return number of stacked errors
     */
    public function count(): int;

    /**
     * Get errors stack.
     *
     * @return  array<int,string>   The errors stack
     */
    public function dump(): array;

    /**
     * Reset errors.
     */
    public function reset(): void;

    /**
     * Returns errors stack as HTML and reset it.
     *
     * @deprecated since 2.28, use your own parser instead.
     *
     * @param   bool    $reset  True if error stack should be reset
     */
    public function toHTML(bool $reset = true): string;
}
