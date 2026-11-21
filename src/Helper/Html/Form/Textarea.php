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
 * @class Textarea
 * @brief HTML Forms textarea creation helpers
 */
class Textarea extends Component
{
    /**
     * The cols attribute
     */
    protected ?int $cols = null;

    /**
     * maxlength attribute
     */
    protected ?int $maxlength = null;

    /**
     * The placeholder attribute
     */
    protected ?string $placeholder = null;

    /**
     * The rows attribute
     */
    protected ?int $rows = null;

    /**
     * @var string DEFAULT_ELEMENT
     */
    private const DEFAULT_ELEMENT = 'textarea';

    /**
     * Constructs a new instance.
     *
     * @param      string|list{0: string, 1?: string}|null      $id       The identifier
     * @param      string                                       $value    The value
     */
    public function __construct(string|array|null $id = null, ?string $value = null)
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
     * Set cols attribute
     *
     * @return static    self instance, enabling to chain calls
     */
    public function cols(int $cols): static
    {
        $this->cols = $cols;

        return $this;
    }

    /**
     * Set maxlength attribute
     */
    public function maxlength(int $maxlength): static
    {
        $this->maxlength = $maxlength;

        return $this;
    }

    /**
     * Set the placeholder attribute
     */
    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * Set rows attribute
     *
     * @return static    self instance, enabling to chain calls
     */
    public function rows(int $rows): static
    {
        $this->rows = $rows;

        return $this;
    }

    /**
     * Renders the HTML component (including the associated label if any).
     *
     * @param      null|string  $extra  The extra
     */
    public function render(?string $extra = null): string
    {
        if (!$this->checkMandatoryAttributes()) {
            if ($this->isVerbose()) {
                return '<!-- ' . static::class . ': ' . 'Textarea without id and name (provide at least one of them)' . ' -->' . "\n";
            }

            return '';
        }

        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . ($extra ?? '') . $this->renderCommonAttributes(false) .
            ($this->cols !== null ? ' cols="' . strval($this->cols) . '"' : '') .
            ($this->rows !== null ? ' rows="' . strval($this->rows) . '"' : '') .
            ($this->maxlength !== null ? ' maxlength="' . $this->maxlength . '"' : '') .
            ($this->placeholder !== null ? ' placeholder="' . $this->placeholder . '"' : '') .
            '>' .
            ($this->value ?? '') .
            '</' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . '>' . "\n";

        if ($this->label instanceof Label) {
            $render = true;
            if ($this->id !== null) {
                $this->label->for($this->id);
            } elseif ($this->label->getPosition() === Label::OL_FT || $this->label->getPosition() === Label::OL_TF) {
                // Do not render label if textarea is outside label and there is no id for textarea
                $render = false;
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
