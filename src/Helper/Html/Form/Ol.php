<?php
/**
 * @class Ol
 * @brief HTML Forms Ol creation helpers
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Helper\Html\Form;

class Ol extends Component
{
    private const DEFAULT_ELEMENT = 'ol';

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
     * Renders the HTML component.
     *
     * @return     string
     */
    public function render(): string
    {
        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) .
            (isset($this->reversed) && $this->reversed ? ' reversed' : '') .
            (isset($this->start) ? ' start="' . $this->start . '"' : '') .
            (isset($this->type) ? ' type="' . $this->type . '"' : '') .
            $this->renderCommonAttributes() . '>' . "\n";

        if (isset($this->items) && is_array($this->items)) {
            foreach ($this->items as $item) {
                $buffer .= sprintf(($this->format ?: '%s'), $item->render());   // @phpstan-ignore-line
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
