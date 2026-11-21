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
 */
class Details extends Component
{
    /**
     * The summary attached to this component
     */
    protected ?Summary $summary = null;

    /**
     * The open attribute
     */
    protected ?bool $open = null;

    /**
     * @var string DEFAULT_ELEMENT
     */
    private const DEFAULT_ELEMENT = 'details';

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
     * Set the attached summary
     *
     * @return static    self instance, enabling to chain calls
     */
    public function summary(Summary $summary): static
    {
        $this->summary = $summary;

        return $this;
    }

    /**
     * Set the open attribute
     *
     * @return static    self instance, enabling to chain calls
     */
    public function open(bool $open): static
    {
        $this->open = $open;

        return $this;
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
        } elseif ($this->summary instanceof Summary) {
            $this->summary = null;
        }
    }

    /**
     * Detaches the summary.
     */
    public function detachSummary(): void
    {
        $this->summary = null;
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

        if ($this->summary instanceof Summary) {
            $buffer .= $this->summary->render();
        }

        // Cope with items
        $buffer .= $this->renderItems($format, $this->summary instanceof Summary ? 'summary' : null);

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
