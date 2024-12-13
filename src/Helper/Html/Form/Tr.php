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
 * @class Tr
 * @brief HTML Forms Tr creation helpers
 *
 * @method      $this format(string $format)
 * @method      $this cols(array<int|string, Component>|Iterable<int|string, Component> $cols)
 * @method      $this items(array<int|string, Component>|Iterable<int|string, Component> $items)
 *
 * @property    string $format
 * @property    array<int|string, Component>|Iterable<int|string, Component> $cols
 * @property    array<int|string, Component>|Iterable<int|string, Component> $items
 */
class Tr extends Component
{
    private const DEFAULT_ELEMENT = 'tr';

    /**
     * Constructs a new instance.
     *
     * @param      string|array{0: string, 1?: string}|null     $id       The identifier
     * @param      string                                       $element  The element
     */
    public function __construct($id = null, ?string $element = null)
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
     *
     * @return     string
     */
    public function render(?string $format = null): string
    {
        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) .
            $this->renderCommonAttributes() . '>';

        $format ??= ($this->format ?? '%s');

        // Cope with cols
        if (isset($this->cols)) {
            foreach ($this->cols as $col) {
                if ($col instanceof None) {
                    continue;
                }
                $buffer .= sprintf(($this->format ?: '%s'), $col->render());
            }
        }

        // Cope with items (as cols)
        if (isset($this->items)) {
            foreach ($this->items as $item) {
                if ($item instanceof None) {
                    continue;
                }
                $buffer .= sprintf($format, $item->render());
            }
        }

        $buffer .= '</' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . '>' . "\n";

        return $buffer;
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
