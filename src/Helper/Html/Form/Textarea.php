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
 * @class Textarea
 * @brief HTML Forms textarea creation helpers
 *
 * @method      $this cols(int $cols)
 * @method      $this rows(int $rows)
 *
 * @property    int $cols
 * @property    int $rows
 */
class Textarea extends Component
{
    private const DEFAULT_ELEMENT = 'textarea';

    /**
     * Constructs a new instance.
     *
     * @param      string|array{0: string, 1?: string}|null     $id       The identifier
     * @param      string                                       $value    The value
     */
    public function __construct($id = null, ?string $value = null)
    {
        parent::__construct(self::class, self::DEFAULT_ELEMENT);
        if ($id !== null) {
            $this->setIdentifier($id);
        }
        if ($value !== null) {
            $this->value = $value;
        }
    }

    /**
     * Renders the HTML component (including the associated label if any).
     *
     * @param      null|string  $extra  The extra
     *
     * @return     string
     */
    public function render(?string $extra = null): string
    {
        if (!$this->checkMandatoryAttributes()) {
            if (!App::config()->cliMode() && App::config()->devMode() === true && App::config()->debugMode() === true) {
                return '<!-- ' . static::class . ': ' . 'Textarea without id and name (provide at least one of them)' . ' -->' . "\n";
            }

            return '';
        }

        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . ($extra ?? '') . $this->renderCommonAttributes(false) .
            (isset($this->cols) ? ' cols="' . strval((int) $this->cols) . '"' : '') .
            (isset($this->rows) ? ' rows="' . strval((int) $this->rows) . '"' : '') .
            '>' .
            ($this->value ?? '') .
            '</' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . '>' . "\n";

        if (isset($this->label)) {
            $render = true;
            if (isset($this->id)) {
                $this->label->for = $this->id;
            } else {
                if ($this->label->getPosition() === Label::OL_FT || $this->label->getPosition() === Label::OL_TF) {
                    // Do not render label if textarea is outside label and there is no id for textarea
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
