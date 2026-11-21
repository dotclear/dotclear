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
 * @class Link
 * @brief HTML Forms note creation helpers
 */
class Link extends Component
{
    /**
     * Text content
     */
    protected ?string $text = null;

    /**
     * The href attribute
     */
    protected ?string $href = null;

    /**
     * The rel attribute
     */
    protected ?string $rel = null;

    /**
     * The target attribute
     */
    protected ?string $target = null;

    /**
     * The download attribute
     */
    protected null|bool|string $download = null;

    /**
     * @var string DEFAULT_ELEMENT
     */
    private const DEFAULT_ELEMENT = 'a';

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
     * Set href attribute
     *
     * @return static    self instance, enabling to chain calls
     */
    public function href(string $href): static
    {
        $this->href = $href;

        return $this;
    }

    /**
     * Set rel attribute
     *
     * @return static    self instance, enabling to chain calls
     */
    public function rel(string $rel): static
    {
        $this->rel = $rel;

        return $this;
    }

    /**
     * Set target attribute
     *
     * @return static    self instance, enabling to chain calls
     */
    public function target(string $target): static
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Set download attribute
     *
     * @return static    self instance, enabling to chain calls
     */
    public function download(string|bool $download): static
    {
        $this->download = $download;

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
            ($this->href !== null ? ' href="' . $this->href . '"' : '') .
            ($this->rel !== null ? ' rel="' . $this->rel . '"' : '') .
            ($this->target !== null ? ' target="' . $this->target . '"' : '') .
            ($this->download !== null && (bool) $this->download ?
                ' download' . (is_string($this->download) ? '="' . $this->download . '"' : '') :
                '') .
            $this->renderCommonAttributes() . '>';

        // Cope with items
        $buffer .= $this->renderItems($format);

        if ($this->text !== null) {
            $buffer .= $this->text;
        }

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
