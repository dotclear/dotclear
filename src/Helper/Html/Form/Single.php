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
 * @class Single
 * @brief HTML Forms single tag (br, hr, …) creation helpers
 */
class Single extends Component
{
    private const DEFAULT_ELEMENT = '';

    /**
     * Constructs a new instance.
     *
     * @param      string  $element  The element
     */
    public function __construct(string $element)
    {
        parent::__construct(self::class, $element);
    }

    /**
     * Renders the HTML component.
     */
    public function render(): string
    {
        $element = $this->getElement() ?? self::DEFAULT_ELEMENT;
        if ($element === '') {
            // Nothing to do
            return '';
        }

        $render_ca = $this->renderCommonAttributes();

        return '<' . $element . $render_ca . '>';
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
