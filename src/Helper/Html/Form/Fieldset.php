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
 *
 * @method      $this legend(Legend $legend)
 * @method      $this fields(array|Iterable $fields)
 * @method      $this items(array|Iterable $items)
 * @method      $this format(string $format)
 * @method      $this separator(string $separator)
 *
 * @property    Legend $legend
 * @property    array|Iterable $fields
 * @property    array|Iterable $items
 * @property    string $format
 * @property    string $separator
 */
class Fieldset extends Component
{
    private const DEFAULT_ELEMENT = 'fieldset';

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
     * Attaches the legend to this fieldset.
     *
     * @param      Legend|null  $legend  The legend
     */
    public function attachLegend(?Legend $legend): void
    {
        if ($legend) {
            $this->legend($legend);
        } elseif (isset($this->legend)) {
            unset($this->legend);
        }
    }

    /**
     * Detaches the legend.
     */
    public function detachLegend(): void
    {
        if (isset($this->legend)) {
            unset($this->legend);
        }
    }

    /**
     * Renders the HTML component (including the associated legend if any).
     *
     * @param   string  $format     sprintf() format applied for each items/fields ('%s' by default)
     *
     * @return     string
     */
    public function render(?string $format = null): string
    {
        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . $this->renderCommonAttributes() . '>' . "\n";

        if (isset($this->legend)) {
            $buffer .= $this->legend->render();
        }

        $first = true;
        $format ??= ($this->format ?? '%s');

        // Cope with fields
        if (isset($this->fields)) {
            foreach ($this->fields as $field) {
                if (isset($this->legend) && $field->getDefaultElement() === 'legend') {
                    // Do not put more than one legend in fieldset
                    continue;
                }
                if (!$first && $this->separator) {
                    $buffer .= (string) $this->separator;
                }
                $buffer .= sprintf($format, $field->render());
                $first = false;
            }
        }

        // Cope with items
        if (isset($this->items)) {
            foreach ($this->items as $item) {
                if (isset($this->legend) && $item->getDefaultElement() === 'legend') {
                    // Do not put more than one legend in fieldset
                    continue;
                }
                if (!$first && $this->separator) {
                    $buffer .= (string) $this->separator;
                }
                $buffer .= sprintf($format, $item->render());
                $first = false;
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
