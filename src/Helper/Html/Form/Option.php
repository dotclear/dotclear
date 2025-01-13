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
 * @class Option
 * @brief HTML Forms option creation helpers
 *
 * @method      $this text(string $text)
 * @method      $this selected(bool $selected)
 *
 * @property    string $text
 * @property    bool   $selected
 */
class Option extends Component
{
    private const DEFAULT_ELEMENT = 'option';

    /**
     * Constructs a new instance.
     *
     * @param      string       $text     The option text
     * @param      string       $value    The option value
     * @param      null|string  $element  The element
     */
    public function __construct(string $text, string $value, ?string $element = null)
    {
        parent::__construct(self::class, $element ?? self::DEFAULT_ELEMENT);
        $this
            ->text($text)
            ->value($value);
    }

    /**
     * Renders the HTML component.
     *
     * @param      null|string  $default   The default value
     */
    public function render(?string $default = null): string
    {
        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) .
            ($this->value === $default || $this->selected ? ' selected' : '') .
            $this->renderCommonAttributes() . '>';

        if ($this->text !== null) {
            $buffer .= $this->text;
        }

        return $buffer . '</' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . '>' . "\n";
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
