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
 * @class Table
 * @brief HTML Forms table creation helpers
 */
class Table extends Component
{
    /**
     * The caption attached to this component
     */
    protected ?Caption $caption = null;

    /**
     * The thead attached to this component
     */
    protected ?Thead $thead = null;

    /**
     * The tbody attached to this component
     */
    protected ?Tbody $tbody = null;

    /**
     * The tfoot attached to this component
     */
    protected ?Tfoot $tfoot = null;

    /**
     * @var string DEFAULT_ELEMENT
     */
    private const DEFAULT_ELEMENT = 'table';

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
     * Set the caption attached to this component
     *
     * @return static    self instance, enabling to chain calls
     */
    public function caption(Caption $caption): static
    {
        $this->caption = $caption;

        return $this;
    }

    /**
     * Set the thead attached to this component
     *
     * @return static    self instance, enabling to chain calls
     */
    public function thead(Thead $thead): static
    {
        $this->thead = $thead;

        return $this;
    }

    /**
     * Set the tbody attached to this component
     *
     * @return static    self instance, enabling to chain calls
     */
    public function tbody(Tbody $tbody): static
    {
        $this->tbody = $tbody;

        return $this;
    }

    /**
     * Set the tfoot attached to this component
     *
     * @return static    self instance, enabling to chain calls
     */
    public function tfoot(Tfoot $tfoot): static
    {
        $this->tfoot = $tfoot;

        return $this;
    }

    /**
     * Attaches the caption to this table.
     *
     * @param      Caption|null  $caption  The legend
     */
    public function attachCaption(?Caption $caption): void
    {
        if ($caption instanceof Caption) {
            $this->caption($caption);
        } elseif ($this->caption instanceof Caption) {
            $this->caption = null;
        }
    }

    /**
     * Detaches the caption.
     */
    public function detachCaption(): void
    {
        $this->caption = null;
    }

    /**
     * Renders the HTML component (including the associated caption if any).
     */
    public function render(): string
    {
        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . $this->renderCommonAttributes() . '>' . "\n";

        if ($this->caption instanceof Caption) {
            $buffer .= $this->caption->render();
        }

        if ($this->thead instanceof Thead) {
            $buffer .= $this->thead->render();
        }

        if ($this->tbody instanceof Tbody) {
            $buffer .= $this->tbody->render();
        }

        if ($this->tfoot instanceof Tfoot) {
            $buffer .= $this->tfoot->render();
        }

        if ($this->items !== null) {
            foreach ($this->items as $item) {
                if ($item instanceof None) {
                    continue;
                }

                if ($this->caption instanceof Caption && $item->getDefaultElement() === 'caption') {
                    // Do not put more than one legend in fieldset
                    continue;
                }

                if ($this->thead instanceof Thead && $item->getDefaultElement() === 'thead') {
                    // Do not put more than one thead in fieldset
                    continue;
                }

                if ($this->tbody instanceof Tbody && $item->getDefaultElement() === 'tbody') {
                    // Do not put more than one tbody in fieldset
                    continue;
                }

                if ($this->tfoot instanceof Tfoot && $item->getDefaultElement() === 'tfoot') {
                    // Do not put more than one tfoot in fieldset
                    continue;
                }

                $buffer .= $item->render() . "\n";
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
