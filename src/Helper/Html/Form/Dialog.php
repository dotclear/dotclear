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
 * @class Dialog
 * @brief HTML Forms details creation helpers
 */
class Dialog extends Component
{
    /**
     * The open attribute
     */
    protected ?bool $open = null;

    /**
     * The closedby attribute
     */
    protected ?string $closedby = null;

    /**
     * @var string DEFAULT_ELEMENT
     */
    private const DEFAULT_ELEMENT = 'dialog';

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
     * Set the closedby attribute
     *
     * @return static    self instance, enabling to chain calls
     */
    public function closedby(string $closedby): static
    {
        $this->closedby = $closedby;

        return $this;
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
        ($this->closedby !== null ? ' closedby="' . $this->closedby . '"' : '') .
        $this->renderCommonAttributes() . '>' . "\n";

        // Cope with items
        $buffer .= $this->renderItems($format);

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
