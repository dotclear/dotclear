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
 */
class Datalist extends Component
{
    /**
     * List of items
     *
     * @var ?Iterable<array-key, Component|string> $items
     */
    public ?Iterable $items = null;     // @phpstan-ignore property.phpDocType (wait for PHP 8.3 and #[Override] attribute)

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
     * Set list of items
     *
     * @param Iterable<array-key, Component|string> $items
     *
     * @return static    self instance, enabling to chain calls
     */
    public function items(Iterable $items): static
    {
        $this->items = $items;

        return $this;
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
                    // Ensure option text is empty
                    $value->text('');
                    $buffer .= $value->render();
                } elseif (is_scalar($value)) {
                    $buffer .= (new Option(null, $value))->render();
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
