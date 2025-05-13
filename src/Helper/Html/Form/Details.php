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
 * @method      $this items(Iterable<int|string, Component> $items)
 * @method      $this format(string $format)
 * @method      $this separator(string $separator)
 * @method      $this open(bool $open)
 *
 * @property    Summary $summary
 * @property    Iterable<int|string, Component> $items
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
        if ($summary instanceof Summary) {
            $this->summary($summary);
        } elseif ($this->summary !== null) {
            unset($this->summary);
        }
    }

    /**
     * Detaches the summary.
     */
    public function detachSummary(): void
    {
        if ($this->summary !== null) {
            unset($this->summary);
        }
    }

    /**
     * Renders the HTML component (including the associated summary if any).
     *
     * @param   string  $format     sprintf() format applied for each items/fields ('%s' by default)
     */
    public function render(?string $format = null): string
    {
        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) .
        ($this->open !== null && $this->open ? ' open' : '') .
        $this->renderCommonAttributes() . '>' . "\n";

        if ($this->summary !== null) {
            $buffer .= $this->summary->render();
        }

        // Cope with items
        $buffer .= $this->renderItems($format, $this->summary !== null ? 'summary' : null);

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
