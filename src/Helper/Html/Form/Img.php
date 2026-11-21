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
 * @class Img
 * @brief HTML Forms img creation helpers
 */
class Img extends Component
{
    /**
     * The src attribute
     */
    protected ?string $src = null;

    /**
     * The alt attribute
     */
    protected ?string $alt = null;

    /**
     * The height attribute
     */
    protected ?int $height = null;

    /**
     * The width attribute
     */
    protected ?int $width = null;

    /**
     * @var string DEFAULT_ELEMENT
     */
    private const DEFAULT_ELEMENT = 'img';

    /**
     * The loading attribute
     */
    protected ?string $loading = null;

    /**
     * The sizes attribute
     */
    protected ?string $sizes = null;

    /**
     * The srcset attribute
     */
    protected ?string $srcset = null;

    /**
     * Constructs a new instance.
     *
     * @param      string                                       $src      The mandatory img src
     * @param      string|list{0: string, 1?: string}|null      $id       The identifier
     */
    public function __construct(string $src, string|array|null $id = null)
    {
        parent::__construct(self::class, self::DEFAULT_ELEMENT);
        $this->src($src);
        if ($id !== null) {
            $this->setIdentifier($id);
        }
    }

    /**
     * Set src attribute
     *
     * @return static    self instance, enabling to chain calls
     */
    public function src(string $src): static
    {
        $this->src = $src;

        return $this;
    }

    /**
     * Set alt attribute
     *
     * @return static    self instance, enabling to chain calls
     */
    public function alt(string $alt): static
    {
        $this->alt = $alt;

        return $this;
    }

    /**
     * Set height attribute
     *
     * @return static    self instance, enabling to chain calls
     */
    public function height(int $height): static
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Set width attribute
     *
     * @return static    self instance, enabling to chain calls
     */
    public function width(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Set loading attribute
     *
     * @return static    self instance, enabling to chain calls
     */
    public function loading(string $loading): static
    {
        $this->loading = $loading;

        return $this;
    }

    /**
     * Set sizes attribute
     *
     * @return static    self instance, enabling to chain calls
     */
    public function sizes(string $sizes): static
    {
        $this->sizes = $sizes;

        return $this;
    }

    /**
     * Set srcset attribute
     *
     * @return static    self instance, enabling to chain calls
     */
    public function srcset(string $srcset): static
    {
        $this->srcset = $srcset;

        return $this;
    }

    /**
     * Renders the HTML component.
     */
    public function render(): string
    {
        return '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) .
            ' src="' . $this->src . '"' .
            ($this->alt !== null ? ' alt="' . $this->alt . '"' : '') .
            ($this->height !== null ? ' height="' . strval($this->height) . '"' : '') .
            ($this->loading !== null ? ' loading="' . $this->loading . '"' : '') .
            ($this->sizes !== null ? ' sizes="' . $this->sizes . '"' : '') .
            ($this->srcset !== null ? ' srcset="' . $this->srcset . '"' : '') .
            ($this->width !== null ? ' width="' . strval($this->width) . '"' : '') .
            $this->renderCommonAttributes() . '>';
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
