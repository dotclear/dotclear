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
 * @class Datetime
 * @brief HTML Forms datetime field creation helpers
 */
class Datetime extends Input
{
    /**
     * Constructs a new instance.
     *
     * @param      string|array{0: string, 1?: string}|null     $id       The identifier
     * @param      string                                       $value    The value
     */
    public function __construct(string|array|null $id = null, ?string $value = null)
    {
        parent::__construct($id, 'datetime-local');
        $this
            ->size(16)
            ->maxlength(16)
            ->pattern('[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}')
            ->placeholder('1962-05-13T14:45');
        if ($value !== null) {
            $this->value($value);
        }
    }
}
