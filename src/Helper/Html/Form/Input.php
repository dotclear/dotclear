<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Html\Form;

use Dotclear\App;

/**
 * @class Input
 * @brief HTML Forms input field creation helpers
 *
 * @method      $this popovertarget(string $popovertarget)
 * @method      $this popovertargetaction(string $popovertargetaction)
 *
 * @property    string $popovertarget
 * @property    string $popovertargetaction (hide, show, toggle = default)
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
        private readonly bool $renderLabel = true
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
            if (!App::config()->cliMode() && App::config()->devMode() === true && App::config()->debugMode() === true) {
                return '<!-- ' . static::class . ': ' . 'Input (type = ' . $this->type . ') without id and name (provide at least one of them)' . ' -->';
            }

            return '';
        }

        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) .
            (isset($this->popovertarget) ? ' popovertarget="' . $this->popovertarget . '"' : '') .
            (isset($this->popovertargetaction) ? ' popovertargetaction="' . $this->popovertargetaction . '"' : '') .
            $this->renderCommonAttributes() . '>';

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
