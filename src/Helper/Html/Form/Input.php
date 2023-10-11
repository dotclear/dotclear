<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
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
     * Should include the associated label if exist
     */
    private bool $renderLabel = true;

    /**
     * Constructs a new instance.
     *
     * @param      string|array{0: string, 1?: string}|null     $id             The identifier
     * @param      string                                       $type           The input type
     * @param      bool                                         $renderLabel    Render label if present
     */
    public function __construct($id = null, string $type = 'text', bool $renderLabel = true)
    {
        parent::__construct(self::class, self::DEFAULT_ELEMENT);
        $this->type($type);
        $this->renderLabel = $renderLabel;
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

        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . $this->renderCommonAttributes() . '/>';

        if ($this->renderLabel && isset($this->label) && isset($this->id)) {
            $this->label->for = $this->id;
            $buffer           = $this->label->render($buffer);
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
