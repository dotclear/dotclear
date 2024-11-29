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
     *
     * @return     string
     */
    public function render(?string $format = null): string
    {
        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) .
            (isset($this->colspan) ? ' colspan=' . strval((int) $this->colspan) : '') .
            (isset($this->rowspan) ? ' rowspan=' . strval((int) $this->rowspan) : '') .
            (isset($this->headers) ? ' headers="' . $this->headers . '"' : '') .
            $this->renderCommonAttributes() . '>';

        if (isset($this->text)) {
            $buffer .= $this->text;
        }

        $first = true;
        $format ??= ($this->format ?? '%s');

        // Cope with items
        if (isset($this->items)) {
            $first = true;
            foreach ($this->items as $item) {
                if (!$first && $this->separator) {
                    $buffer .= (string) $this->separator;
                }
                $buffer .= sprintf($format, $item->render());
                $first = false;
            }
        }

        $buffer .= '</' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . '>';

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
