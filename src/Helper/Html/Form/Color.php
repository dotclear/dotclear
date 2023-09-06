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
 * @class Color
 * @brief HTML Forms color field creation helpers
 */
class Color extends Input
{
    /**
     * Constructs a new instance.
     *
     * @param      mixed  $id     The identifier
     * @param      string $value  The value
     */
    public function __construct($id = null, ?string $value = null)
    {
        parent::__construct($id, 'color');
        $this
            ->size(7)
            ->maxlength(7);
        if ($value !== null) {
            $this->value($value);
        }
    }
}
