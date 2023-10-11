<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Helper\Html\Form;

/**
 * @class Date
 * @brief HTML Forms date field creation helpers
 */
class Date extends Input
{
    /**
     * Constructs a new instance.
     *
     * @param      string|array{0: string, 1?: string}|null     $id       The identifier
     * @param      string                                       $value    The value
     */
    public function __construct($id = null, ?string $value = null)
    {
        parent::__construct($id, 'date');
        $this
            ->size(10)
            ->maxlength(10)
            ->pattern('[0-9]{4}-[0-9]{2}-[0-9]{2}')
            ->placeholder('1962-05-13');
        if ($value !== null) {
            $this->value($value);
        }
    }
}
