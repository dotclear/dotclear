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
 * @class Td
 * @brief HTML Forms Td creation helpers
 */
class Td extends Component
{
    /**
     * Text content
     */
    protected ?string $text = null;

    /**
     * The colspan attribute
     */
    protected ?int $colspan = null;

    /**
     * The rowspan attribute
     */
    protected ?int $rowspan = null;

    /**
     * The headers attribute
     */
    protected ?string $headers = null;

    /**
     * @var string DEFAULT_ELEMENT
     */
    private const DEFAULT_ELEMENT = 'td';

    /**
     * Constructs a new instance.
     *
     * @param      string|list{0: string, 1?: string}|null      $id       The identifier
     * @param      string                                       $element  The element
     */
    public function __construct(string|array|null $id = null, ?string $element = null)
    {
        parent::__construct(self::class, $element ?? self::DEFAULT_ELEMENT);
        if ($id !== null) {
            $this->setIdentifier($id);
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
     * Set colspan attribute
     *
     * @return static    self instance, enabling to chain calls
     */
    public function colspan(int $colspan): static
    {
        $this->colspan = $colspan;

        return $this;
    }

    /**
     * Set rowspan attribute
     *
     * @return static    self instance, enabling to chain calls
     */
    public function rowspan(int $rowspan): static
    {
        $this->rowspan = $rowspan;

        return $this;
    }

    /**
     * Set headers attribute
     *
     * @return static    self instance, enabling to chain calls
     */
    public function headers(string $headers): static
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Renders the HTML component.
     *
     * @param   string  $format     sprintf() format applied for each items/fields ('%s' by default)
     */
    public function render(?string $format = null): string
    {
        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) .
            ($this->colspan !== null ? ' colspan=' . strval($this->colspan) : '') .
            ($this->rowspan !== null ? ' rowspan=' . strval($this->rowspan) : '') .
            ($this->headers !== null ? ' headers="' . $this->headers . '"' : '') .
            $this->renderCommonAttributes() . '>';

        if ($this->text !== null) {
            $buffer .= $this->text;
        }

        // Cope with items
        $buffer .= $this->renderItems($format);

        return $buffer . '</' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . '>';
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
