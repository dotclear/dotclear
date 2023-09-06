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
 * @class Hidden
 * @brief HTML Forms hidden field creation helpers
 */
class Hidden extends Input
{
    /**
     * Constructs a new instance.
     *
     * @param      mixed  $id     The identifier
     * @param      string $value  The value
     */
    public function __construct($id = null, ?string $value = null)
    {
        // Label should not be rendered for an input type="hidden"
        parent::__construct($id, 'hidden', false);
        if ($value !== null) {
            $this->value($value);
        }
    }
}
