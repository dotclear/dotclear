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
 * @class Details
 * @brief HTML Forms details creation helpers
 *
 * @method      $this summary(Summary $summary)
 * @method      $this items(array<int|string, Component>|Iterable<int|string, Component> $items)
 * @method      $this format(string $format)
 * @method      $this separator(string $separator)
 * @method      $this open(bool $open)
 *
 * @property    Summary $summary
 * @property    array<int|string, Component>|Iterable<int|string, Component> $items
 * @property    string $format
 * @property    string $separator
 * @property    bool $open
 */
class Details extends Component
{
    private const DEFAULT_ELEMENT = 'details';

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
     * Attaches the summary to this fieldset.
     *
     * @param      Summary|null  $summary  The summary
     */
    public function attachSummary(?Summary $summary): void
    {
        if ($summary) {
            $this->summary($summary);
        } elseif (isset($this->summary)) {
            unset($this->summary);
        }
    }

    /**
     * Detaches the summary.
     */
    public function detachSummary(): void
    {
        if (isset($this->summary)) {
            unset($this->summary);
        }
    }

    /**
     * Renders the HTML component (including the associated summary if any).
     *
     * @param   string  $format     sprintf() format applied for each items/fields ('%s' by default)
     *
     * @return     string
     */
    public function render(?string $format = null): string
    {
        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) .
        (isset($this->open) && $this->open ? ' open' : '') .
        $this->renderCommonAttributes() . '>' . "\n";

        if (isset($this->summary)) {
            $buffer .= $this->summary->render();
        }

        $first = true;
        $format ??= ($this->format ?? '%s');

        // Cope with items
        if (isset($this->items)) {
            foreach ($this->items as $item) {
                if ($item instanceof None) {
                    continue;
                }
                if (isset($this->summary) && $item->getDefaultElement() === 'summary') {
                    // Do not put more than one summary in fieldset
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
