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
 *
 * This class describes a component.
 *
 * @method      $this accesskey(string $accesskey)
 * @method      $this autocapitalize(string $autocapitalize)
 * @method      $this autocomplete(string $autocomplete)
 * @method      $this autocorrect(string $autocorrect)
 * @method      $this autofocus(bool $autofocus)
 * @method      $this checked(bool $checked)
 * @method      $this class(string|array<string> $class)
 * @method      $this contenteditable(bool $contenteditable)
 * @method      $this default(null|string|int|float $default)
 * @method      $this data(array<string, string> $data)
 * @method      $this dir(string $dir)
 * @method      $this disabled(bool $disabled)
 * @method      $this enterkeyhint(string $enterkeyhint)
 * @method      $this extra(string|array<string> $extra)
 * @method      $this form(string $form)
 * @method      $this id(string $id)
 * @method      $this inert(bool $inert)
 * @method      $this inputmode(string $inputmode)
 * @method      $this label(Label $label)
 * @method      $this lang(string $lang)
 * @method      $this list(string $list)
 * @method      $this max(null|int|float|string $max)
 * @method      $this maxlength(int $maxlength)
 * @method      $this min(null|int|float|string $min)
 * @method      $this name(string $name)
 * @method      $this pattern(string $pattern)
 * @method      $this placeholder(string $placeholder)
 * @method      $this popover(bool $popover)
 * @method      $this readonly(bool $readonly)
 * @method      $this required(bool $required)
 * @method      $this role(string $role)
 * @method      $this size(int $size)
 * @method      $this spellcheck(bool $spellcheck)
 * @method      $this step(string $step)
 * @method      $this tabindex(int $tabindex)
 * @method      $this title(string $title)
 * @method      $this type(string $type)
 * @method      $this value(string|int|float $value)
 *
 * @property    string $accesskey
 * @property    string $autocapitalize
 * @property    string $autocomplete
 * @property    string $autocorrect
 * @property    bool $autofocus
 * @property    bool $checked
 * @property    string|array<string> $class
 * @property    bool $contenteditable
 * @property    array<string, string> $data
 * @property    null|string|int|float $default
 * @property    string $dir
 * @property    bool $disabled
 * @property    string $enterkeyhint
 * @property    string|array<string> $extra
 * @property    string $form
 * @property    string $id
 * @property    bool $inert
 * @property    string $inputmode
 * @property    Label $label
 * @property    string $lang
 * @property    string $list
 * @property    null|int|float|string $max
 * @property    int $maxlength
 * @property    null|int|float|string $min
 * @property    string $name
 * @property    string $pattern
 * @property    string $placeholder
 * @property    bool $popover
 * @property    bool $readonly
 * @property    bool $required
 * @property    string $role
 * @property    int $size
 * @property    bool $spellcheck
 * @property    string $step
 * @property    int $tabindex
 * @property    string $title
 * @property    string $type
 * @property    string|int|float $value
 */
abstract class Component
{
    /**
     * @var array<array-key, mixed> Custom component properties (see __get() and __set())
     */
    protected array $properties = [];

    /**
     * Constructs a new instance.
     *
     * @param      null|string  $componentClass     The component class
     * @param      null|string  $htmlElement        The html element (will be used to render component)
     */
    public function __construct(
        private ?string $componentClass = null,
        private ?string $htmlElement = null
    ) {
        $this->componentClass ??= self::class;
    }

    /**
     * Call statically new instance
     *
     * Use formXxx::init(...$args) to statically create a new instance
     *
     * @param     mixed   ...$args The arguments
     *
     * @return object New formXxx instance
     */
    public static function init(...$args)
    {
        $class = static::class;

        return new $class(...$args);
    }

    /**
     * Magic getter method
     *
     * @param      string  $property  The property
     *
     * @return     mixed   property value if property exists or null
     */
    public function __get(string $property)
    {
        return $this->properties[$property] ?? null;
    }

    /**
     * Magic setter method
     *
     * @param      string  $property  The property
     * @param      mixed   $value     The value
     *
     * @return     self
     */
    public function __set(string $property, $value)
    {
        $this->properties[$property] = $value;

        return $this;   // @phpstan-ignore-line
    }

    /**
     * Magic isset method
     *
     * @param      string  $property  The property
     */
    public function __isset(string $property): bool
    {
        return isset($this->properties[$property]);
    }

    /**
     * Magic unset method
     *
     * @param      string  $property  The property
     */
    public function __unset(string $property): void
    {
        unset($this->properties[$property]);
    }

