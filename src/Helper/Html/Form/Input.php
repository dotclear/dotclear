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
 * @class Input
 * @brief HTML Forms input field creation helpers
 */
class Input extends Component
{
    private const DEFAULT_ELEMENT = 'input';

    /**
     * Constructs a new instance.
     *
     * @param      string|array{0: string, 1?: string}|null     $id             The identifier
     * @param      string                                       $type           The input type
     * @param      bool                                         $renderLabel    Render label if present
     */
    public function __construct(
        $id = null,
        string $type = 'text',
        private bool $renderLabel = true
    ) {
        parent::__construct(self::class, self::DEFAULT_ELEMENT);
        $this->type($type);
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
        if (!$this->checkMandatoryAttributes()) {
            return '';
        }

        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . $this->renderCommonAttributes() . '>';

        if ($this->renderLabel && isset($this->label)) {
            $render = true;
            if (isset($this->id)) {
                $this->label->for = $this->id;
            } else {
                if ($this->label->getPosition() === Label::OL_FT || $this->label->getPosition() === Label::OL_TF) {
                    // Do not render label if input is outside label and there is no id for input
                    $render = false;
                }
            }
            if ($render) {
                $buffer = $this->label->render($buffer);
            }
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
