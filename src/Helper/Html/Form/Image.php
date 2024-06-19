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
 *
 * @method      $this src(string $src)
 * @method      $this alt(string $alt)
 * @method      $this height(int $height)
 * @method      $this width(int $width)
 *
 * @property    string $src
 * @property    string $alt
 * @property    int $height
 * @property    int $width
 */
class Image extends Input
{
    /**
     * Constructs a new instance.
     *
     * @param      string                                       $src      The mandatory img src
     * @param      string|array{0: string, 1?: string}|null     $id       The identifier
     */
    public function __construct(string $src, $id = null)
    {
        parent::__construct($id, 'image');
        $this->src($src);
    }

    /**
     * Renders the HTML component.
     *
     * @return     string
     */
    public function render(): string
    {
        // Add src and if present the alt attribute
        $extra = $this->extra;
        if (!is_array($extra)) {
            $extra = [$extra];
        }
        $this->extra(array_filter(array_merge($extra, [
            'src="' . $this->src . '"',
            (isset($this->alt) ? 'alt="' . $this->alt . '"' : ''),
            (isset($this->height) ? 'height="' . strval((int) $this->height) . '"' : ''),
            (isset($this->width) ? 'width="' . strval((int) $this->width) . '"' : ''),
        ])));

        return parent::render();
    }
}
