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
 * @class Datalist
 * @brief HTML Forms optgroup creation helpers
 *
 * @method      $this items(array<array-key, Component|string>|Iterable<array-key, Component|string> $items)
 *
 * @property    null|array<array-key, Component|string>|Iterable<array-key, Component|string> $items
 */
class Datalist extends Component
{
    /**
     * @var string DEFAULT_ELEMENT
     */
    private const DEFAULT_ELEMENT = 'datalist';

    /**
     * Constructs a new instance.
     *
     * @param      string|list{0: string, 1?: string}|null      $id       The identifier
     * @param      null|string                                  $element  The element
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
     */
    public function render(): string
    {
        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) .
            $this->renderCommonAttributes() . '>' . "\n";

        if ($this->items !== null) {
            foreach ($this->items as $value) {
                if ($value instanceof None) {
                    continue;
                }

                if ($value instanceof Option) {
                    // Ensure option text is not set
                    $value->text(null);
                    $buffer .= $value->render();
                } elseif (is_scalar($value)) {
                    $buffer .= (new Option(null, (string) $value))->render();
                }
            }
        }

        return $buffer . '</' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . '>' . "\n";
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
