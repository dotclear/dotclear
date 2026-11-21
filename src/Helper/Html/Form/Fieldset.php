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
 * @class Fieldset
 * @brief HTML Forms fieldset creation helpers
 */
class Fieldset extends Component
{
    /**
     * The Legend attached to this component
     */
    protected ?Legend $legend = null;

    /**
     * @var string DEFAULT_ELEMENT
     */
    private const DEFAULT_ELEMENT = 'fieldset';

    /**
     * Constructs a new instance.
     *
     * @param      string|list{0: string, 1?: string}|null      $id       The identifier
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
     * Set the attached legend
     *
     * @return static    self instance, enabling to chain calls
     */
    public function legend(Legend $legend): static
    {
        $this->legend = $legend;

        return $this;
    }

    /**
     * Attaches the legend to this fieldset.
     *
     * @param      Legend|null  $legend  The legend
     */
    public function attachLegend(?Legend $legend): void
    {
        if ($legend instanceof Legend) {
            $this->legend($legend);
        } elseif ($this->legend instanceof Legend) {
            $this->legend = null;
        }
    }

    /**
     * Detaches the legend.
     */
    public function detachLegend(): void
    {
        $this->legend = null;
    }

    /**
     * Renders the HTML component (including the associated legend if any).
     *
     * @param   string  $format     sprintf() format applied for each items/fields ('%s' by default)
     */
    public function render(?string $format = null): string
    {
        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . $this->renderCommonAttributes() . '>' . "\n";

        if ($this->legend instanceof Legend) {
            $buffer .= $this->legend->render();
        }

        // Cope with fields and items
        $buffer .= implode((string) $this->separator, array_filter(
            [
                $this->renderFields($format, $this->legend instanceof Legend ? 'legend' : null),
                $this->renderItems($format, $this->legend instanceof Legend ? 'legend' : null),
            ],
            fn (string $v): bool => $v !== ''
        ));

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
