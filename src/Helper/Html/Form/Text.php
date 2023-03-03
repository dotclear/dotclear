<?php
/**
 * @class Text
 * @brief HTML Forms text creation helpers
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Helper\Html\Form;

class Text extends Component
{
    private const DEFAULT_ELEMENT = '';

    /**
     * Constructs a new instance.
     *
     * @param      string  $element  The element
     * @param      string  $value    The value
     */
    public function __construct(?string $element = null, ?string $value = null)
    {
        parent::__construct(self::class, $element ?? self::DEFAULT_ELEMENT);
        if ($value !== null) {
            $this->text = $value;
        }
    }

    /**
     * Renders the HTML component.
     *
     * @return     string
     */
    public function render(): string
    {
        $render_ca = $this->renderCommonAttributes();

        $element = $this->getElement() ?? self::DEFAULT_ELEMENT;
        if ($element === '') {
            // Use span element to render common attributes
            $element = $render_ca ? 'span' : null;
        }

        $buffer = '';
        if ($element) {
            $buffer .= '<' . $element . $render_ca . '>' . "\n";
        }

        if (isset($this->text) && $this->text) {
            $buffer .= $this->text;
        }

        if ($element) {
            $buffer .= '</' . $element . '>' . "\n";
        }

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
