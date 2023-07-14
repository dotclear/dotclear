<?php
/**
 * @class Table
 * @brief HTML Forms table creation helpers
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Helper\Html\Form;

class Table extends Component
{
    private const DEFAULT_ELEMENT = 'table';

    /**
     * Constructs a new instance.
     *
     * @param      mixed   $id       The identifier
     * @param      string  $element  The element
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
     * @param      Caption|null  $legend  The legend
     */
    public function attachCaption(?Caption $caption)
    {
        if ($caption) {
            $this->caption($caption);
        } elseif (isset($this->caption)) {
            unset($this->caption);
        }
    }

    /**
     * Detaches the caption.
     */
    public function detachCaption()
    {
        if (isset($this->caption)) {
            unset($this->caption);
        }
    }

    /**
     * Renders the HTML component (including the associated caption if any).
     *
     * @return     string
     */
    public function render(): string
    {
        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . $this->renderCommonAttributes() . '>' . "\n";

        if (isset($this->caption)) {
            $buffer .= $this->caption->render();
        }

        if (isset($this->thead)) {
            $buffer .= $this->thead->render();
        }

        if (isset($this->tbody)) {
            $buffer .= $this->tbody->render();
        }

        if (isset($this->tfoot)) {
            $buffer .= $this->tfoot->render();
        }

        if (isset($this->items) && is_array($this->items)) {
            foreach ($this->items as $item) {
                if (isset($this->caption) && $item->getDefaultElement() === 'caption') {
                    // Do not put more than one legend in fieldset
                    continue;
                }
                if (isset($this->thead) && $item->getDefaultElement() === 'thead') {
                    // Do not put more than one thead in fieldset
                    continue;
                }
                if (isset($this->tbody) && $item->getDefaultElement() === 'tbody') {
                    // Do not put more than one tbody in fieldset
                    continue;
                }
                if (isset($this->tfoot) && $item->getDefaultElement() === 'tfoot') {
                    // Do not put more than one tfoot in fieldset
                    continue;
                }
                $buffer .= $item->render() . "\n";
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
