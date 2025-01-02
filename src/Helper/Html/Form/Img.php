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
 *
 * @method      $this src(string $src)
 * @method      $this alt(string $alt)
 * @method      $this height(int $height)
 * @method      $this loading(string $loading)
 * @method      $this sizes(string $sizes)
 * @method      $this srcset(string $srcset)
 * @method      $this width(int $width)
 *
 * @property    string $src
 * @property    string $alt
 * @property    int $height
 * @property    string $loading
 * @property    string $sizes
 * @property    string $srcset
 * @property    int $width
 */
class Img extends Component
{
    private const DEFAULT_ELEMENT = 'img';

    /**
     * Constructs a new instance.
     *
     * @param      string                                       $src      The mandatory img src
     * @param      string|array{0: string, 1?: string}|null     $id       The identifier
     */
    public function __construct(string $src, $id = null)
    {
        parent::__construct(self::class, self::DEFAULT_ELEMENT);
        $this->src($src);
        if ($id !== null) {
            $this->setIdentifier($id);
        }
    }

    /**
     * Renders the HTML component.
     */
    public function render(): string
    {
        return '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) .
            ' src="' . $this->src . '"' .
            ($this->alt !== null ? ' alt="' . $this->alt . '"' : '') .
            ($this->height !== null ? ' height="' . strval((int) $this->height) . '"' : '') .
            ($this->loading !== null ? ' loading="' . $this->loading . '"' : '') .
            ($this->sizes !== null ? ' sizes="' . $this->sizes . '"' : '') .
            ($this->srcset !== null ? ' srcset="' . $this->srcset . '"' : '') .
            ($this->width !== null ? ' width="' . strval((int) $this->width) . '"' : '') .
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
