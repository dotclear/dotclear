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
 *
 * @method      $this colspan(int $colspan)
 * @method      $this rowspan(int $rowspan)
 * @method      $this headers(string $headers)
 * @method      $this text(string $text)
 * @method      $this separator(string $separator)
 * @method      $this format(string $format)
 * @method      $this items(array<int|string, Component>|Iterable<int|string, Component> $items)
 *
 * @property    int $colspan
 * @property    int $rowspan
 * @property    string $headers
 * @property    string $text
 * @property    string $separator
 * @property    string $format
 * @property    array<int|string, Component>|Iterable<int|string, Component> $items
 */
class Td extends Component
{
    public const DEFAULT_ELEMENT = 'td';

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
     */
    public function render(?string $format = null): string
    {
        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) .
            ($this->colspan !== null ? ' colspan=' . strval((int) $this->colspan) : '') .
            ($this->rowspan !== null ? ' rowspan=' . strval((int) $this->rowspan) : '') .
            ($this->headers !== null ? ' headers="' . $this->headers . '"' : '') .
            $this->renderCommonAttributes() . '>';

        if ($this->text !== null) {
            $buffer .= $this->text;
        }

        $first = true;
        $format ??= ($this->format ?? '%s');

        // Cope with items
        if ($this->items !== null) {
            $first = true;
            foreach ($this->items as $item) {
                if ($item instanceof None) {
                    continue;
                }
                if (!$first && $this->separator) {
                    $buffer .= (string) $this->separator;
                }
                $buffer .= sprintf($format, $item->render());
                $first = false;
            }
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
