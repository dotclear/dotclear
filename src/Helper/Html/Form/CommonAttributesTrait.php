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
 * @class Component
 * @brief HTML Forms creation helpers
 */
trait CommonAttributesTrait
{
    // Component properties

    // - Identification

    /**
     * id attribute
     */
    protected ?string $id = null;

    /**
     * name attribute
     */
    protected ?string $name = null;

    // - Common attributes

    /**
     * accesskey attribute
     */
    protected ?string $accesskey = null;

    /**
     * autocapitalize attribute
     *
     * @var null|'none'|'off'|'sentences'|'on'|'words'|'characters' $autocapitalize
     */
    protected ?string $autocapitalize = null;

    /**
     * autocomplete attribute
     */
    protected ?string $autocomplete = null;

    /**
     * autocorrect attribute
     *
     * @var null|'on'|'off' $autocorrect
     */
    protected ?string $autocorrect = null;

    /**
     * autofocus attribute
     */
    protected ?bool $autofocus = null;

    /**
     * checked attribute
     */
    protected ?bool $checked = null;

    /**
     * class attribute
     *
     * @var null|string|string[] $class
     */
    protected null|string|array $class = null;

    /**
     * contenteditable attribute
     */
    protected ?bool $contenteditable = null;

    /**
     * data attribute
     *
     * @var ?array<string, string> $data
     */
    protected ?array $data = null;

    /**
     * dir attribute
     *
     * @var null|'ltr'|'rtl'|'auto' $dir
     */
    protected ?string $dir = null;

    /**
     * disabled attribute
     */
    protected ?bool $disabled = null;

    /**
     * enterkeyhint attribute
     *
     * @var null|'enter'|'done'|'go'|'next'|'previous'|'search'|'send' $enterkeyhint
     */
    protected ?string $enterkeyhint = null;

    /**
     * form attribute (ID of the related form)
     */
    protected ?string $form = null;

    /**
     * inert attribute
     */
    protected ?bool $inert = null;

    /**
     * inputmode attribute
     *
     * @var null|'none'|'text'|'decimal'|'numeric'|'tel'|'search'|'email'|'url' $inputmode
     */
    protected ?string $inputmode = null;

    /**
     * lang attribute
     */
    protected ?string $lang = null;

    /**
     * max attribute
     */
    protected null|int|float|string $max = null;

    /**
     * min attribute
     */
    protected null|int|float|string $min = null;

    /**
     * popover attribute
     *
     * @var null|bool|'auto'|'hint'|'manual' $popover
     */
    protected null|bool|string $popover = null;

    /**
     * readonly attribute
     */
    protected ?bool $readonly = null;

    /**
     * required attribute
     */
    protected ?bool $required = null;

    /**
     * size attribute
     */
    protected ?int $size = null;

    /**
     * tabindex attribute
     */
    protected ?int $tabindex = null;

    /**
     * spellcheck attribute
     */
    protected ?bool $spellcheck = null;

    /**
     * title attribute
     */
    protected ?string $title = null;

    /**
     * translate attribute
     */
    protected ?bool $translate = null;

    /**
     * type attribute
     */
    protected ?string $type = null;

    /**
     * value attribute
     */
    protected null|bool|int|float|string $value = null;

    // Setters

