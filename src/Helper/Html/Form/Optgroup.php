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
 * @class Optgroup
 * @brief HTML Forms optgroup creation helpers
 */
class Optgroup extends Component
{
    /**
     * Text content
     */
    protected ?string $text = null;

    /**
     * List of items
     *
     * @var ?Iterable<array-key, Component|string|Iterable<array-key, Component|string>> $items
     */
    public ?Iterable $items = null;     // @phpstan-ignore property.phpDocType (wait for PHP 8.3 and #[Override] attribute)

    /**
     * @var string DEFAULT_ELEMENT
     */
    private const DEFAULT_ELEMENT = 'optgroup';

    /**
     * Constructs a new instance.
     *
     * @param      string       $text     The optgroup text
     * @param      null|string  $element  The element
     */
    public function __construct(string $text, ?string $element = null)
    {
        parent::__construct(self::class, $element ?? self::DEFAULT_ELEMENT);
        $this
            ->text($text);
    }

    /**
     * Set text
     *
     * @return static    self instance, enabling to chain calls
     */
    public function text(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Set list of items
     *
     * @param Iterable<array-key, Component|string|Iterable<array-key, Component|string>> $items
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
     *
     * @param      null|string  $default   The default value
     */
    public function render(?string $default = null): string
    {
        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) .
            ($this->text !== null ? ' label="' . $this->text . '"' : '') .
            $this->renderCommonAttributes() . '>' . "\n";

        if ($this->items !== null) {
            foreach ($this->items as $item => $value) {
                if ($value instanceof None) {
                    continue;
                }

                if ($value instanceof Component) {
                    if ($value instanceof Option || $value instanceof Optgroup) {
                        $buffer .= $value->render($default);
                    } else {
                        $buffer .= $value->render();
                    }
                } elseif (is_iterable($value)) {
                    $default_value = $default ?? (isset($this->default) ? (string) $this->default : null);
                    $buffer .= (new Optgroup((string) $item))->items($value)->render($default_value);
                } else {
                    $default_value = $default ?? (isset($this->default) ? (string) $this->default : null);
                    $buffer .= (new Option((string) $item, $value))->render($default_value);
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
