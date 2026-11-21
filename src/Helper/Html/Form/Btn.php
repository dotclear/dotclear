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
 */
class Btn extends Component
{
    /**
     * Text content
     */
    protected ?string $text = null;

    /**
     * The command attribute
     */
    protected ?string $command = null;

    /**
     * The commandfor attribute
     */
    protected ?string $commandfor = null;

    /**
     * The popovertarget attribute
     */
    protected ?string $popovertarget = null;

    /**
     * The popovertargetaction attribute
     *
     * @var null|'hide'|'show'|'toggle' $popovertargetaction
     */
    protected ?string $popovertargetaction = null;

    /**
     * @var string DEFAULT_ELEMENT
     */
    private const DEFAULT_ELEMENT = 'button';

    /**
     * Constructs a new instance.
     *
     * @param      string|list{0: string, 1?: string}|null      $id       The identifier
     * @param      string                                       $value    The value
     * @param      string                                       $element  The element
     */
    public function __construct(string|array|null $id = null, ?string $value = null, ?string $element = null)
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
     * Set text
     *
     * @return static    self instance, enabling to chain calls
     */
    public function text(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Set the command attribute
     *
     * @return static    self instance, enabling to chain calls
     */
    public function command(string $command): static
    {
        $this->command = $command;

        return $this;
    }

    /**
     * Set the commandfor attribute
     *
     * @return static    self instance, enabling to chain calls
     */
    public function commandfor(string $commandfor): static
    {
        $this->commandfor = $commandfor;

        return $this;
    }

    /**
     * Set the popovertarget attribute
     *
     * @return static    self instance, enabling to chain calls
     */
    public function popovertarget(string $popovertarget): static
    {
        $this->popovertarget = $popovertarget;

        return $this;
    }

    /**
     * Set the popovertargetaction attribute
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
     * Renders the HTML component.
     */
    public function render(): string
    {
        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) .
            ($this->command !== null ? ' command="' . $this->command . '"' : '') .
            ($this->commandfor !== null ? ' commandfor="' . $this->commandfor . '"' : '') .
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
