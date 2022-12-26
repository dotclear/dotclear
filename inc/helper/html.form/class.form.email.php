<?php

declare(strict_types=1);

/**
 * @class formEmail
 * @brief HTML Forms email field creation helpers
 *
 * @package Clearbricks
 * @subpackage html.form
 *
 * @since 1.2 First time this was introduced.
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class formEmail extends formInput
{
    /**
     * Constructs a new instance.
     *
     * @param      mixed  $id     The identifier
     * @param      string $value  The value
     */
    public function __construct($id = null, ?string $value = null)
    {
        parent::__construct($id, 'email');
        if ($value !== null) {
            $this->value($value);
        }
    }
}
