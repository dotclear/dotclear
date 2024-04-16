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
 * @class Decimal
 * @brief HTML Forms decimal number field creation helpers
 */
class Decimal extends Input
{
    /**
     * Constructs a new instance.
     *
     * @param      string|array{0: string, 1?: string}|null     $id       The identifier
     * @param      float                                        $min      The minimum value
     * @param      float                                        $max      The maximum value
     * @param      float                                        $value    The value
     */
    public function __construct($id = null, ?float $min = null, ?float $max = null, ?float $value = null)
    {
        parent::__construct($id, 'number');
        $this
            ->min($min)
            ->max($max)
            ->inputmode('decimal')
            ->step('any');
        if ($value !== null) {
            $this->value($value);
        }
    }
}
