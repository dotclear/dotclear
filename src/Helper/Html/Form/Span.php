<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Html\Form;

/**
 * @class Span
 * @brief HTML Forms span creation helpers
 */
class Span extends Text
{
    /**
     * Constructs a new instance.
     *
     * @param      string  $value    The value
     */
    public function __construct(?string $value = null)
    {
        parent::__construct('span', $value);
    }
}
