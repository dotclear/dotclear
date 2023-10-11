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
 * @class Checkbox
 * @brief HTML Forms checkbox button creation helpers
 */
class Checkbox extends Input
{
    /**
     * Constructs a new instance.
     *
     * @param      string|array{0: string, 1?: string}|null     $id       The identifier
     * @param      bool                                         $checked  Is checked
     */
    public function __construct($id = null, ?bool $checked = null)
    {
        parent::__construct($id, 'checkbox');
        if ($checked !== null) {
            $this->checked($checked);
        }
    }
}
