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
 *
 * @method      $this caption(Caption $caption)
 * @method      $this thead(Thead $thead)
 * @method      $this tbody(Tbody $tbody)
 * @method      $this tfoot(Tbody $tfoot)
 * @method      $this items(array<int|string, Component>|Iterable<int|string, Component> $items)
 *
 * @property    Caption $caption
 * @property    Thead $thead
 * @property    Tbody $tbody
 * @property    Tfoot $tfoot
 * @property    array<int|string, Component>|Iterable<int|string, Component> $items
 */
class Table extends Component
{
    private const DEFAULT_ELEMENT = 'table';

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
     * Attaches the caption to this table.
     *
     * @param      Caption|null  $caption  The legend
     */
    public function attachCaption(?Caption $caption): void
    {
        if ($caption instanceof Caption) {
            $this->caption($caption);
        } elseif ($this->caption !== null) {
            unset($this->caption);
        }
    }

    /**
     * Detaches the caption.
     */
    public function detachCaption(): void
    {
        if ($this->caption !== null) {
            unset($this->caption);
        }
    }

    /**
     * Renders the HTML component (including the associated caption if any).
     */
    public function render(): string
    {
        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . $this->renderCommonAttributes() . '>' . "\n";

        if ($this->caption !== null) {
            $buffer .= $this->caption->render();
        }

        if ($this->thead !== null) {
            $buffer .= $this->thead->render();
        }

        if ($this->tbody !== null) {
            $buffer .= $this->tbody->render();
        }

        if ($this->tfoot !== null) {
            $buffer .= $this->tfoot->render();
        }

        if ($this->items !== null) {
            foreach ($this->items as $item) {
                if ($item instanceof None) {
                    continue;
                }
                if ($this->caption !== null && $item->getDefaultElement() === 'caption') {
                    // Do not put more than one legend in fieldset
                    continue;
                }
                if ($this->thead !== null && $item->getDefaultElement() === 'thead') {
                    // Do not put more than one thead in fieldset
                    continue;
                }
                if ($this->tbody !== null && $item->getDefaultElement() === 'tbody') {
                    // Do not put more than one tbody in fieldset
                    continue;
                }
                if ($this->tfoot !== null && $item->getDefaultElement() === 'tfoot') {
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
