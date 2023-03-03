<?php
/**
 * @class Password
 * @brief HTML Forms password field creation helpers
 *
 * @package Clearbricks
 * @subpackage html.form
 *
 * @since 1.2 First time this was introduced.
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Helper\Html\Form;

class Password extends Input
{
    /**
     * Constructs a new instance.
     *
     * @param      mixed  $id     The identifier
     * @param      string $value  The value
     */
    public function __construct($id = null, ?string $value = null)
    {
        parent::__construct($id, 'password');
        // Default attributes for a password, may be supercharge after
        $this->autocorrect('off');
        $this->spellcheck('off');
        $this->autocapitalize('off');
        if ($value !== null) {
            $this->value($value);
        }
    }
}
