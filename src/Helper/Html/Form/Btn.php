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
 * @class Btn
 * @brief HTML Forms button field creation helpers
 *
 * @method      $this popovertarget(string $popovertarget)
 * @method      $this popovertargetaction(string $popovertargetaction)
 * @method      $this text(string $text)
 *
 * @property    string $popovertarget
 * @property    string $popovertargetaction (hide, show, toggle = default)
 * @property    string $text
 */
class Btn extends Component
{
    private const DEFAULT_ELEMENT = 'button';

    /**
     * Constructs a new instance.
     *
     * @param      string|array{0: string, 1?: string}|null     $id       The identifier
     * @param      string                                       $value    The value
     * @param      string                                       $element  The element
     */
    public function __construct($id = null, ?string $value = null, ?string $element = null)
    {
        parent::__construct(self::class, $element ?? self::DEFAULT_ELEMENT);
        if ($id !== null) {
            $this->setIdentifier($id);
        }
        if ($value !== null) {
            $this->value($value);
        }
    }

    /**
     * Renders the HTML component.
     */
    public function render(): string
    {
        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) .
            ($this->popovertarget !== null ? ' popovertarget="' . $this->popovertarget . '"' : '') .
            ($this->popovertargetaction !== null ? ' popovertargetaction="' . $this->popovertargetaction . '"' : '') .
            $this->renderCommonAttributes(false) . '>';

        if ($this->value !== null) {
            $buffer .= $this->value;
        } elseif ($this->text !== null) {
            $buffer .= $this->text;
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