    /**
     * Set id attribute
     *
     * @return static    self instance, enabling to chain calls
     */
    public function id(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set name attribute
     *
     * @return static    self instance, enabling to chain calls
     */
    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set accesskey attribute
     *
     * @return static    self instance, enabling to chain calls
     */
    public function accesskey(string $accesskey): static
    {
        $this->accesskey = $accesskey;

        return $this;
    }

    /**
     * Set autocapitalize attribute
     *
     * @param 'none'|'off'|'sentences'|'on'|'words'|'characters' $autocapitalize
     *
     * @return static    self instance, enabling to chain calls
     */
    public function autocapitalize(string $autocapitalize): static
    {
        $this->autocapitalize = $autocapitalize;

        return $this;
    }

    /**
     * Set autocomplete attribute
     */
    public function autocomplete(string $autocomplete): static
    {
        $this->autocomplete = $autocomplete;

        return $this;
    }

    /**
     * Set autocorrect attribute
     *
     * @param 'on'|'off' $autocorrect
     */
    public function autocorrect(string $autocorrect): static
    {
        $this->autocorrect = $autocorrect;

        return $this;
    }

    /**
     * Set autofocus attribute
     */
    public function autofocus(bool $autofocus): static
    {
        $this->autofocus = $autofocus;

        return $this;
    }

    /**
     * Set checked attribute
     */
    public function checked(bool $checked): static
    {
        $this->checked = $checked;

        return $this;
    }

    /**
     * Set class attribute
     *
     * @param string|string[] $class
     */
    public function class(string|array $class): static
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Set contenteditable attribute
     */
    public function contenteditable(bool $contenteditable): static
    {
        $this->contenteditable = $contenteditable;

        return $this;
    }

    /**
     * Set data attribute
     *
     * @param array<string, string> $data
     */
    public function data(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set dir attribute
     *
     * @param 'ltr'|'rtl'|'auto' $dir
     *
     * @return static    self instance, enabling to chain calls
     */
    public function dir(string $dir): static
    {
        $this->dir = $dir;

        return $this;
    }

    /**
     * Set disabled attribute
     */
    public function disabled(bool $disabled): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Set enterkeyhint attribute
     *
     * @param 'enter'|'done'|'go'|'next'|'previous'|'search'|'send' $enterkeyhint
     *
     * @return static    self instance, enabling to chain calls
     */
    public function enterkeyhint(string $enterkeyhint): static
    {
        $this->enterkeyhint = $enterkeyhint;

        return $this;
    }

    /**
     * Set form attribute (ID of the related form)
     */
    public function form(string $id): static
    {
        $this->form = $id;

        return $this;
    }

    /**
     * Set inert attribute
     */
    public function inert(bool $inert): static
    {
        $this->inert = $inert;

        return $this;
    }

    /**
     * Set inputmode attribute
     *
     * @param 'none'|'text'|'decimal'|'numeric'|'tel'|'search'|'email'|'url' $inputmode
     */
    public function inputmode(string $inputmode): static
    {
        $this->inputmode = $inputmode;

        return $this;
    }

    /**
     * Set lang attribute
     */
    public function lang(string $lang): static
    {
        $this->lang = $lang;

        return $this;
    }

    /**
     * Set max attribute
     */
    public function max(null|int|float|string $max): static
    {
        $this->max = $max;

        return $this;
    }

    /**
     * Set min attribute
     */
    public function min(null|int|float|string $min): static
    {
        $this->min = $min;

        return $this;
    }

    /**
     * Set popover attribute
     *
     * @param bool|'auto'|'hint'|'manual' $popover
     */
    public function popover(bool|string $popover): static
    {
        $this->popover = $popover;

        return $this;
    }

    /**
     * Set readonly attribute
     */
    public function readonly(bool $readonly): static
    {
        $this->readonly = $readonly;

        return $this;
    }

    /**
     * Set required attribute
     */
    public function required(bool $required): static
    {
        $this->required = $required;

        return $this;
    }

    /**
     * Set size attribute
     */
    public function size(int $size): static
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Set spellcheck attribute
     */
    public function spellcheck(bool $spellcheck): static
    {
        $this->spellcheck = $spellcheck;

        return $this;
    }

    /**
     * Set tabindex attribute
     */
    public function tabindex(int $tabindex): static
    {
        $this->tabindex = $tabindex;

        return $this;
    }

    /**
     * Set title attribute
     */
    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set translate attribute
     */
    public function translate(bool $translate): static
    {
        $this->translate = $translate;

        return $this;
    }

    /**
     * Set type attribute
     */
    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set value attribute
     */
    public function value(null|bool|int|float|string $value): static
    {
        $this->value = $value;

        return $this;
    }

    // Getters

    /**
     * Get value attribute
     */
    public function getValue(): null|bool|int|float|string
    {
        return $this->value;
    }
}
