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
 * @class Strong
 * @brief HTML Forms strong creation helpers
 */
class Strong extends Text
{
    /**
     * Constructs a new instance.
     *
     * @param      string  $value    The value
     */
    public function __construct(?string $value = null)
    {
        parent::__construct('strong', $value);
    }
}
