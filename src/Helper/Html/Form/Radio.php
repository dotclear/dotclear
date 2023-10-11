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
 * @class Radio
 * @brief HTML Forms radio button creation helpers
 */
class Radio extends Input
{
    /**
     * Constructs a new instance.
     *
     * @param      string|array{0: string, 1?: string}|null     $id       The identifier
     * @param      bool                                         $checked  If checked
     */
    public function __construct($id = null, ?bool $checked = null)
    {
        parent::__construct($id, 'radio');
        if ($checked !== null) {
            $this->checked($checked);
        }
    }
}
