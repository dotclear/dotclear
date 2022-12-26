<?php

declare(strict_types=1);

/**
 * @class formCheckbox
 * @brief HTML Forms checkbox button creation helpers
 *
 * @package Clearbricks
 * @subpackage html.form
 *
 * @since 1.2 First time this was introduced.
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class formCheckbox extends formInput
{
    /**
     * Constructs a new instance.
     *
     * @param      mixed   $id      The identifier
     * @param      bool    $checked Is checked
     */
    public function __construct($id = null, ?bool $checked = null)
    {
        parent::__construct($id, 'checkbox');
        if ($checked !== null) {
            $this->checked($checked);
        }
    }
}
