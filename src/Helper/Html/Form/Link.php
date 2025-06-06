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
 *
 * @method      $this href(string $href)
 * @method      $this text(string $text)
 * @method      $this format(string $format)
 * @method      $this separator(string $separator)
 * @method      $this items(Iterable<int|string, Component> $items)
 *
 * @property    string $href
 * @property    string $text
 * @property    string $format
 * @property    string $separator
 * @property    Iterable<int|string, Component> $items
 */
class Link extends Component
{
    public const DEFAULT_ELEMENT = 'a';

    /**
     * Constructs a new instance.
     *
     * @param      string|array{0: string, 1?: string}|null     $id       The identifier
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
     * Renders the HTML component.
     *
     * @param   string  $format     sprintf() format applied for each items/fields ('%s' by default)
     */
    public function render(?string $format = null): string
    {
        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) .
            ($this->href !== null ? ' href="' . $this->href . '"' : '') .
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
