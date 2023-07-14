<?php
/**
 * @class Th
 * @brief HTML Forms Th creation helpers
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Helper\Html\Form;

class Th extends Component
{
    public const DEFAULT_ELEMENT = 'th';

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
            (isset($this->colspan) ? ' colspan=' . strval((int) $this->colspan) : '') .
            (isset($this->rowspan) ? ' rowspan=' . strval((int) $this->rowspan) : '') .
            (isset($this->headers) ? ' headers="' . $this->headers . '"' : '') .
            (isset($this->scope) ? ' scope="' . $this->scope . '"' : '') .
            (isset($this->abbr) ? ' abbr="' . $this->abbr . '"' : '') .
            $this->renderCommonAttributes() . '>';
        if ($this->text) {
            $buffer .= $this->text;
        }
        $buffer .= '</' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . '>';

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
