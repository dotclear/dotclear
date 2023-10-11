<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Helper\Html\Form;

/**
 * @class Img
 * @brief HTML Forms img creation helpers
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
     *
     * @return     string
     */
    public function render(): string
    {
        return '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) .
            ' src="' . $this->src . '"' .
            (isset($this->alt) ? ' alt="' . $this->alt . '"' : '') .
            (isset($this->height) ? ' height="' . strval((int) $this->height) . '"' : '') .
            (isset($this->loading) ? ' loading="' . $this->loading . '"' : '') .
            (isset($this->sizes) ? ' sizes="' . $this->sizes . '"' : '') .
            (isset($this->srcset) ? ' srcset="' . $this->srcset . '"' : '') .
            (isset($this->width) ? ' width="' . strval((int) $this->width) . '"' : '') .
            $this->renderCommonAttributes() . '/>';
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
