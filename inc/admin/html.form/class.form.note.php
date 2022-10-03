<?php

declare(strict_types=1);
/**
 * @class formNote
 * @brief HTML Forms note creation helpers
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class formNote extends formComponent
{
    public const DEFAULT_ELEMENT = 'p';

    /**
     * Constructs a new instance.
     *
     * @param      mixed  $id     The identifier
     */
    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    /**
     * Renders the HTML component.
     *
     * @return     string
     */
    public function render(): string
    {
        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . $this->renderCommonAttributes() . '>';
        if ($this->text) {
            $buffer .= $this->text;
        }
        $buffer .= '</' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . '>' . "\n";

        return $buffer;
    }

    /**
     * Gets the default element.
     *
     * @return     string  The default element.
     */
    public function getDefaultElement(): string
    {
        return self::DEFAULT_ELEMENT;
    }
}