    /**
     * Magic call method
     *
     * If the method exists, call it and return it's return value
     * If not, if there is no argument ($argument empty array), assume that it's a get
     * If not, assume that's is a set (value = $argument[0])
     *
     * @param      string           $method     The property
     * @param      array<mixed>     $arguments  The arguments
     *
     * @return     mixed   method called, property value (or null), self
     */
    public function __call(string $method, array $arguments)
    {
        // Cope with known methods
        if (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], $arguments);  // @phpstan-ignore-line
        }

        // Unknown method
        if ($arguments === []) {
            // No argument, assume its a get
            if (array_key_exists($method, $this->properties)) {
                return $this->properties[$method];
            }

            return null;    // @phpstan-ignore-line
        }
        // Argument here, assume its a set
        $this->properties[$method] = $arguments[0];

        return $this;   // @phpstan-ignore-line
    }

    /**
     * Magic invoke method
     *
     * Return rendering of component
     */
    public function __invoke(): string
    {
        return $this->render();
    }

    /**
     * Gets the type of component
     *
     * @return     string  The type.
     */
    public function getType(): string
    {
        return $this->componentClass ?? self::class;
    }

    /**
     * Sets the type of component
     *
     * @param      string  $type   The type
     *
     * @return static    self instance, enabling to chain calls
     */
    public function setType(string $type): static
    {
        $this->componentClass = $type;

        return $this;
    }

    /**
     * Gets the HTML element
     *
     * @return     null|string  The element.
     */
    public function getElement(): ?string
    {
        return $this->htmlElement;
    }

    /**
     * Sets the HTML element
     *
     * @param      string  $element  The element
     *
     * @return static    self instance, enabling to chain calls
     */
    public function setElement(string $element): static
    {
        $this->htmlElement = $element;

        return $this;
    }

    /**
     * Attaches the label.
     *
     * @param      Label|null      $label     The label
     * @param      int|null        $position  The position
     *
     * @return static    self instance, enabling to chain calls
     */
    public function attachLabel(?Label $label = null, ?int $position = null): static
    {
        if ($label instanceof Label) {
            $this->label($label);
            $label->for($this->id);
            if ($position !== null) {
                $label->setPosition($position);
            }
        } elseif ($this->label !== null) {
            unset($this->label);
        }

        return $this;
    }

    /**
     * Detaches the label from this component
     *
     * @return static    self instance, enabling to chain calls
     */
    public function detachLabel(): static
    {
        if ($this->label !== null) {
            unset($this->label);
        }

        return $this;
    }

    /**
     * Sets the identifier (name/id).
     *
     * If the given identifier is a string, set name = id = given string
     * If it is an array of only one element, name = [first element]
     * Else name = [first element], id = [second element]
     *
     * @param      string|array{0: string, 1?: string}|null $identifier (string or array)
     *
     * @return static    self instance, enabling to chain calls
     */
    public function setIdentifier($identifier): static
    {
        if (is_array($identifier)) {
            $this->name = (string) $identifier[0];
            if (isset($identifier[1])) {
                $this->id = $identifier[1];
            }
        } elseif (!is_null($identifier)) {
            $this->name = (string) $identifier;
            $this->id   = (string) $identifier;
        }

        return $this;
    }

    /**
     * Check mandatory attributes in properties, at least name or id must be present
     */
    public function checkMandatoryAttributes(): bool
    {
        // Check for mandatory info
        return $this->name !== null || $this->id !== null;
    }

    /**
     * Render common attributes
     *
     *      $this->
     *
     *          type            => string type (may be used for input component).
     *
     *          name            => string name (required if id is not provided).
     *          id              => string id (required if name is not provided).
     *
     *          value           => string|int|float value.
     *          default         => null|string|int|float default value (will be used if value is not provided).
     *          checked         => boolean checked.
     *
     *          accesskey       => string accesskey (character(s) space separated).
     *          autocapitalize  => string autocapitalyze mode.
     *          autocomplete    => string autocomplete type.
     *          autocorrect     => string autocorrect mode.
     *          autofocus       => boolean autofocus.
     *          class           => string (or array of string) class(es).
     *          contenteditable => boolean content editable.
     *          dir             => string direction.
     *          disabled        => boolean disabled.
     *          enterkeyhint    => string enter key hint.
     *          form            => string form id.
     *          inert           => boolean inert.
     *          inputmode       => string inputmode.
     *          lang            => string lang.
     *          list            => string list id.
     *          max             => int|float max value.
     *          maxlength       => int max length.
     *          min             => int|float min value.
     *          readonly        => boolean readonly.
     *          required        => boolean required.
     *          role            => string role.
     *          pattern         => string pattern.
     *          placeholder     => string placeholder.
     *          popover         => bool popover.
     *          size            => int size.
     *          spellcheck      => boolean spellcheck.
     *          step            => string step.
     *          tabindex        => int tabindex.
     *          title           => string title.
     *
     *          data            => array data.
     *              [
     *                  key   => string data id (rendered as data-<id>).
     *                  value => string data value.
     *              ]
     *
     *          extra           => string (or array of string) extra HTML attributes.
     *
     * @param      bool    $includeValue    Includes $this->value if exist (default = true)
     *                                      should be set to false to textarea and may be some others
     */
    public function renderCommonAttributes(bool $includeValue = true): string
    {
        $render = '' .

            // Type (used for input component)
            ($this->type !== null ?
                ' type="' . $this->type . '"' : '') .

            // Identifier
            ($this->name !== null ?
                 ' name="' . $this->name . '"' : '') .
            ($this->id !== null ?
                ' id="' . $this->id . '"' : '') .

            // Value
            // - $this->default will be used as value if exists and $this->value does not
            ($includeValue && array_key_exists('value', $this->properties) ?
                ' value="' . $this->value . '"' : '') .
            ($includeValue && !array_key_exists('value', $this->properties) && array_key_exists('default', $this->properties) ?
                ' value="' . $this->default . '"' : '') .
            ($this->checked !== null && $this->checked ?
                ' checked' : '') .

            // Common attributes
            ($this->accesskey !== null ?
                ' accesskey="' . $this->accesskey . '"' : '') .
            ($this->autocapitalize !== null ?
                ' autocapitalize="' . $this->autocapitalize . '"' : '') .
            ($this->autocomplete !== null ?
                ' autocomplete="' . $this->autocomplete . '"' : '') .
            ($this->autocorrect !== null ?
                ' autocorrect="' . $this->autocorrect . '"' : '') .
            ($this->autofocus !== null && $this->autofocus ?
                ' autofocus' : '') .
            ($this->class !== null ?
                ' class="' . (is_array($this->class) ? implode(' ', array_filter($this->class, fn ($class): bool => $class !== '')) : $this->class) . '"' : '') .
            ($this->contenteditable !== null && $this->contenteditable ?
                ' contenteditable' : '') .
            ($this->dir !== null ?
                ' dir="' . $this->dir . '"' : '') .
            ($this->disabled !== null && $this->disabled ?
                ' disabled' : '') .
            ($this->enterkeyhint !== null ?
                ' enterkeyhint="' . $this->enterkeyhint . '"' : '') .
            ($this->form !== null ?
                ' form="' . $this->form . '"' : '') .
            ($this->inert !== null && $this->inert ?
                ' inert' : '') .
            ($this->inputmode !== null ?
                ' inputmode="' . $this->inputmode . '"' : '') .
            ($this->lang !== null ?
                ' lang="' . $this->lang . '"' : '') .
            ($this->list !== null ?
                ' list="' . $this->list . '"' : '') .
            ($this->max !== null ?
                ' max="' . strval($this->max) . '"' : '') .
            ($this->maxlength !== null ?
                ' maxlength="' . strval((int) $this->maxlength) . '"' : '') .
            ($this->min !== null ?
                ' min="' . strval($this->min) . '"' : '') .
            ($this->pattern !== null ?
                ' pattern="' . $this->pattern . '"' : '') .
            ($this->placeholder !== null ?
                ' placeholder="' . $this->placeholder . '"' : '') .
            ($this->popover !== null && $this->popover ?
                ' popover' : '') .
            ($this->readonly !== null && $this->readonly ?
                ' readonly' : '') .
            ($this->required !== null && $this->required ?
                ' required' : '') .
            ($this->role !== null && $this->role ?
                ' role="' . $this->role . '"' : '') .
            ($this->size !== null ?
                ' size="' . strval((int) $this->size) . '"' : '') .
            ($this->spellcheck !== null ?
                ' spellcheck="' . ($this->spellcheck ? 'true' : 'false') . '"' : '') .
            ($this->step !== null ?
                ' step="' . $this->step . '"' : '') .
            ($this->tabindex !== null ?
                ' tabindex="' . strval((int) $this->tabindex) . '"' : '') .
            ($this->title !== null ?
                ' title="' . $this->title . '"' : '') .

        '';

        if ($this->data !== null) {
            // Data attributes
            foreach ($this->data as $key => $value) {
                $render .= ' data-' . $key . '="' . $value . '"';
            }
        }

        if ($this->extra !== null) {
            // Extra HTML
            $render .= ' ' . (is_array($this->extra) ? implode(' ', $this->extra) : $this->extra);
        }

        return $render;
    }

    // Abstract methods

    /**
     * Renders the object.
     *
     * Must be provided by classes which extends this class
     */
    abstract protected function render(): string;

    /**
     * Gets the default element.
     *
     * @return     string  The default HTML element.
     */
    abstract protected function getDefaultElement(): string;
}
