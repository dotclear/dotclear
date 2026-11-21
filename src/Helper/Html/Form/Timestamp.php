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
 * @class Timestamp
 * @brief HTML Forms time field creation helpers
 */
class Timestamp extends Component
{
    /**
     * Text content
     */
    protected ?string $text = null;

    /**
     * Datetime content
     */
    protected ?string $datetime = null;

    /**
     * @var string DEFAULT_ELEMENT
     */
    private const DEFAULT_ELEMENT = 'time';

    /**
     * Constructs a new instance.
     *
     * @param      string                                       $value    The value
     * @param      string                                       $element  The element
     */
    public function __construct(?string $value = null, ?string $element = null)
    {
        parent::__construct(self::class, $element ?? self::DEFAULT_ELEMENT);
        if ($value !== null) {
            $this->value($value);
        }
    }

    /**
     * Set text
     *
     * @return static    self instance, enabling to chain calls
     */
    public function text(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Set datetime
     *
     * @return static    self instance, enabling to chain calls
     */
    public function datetime(string $datetime): static
    {
        $this->datetime = $datetime;

        return $this;
    }

    /**
     * Renders the HTML component.
     */
    public function render(): string
    {
        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) .
            ($this->datetime !== null ? ' datetime="' . $this->datetime . '"' : '') .
            $this->renderCommonAttributes(false) . '>';

        if ($this->value !== null) {
            $buffer .= $this->value;
        } elseif ($this->text !== null) {
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
