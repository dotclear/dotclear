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
    /**
     * list attribute (ID of the datalist component)
     */
    protected ?string $list = null;

    /**
     * maxlength attribute
     */
    protected ?int $maxlength = null;

    /**
     * pattern attribute
     */
    protected ?string $pattern = null;

    /**
     * placeholder attribute
     */
    protected ?string $placeholder = null;

    /**
     * popovertarget attribute
     */
    protected ?string $popovertarget = null;

    /**
     * popovertargetaction attribute
     *
     * @var null|'hide'|'show'|'toggle' $popovertargetaction
     */
    protected ?string $popovertargetaction = null;

    /**
     * step attribute
     */
    protected ?string $step = null;

    /**
     * @var string DEFAULT_ELEMENT
     */
    protected const DEFAULT_ELEMENT = 'input';

    /**
     * Constructs a new instance.
     *
     * @param      string|list{0: string, 1?: string}|null      $id             The identifier
     * @param      string                                       $type           The input type
     * @param      bool                                         $renderLabel    Render label if present
     */
    public function __construct(
        string|array|null $id = null,
        string $type = 'text',
        protected readonly bool $renderLabel = true
    ) {
        parent::__construct(self::class, self::DEFAULT_ELEMENT);
        $this->type($type);
        if ($id !== null) {
            $this->setIdentifier($id);
        }
    }

    /**
     * Set list attribute
     */
    public function list(string $id): static
    {
        $this->list = $id;

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
     * Set pattern attribute
     */
    public function pattern(string $pattern): static
    {
        $this->pattern = $pattern;

        return $this;
    }

    /**
     * Set placeholder attribute
     */
    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * Set popovertarget attribute
     *
     * @return static    self instance, enabling to chain calls
     */
    public function popovertarget(string $popovertarget): static
    {
        $this->popovertarget = $popovertarget;

        return $this;
    }

    /**
     * Set popovertargetaction attribute
     *
     * @param 'hide'|'show'|'toggle' $popovertargetaction
     *
     * @return static    self instance, enabling to chain calls
     */
    public function popovertargetaction(string $popovertargetaction): static
    {
        $this->popovertargetaction = $popovertargetaction;

        return $this;
    }

    /**
     * Set step attribute
     */
    public function step(string $step): static
    {
        $this->step = $step;

        return $this;
    }

    /**
     * Renders the HTML component.
     */
    public function render(): string
    {
        if (!$this->checkMandatoryAttributes()) {
            if ($this->isVerbose()) {
                return '<!-- ' . static::class . ': ' . 'Input (type = ' . $this->type . ') without id and name (provide at least one of them)' . ' -->';
            }

            return '';
        }

        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) .
            $this->renderCommonAttributes() .
            ($this->list !== null ? ' list="' . $this->list . '"' : '') .
            ($this->maxlength !== null ? ' maxlength="' . $this->maxlength . '"' : '') .
            ($this->pattern !== null ? ' pattern="' . $this->pattern . '"' : '') .
            ($this->placeholder !== null ? ' placeholder="' . $this->placeholder . '"' : '') .
            ($this->popovertarget !== null ? ' popovertarget="' . $this->popovertarget . '"' : '') .
            ($this->popovertargetaction !== null ? ' popovertargetaction="' . $this->popovertargetaction . '"' : '') .
            ($this->step !== null ? ' step="' . $this->step . '"' : '') .
            '>';

        if ($this->renderLabel && $this->label instanceof Label) {
            $render = true;
            if ($this->id !== null) {
                $this->label->for($this->id);
            } elseif ($this->label->getPosition() === Label::OL_FT || $this->label->getPosition() === Label::OL_TF) {
                // Do not render label if input is outside label and there is no id for input
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
