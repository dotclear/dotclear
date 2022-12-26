<?php

declare(strict_types=1);

/**
 * @class formTime
 * @brief HTML Forms time field creation helpers
 *
 * @package Clearbricks
 * @subpackage html.form
 *
 * @since 1.2 First time this was introduced.
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class formTime extends formInput
{
    /**
     * Constructs a new instance.
     *
     * @param      mixed  $id     The identifier
     * @param      string $value  The value
     */
    public function __construct($id = null, ?string $value = null)
    {
        parent::__construct($id, 'time');
        $this
            ->size(5)
            ->maxlength(5)
            ->pattern('[0-9]{2}:[0-9]{2}')
            ->placeholder('14:45');

        if ($value !== null) {
            $this->value($value);
        }
    }
}
