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
 * @class Thead
 * @brief HTML Forms Thead creation helpers
 *
 * @method      $this format(string $format)
 * @method      $this rows(array|Iterable $rows)
 * @method      $this items(array|Iterable $items)
 *
 * @property    string $format
 * @property    array|Iterable $rows
 * @property    array|Iterable $items
 */
class Thead extends Component
{
    private const DEFAULT_ELEMENT = 'thead';

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

        // Cope with rows
        if (isset($this->rows)) {
            foreach ($this->rows as $row) {
                $buffer .= sprintf($format, $row->render());
            }
        }

        // Cope with items (as rows)
        if (isset($this->items)) {
            foreach ($this->items as $item) {
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
