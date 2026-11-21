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
 * @class Image
 * @brief HTML Forms input image field creation helpers
 */
class Image extends Input
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
     * Constructs a new instance.
     *
     * @param      string                                       $src      The mandatory img src
     * @param      string|list{0: string, 1?: string}|null      $id       The identifier
     */
    public function __construct(string $src, string|array|null $id = null)
    {
        parent::__construct($id, 'image');
        $this->src($src);
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
     * Renders the HTML component.
     */
    public function render(): string
    {
        // Add image attributes to the common extra attributes for this render
        // Save current extra and restore it after rendering
        $backup = $this->extra;
        $extra  = is_array($backup) ? $backup : [$backup];

        $this->extra(array_filter(
            array_merge(
                $extra,
                [
                    'src="' . $this->src . '"',
                    ($this->alt !== null ? 'alt="' . $this->alt . '"' : ''),
                    ($this->height !== null ? 'height="' . strval($this->height) . '"' : ''),
                    ($this->width !== null ? 'width="' . strval($this->width) . '"' : ''),
                ]
            ),
            fn ($v): bool => !empty($v)
        ));

        try {
            return parent::render();
        } finally {
            $this->extra = $backup;
        }
    }
}
