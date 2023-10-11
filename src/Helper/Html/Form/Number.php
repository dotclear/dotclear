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
 * @class Number
 * @brief HTML Forms number field creation helpers
 */
class Number extends Input
{
    /**
     * Constructs a new instance.
     *
     * @param      string|array{0: string, 1?: string}|null     $id       The identifier
     * @param      int                                          $min      The minimum value
     * @param      int                                          $max      The maximum value
     * @param      int                                          $value    The value
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
