<?php

declare(strict_types=1);

/**
 * @class formNumber
 * @brief HTML Forms number field creation helpers
 *
 * @package Clearbricks
 * @subpackage html.form
 *
 * @since 1.2 First time this was introduced.
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class formNumber extends formInput
{
    /**
     * Constructs a new instance.
     *
     * @param      mixed  $id     The identifier
     * @param      int    $min    The minimum value
     * @param      int    $max    The maximum value
     * @param      int    $value  The value
     */
    public function __construct($id = null, ?int $min = null, ?int $max = null, ?int $value = null)
    {
        parent::__construct($id, 'number');
        $this
            ->min($min)
            ->max($max)
            ->inputmode('numeric');
        if ($value !== null) {
            $this->value($value);
        }
    }
}
