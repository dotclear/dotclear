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
 * @class Component
 * @brief HTML Forms creation helpers
 *
 * This class describes a component.
 */
abstract class Component
{
    // Component properties

    use CommonAttributesTrait;
    use AriaAttributesTrait;

    /**
     * Default value, used to set a value or the current selected item
     */
    protected null|string|int|float $default = null;

    /**
     * List of items
     *
     * @var ?Iterable<array-key, Component> $items
     *
     * @todo switch to protected and use $this->hasItems(), $this->getItems()
     */
    public ?Iterable $items = null;

    /**
     * List of fields (form and fieldset)
     *
     * @var ?Iterable<array-key, Component> $fields
     */
    protected ?Iterable $fields = null;

    /**
     * Attached label
     */
    protected ?Label $label = null;

    // Used for rendering

    /**
     * Extra attributes
     *
     * @var null|string|string[] $extra
     */
    protected null|string|array $extra = null;

    /**
     * Format to use (sprintf) to render childs (items, fields, rows, …)
     */
    protected ?string $format = null;

    /**
     * Separator to use (sprintf) between childs (items, fields, rows, …)
     */
    protected ?string $separator = null;

    // Custom properties

    /**
     * Custom component properties (see __get() and __set())
     *
     * @var array<array-key, mixed>     $properties
     */
    protected array $properties = [];

    /**
     * May be use to display development and debug information (disabled in CLI mode)
     */
    protected bool $renderVerbose;

    /**
     * Constructs a new instance.
     *
     * @param      null|class-string<Component>     $componentClass     The component class
     * @param      null|string                      $htmlElement        The html element (will be used to render component)
     */
    public function __construct(
        private ?string $componentClass = null,
        private ?string $htmlElement = null
    ) {
        $this->componentClass ??= self::class;

        $this->renderVerbose = !App::config()->cliMode() && App::config()->devMode() && App::config()->debugMode();
    }

    /**
     * Get current value of renderVerbose property
     */
    public function isVerbose(): bool
    {
        return $this->renderVerbose;
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
     *
     * @deprecated since 2.41
     */
    public function __get(string $property): mixed
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
     *
     * @deprecated since 2.41
     */
    public function __set(string $property, mixed $value)
    {
        $this->properties[$property] = $value;

        // @phpstan-ignore return.void
        return $this;
    }

    /**
     * Magic isset method
     *
     * @param      string  $property  The property
     *
     * @deprecated since 2.41
     */
    public function __isset(string $property): bool
    {
        return isset($this->properties[$property]);
    }

    /**
     * Magic unset method
     *
     * @param      string  $property  The property
     *
     * @deprecated since 2.41
     */
    public function __unset(string $property): void
    {
        unset($this->properties[$property]);
    }

    /**
     * Magic call method
     *
     * If there is no argument ($argument empty array), assume that it's a get
     * If not, assume that's is a set (value = $argument[0])
     *
     * @param      string           $method     The property
     * @param      array<mixed>     $arguments  The arguments
     *
     * @return     mixed   property value (or null), self
     *
     * @deprecated since 2.41
     */
    public function __call(string $method, array $arguments)
    {
        if ($arguments === []) {
            if (array_key_exists($method, $this->properties)) {
                return $this->properties[$method];
            }

            return null;
        }

        // Argument here, assume its a set
        $this->properties[$method] = $arguments[0];

        return $this;
    }

    /**
     * Magic invoke method
     *
     * Return rendering of component
     *
     * @deprecated since 2.41 use render() method instead
     */
    public function __invoke(): string
    {
        return $this->render();
    }

    /**
     * Set default
     *
     * @return static    self instance, enabling to chain calls
     */
    public function default(null|string|int|float $default): static
    {
        $this->default = $default;

        return $this;
    }

    /**
     * Set list of items
     *
     * @param Iterable<array-key, Component> $items
     *
     * @return static    self instance, enabling to chain calls
     */
    public function items(Iterable $items): static
    {
        $this->items = $items;

        return $this;
    }

    /**
     * Set list of fields
     *
     * @param Iterable<array-key, Component> $fields
     *
     * @return static    self instance, enabling to chain calls
     */
    public function fields(Iterable $fields): static
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Set attached label
     *
     * @return static    self instance, enabling to chain calls
     */
    public function label(Label $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Set extra attributes
     *
     * @param string|string[] $extra
     *
     * @return static    self instance, enabling to chain calls
     */
    public function extra(string|array $extra): static
    {
        $this->extra = $extra;

        return $this;
    }

    /**
     * Set format
     *
     * @return static    self instance, enabling to chain calls
     */
    public function format(string $format): static
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Set separator
     *
     * @return static    self instance, enabling to chain calls
     */
    public function separator(string $separator): static
    {
        $this->separator = $separator;

        return $this;
    }

    // Helper methods

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
     * @param      class-string<Component>  $type   The type (class name)
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
     * Check if component has a non empty list of items
     *
     * @since 2.41
     */
    public function hasItems(): bool
    {
        return $this->items !== null && $this->items !== [];
    }

    /**
     * Get list of items
     *
     * @return ?Iterable<array-key, Component>
     *
     * @since 2.41
     */
    public function getItems(): ?Iterable
    {
        return $this->items;
    }

    /**
     * Check if component has a non empty list of fields (form and fieldset)
     *
     * @since 2.41
     */
    public function hasFields(): bool
    {
        return $this->fields !== null && $this->fields !== [];
    }

    /**
     * Get list of fields
     *
     * @return ?Iterable<array-key, Component>
     *
     * @since 2.41
     */
    public function getFields(): ?Iterable
    {
        return $this->fields;
    }

    /**
     * Get attached label
     *
     * @since 2.41
     */
    public function getLabel(): ?Label
    {
        return $this->label;
    }

    /**
     * Attaches the label.
     *
     * @param      Label|null      $label     The label
     * @param      int|null        $position  The position
     *
     * @return static    self instance, enabling to chain calls
     *
     * @deprecated since 2.41 Use label() to attach a label to a component
     */
    public function attachLabel(?Label $label = null, ?int $position = null): static
    {
        if ($label instanceof Label) {
            $this->label($label);
            $label->for($this->id ?? '');
            if ($position !== null) {
                $label->setPosition($position);
            }
        } elseif ($this->label instanceof Label) {
            $this->label = null;
        }

        return $this;
    }

    /**
     * Detaches the label from this component
     *
     * @return static    self instance, enabling to chain calls
     *
     * @deprecated since 2.41
     */
    public function detachLabel(): static
    {
        if ($this->label instanceof Label) {
            $this->label = null;
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
     * A name attribute will be set only on a specific list of HTML element (see list below).
     * You may bypass this by using ->name($name) method.
     *
     * @param      string|list{0: string, 1?: string}|null $identifier (string or array)
     *
     * @return static    self instance, enabling to chain calls
     */
    public function setIdentifier(string|array|null $identifier): static
    {
        $nameable = [
            'button',
            'details',
            'fieldset',
            'form',
            'iframe',
            'input',
            'map',
            'meta',
            'object',
            'output',
            'param',
            'select',
            'textarea',
        ];

        $element = mb_strtolower($this->htmlElement ?? '');
        if (is_array($identifier)) {
            if (in_array($element, $nameable, true)) {
                $this->name($identifier[0]);
            }

            if (isset($identifier[1])) {
                $this->id($identifier[1]);
            }
        } elseif (!is_null($identifier)) {
            if (in_array($element, $nameable, true)) {
                $this->name($identifier);
            }

            $this->id($identifier);
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
     *          hidden          => bool|'hidden'|'until-found' hidden.
     *          lang            => string lang.
     *          max             => int|float max value.
     *          min             => int|float min value.
     *          readonly        => bool readonly.
     *          required        => bool required.
     *          role            => string role.
     *          popover         => 'hint'|'auto'|'manual'|bool popover.
     *          size            => int size.
     *          spellcheck      => boolean spellcheck.
     *          tabindex        => int tabindex.
     *          title           => string title.
     *          translate       => no if false, yes if true
     *
     *          data            => array data.
     *              [
     *                  key   => string data id (rendered as data-<id>).
     *                  value => string data value.
     *              ]
     *
     *          extra           => string (or array of string) extra HTML attributes.
     *
     *          aria-current     => bool|'page'|'step'|'location'|'date'|'time'
     *          aria-describedby => string
     *          aria-details     => string
     *          aria-disabled    => bool
     *          aria-hidden      => bool
     *          aria-label       => string
     *          aria-labelledby  => string
     *
     * @param      bool    $includeValue    Includes $this->value if exist (default = true)
     *                                      should be set to false to textarea and may be some others
     */
    public function renderCommonAttributes(bool $includeValue = true): string
    {
        // Filter some attributes
        $class = $this->class;
        if (is_array($class)) {
            $class = array_filter($class, fn (string $value): bool => !empty($value));
        }

        if ($class === '' || $class === []) {
            $class = null;
        }

        $style = $this->style;
        if (is_array($style)) {
            $style = array_filter($style, fn (string $value): bool => !empty($value));
        }

        if ($style === '' || $style === []) {
            $style = null;
        }

        $extra = $this->extra;
        if (is_array($extra)) {
            $extra = array_filter($extra, fn (string $value): bool => !empty($value));
        }

        if ($extra === '' || $extra === []) {
            $extra = null;
        }

        //Render attributes
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
            ($includeValue && $this->value !== null ?
                ' value="' . $this->value . '"' : '') .
            ($includeValue && $this->value === null && $this->default !== null ?
                ' value="' . $this->default . '"' : '') .

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
            ($class !== null ?
                ' class="' . (is_array($class) ? implode(' ', array_unique($class)) : $class) . '"' : '') .
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
            ($this->hidden !== null && (bool) $this->hidden ?
                ' hidden' . (is_string($this->hidden) ? '="' . $this->hidden . '"' : '') : '') .
            ($this->lang !== null ?
                ' lang="' . $this->lang . '"' : '') .
            ($this->max !== null ?
                ' max="' . strval($this->max) . '"' : '') .
            ($this->min !== null ?
                ' min="' . strval($this->min) . '"' : '') .
            ($this->popover !== null && (bool) $this->popover ?
                ' popover' . (is_string($this->popover) ? '="' . $this->popover . '"' : '') : '') .
            ($this->readonly !== null && $this->readonly ?
                ' readonly' : '') .
            ($this->required !== null && $this->required ?
                ' required' : '') .
            ($this->role !== null && (bool) $this->role ?
                ' role="' . $this->role . '"' : '') .
            ($this->size !== null ?
                ' size="' . strval($this->size) . '"' : '') .
            ($this->spellcheck !== null ?
                ' spellcheck="' . ($this->spellcheck ? 'true' : 'false') . '"' : '') .
            ($style !== null ?
                ' style="' . (is_array($style) ? implode(' ', array_unique($style)) : $style) . '"' : '') .
            ($this->tabindex !== null ?
                ' tabindex="' . strval($this->tabindex) . '"' : '') .
            ($this->title !== null ?
                ' title="' . $this->title . '"' : '') .
            ($this->translate !== null ?
                ' translate="' . ($this->translate ? 'yes' : 'no') . '"' : '') .

        '';

        if ($this->data !== null) {
            // Data attributes
            foreach ($this->data as $key => $value) {
                $render .= ' data-' . $key . '="' . $value . '"';
            }
        }

        if ($extra !== null) {
            // Extra HTML
            $render .= ' ' . (is_array($extra) ? implode(' ', $extra) : $extra);
        }

        // ARIA
        $render .= '' .

            ($this->ariaCurrent !== null && (bool) $this->ariaCurrent ?
                ' aria-current="' . ($this->ariaCurrent === true ? 'true' : $this->ariaCurrent) . '"' : '') .
            ($this->ariaDescribedby !== null ?
                ' aria-describedby="' . $this->ariaDescribedby . '"' : '') .
            ($this->ariaDetails !== null ?
                ' aria-details="' . $this->ariaDetails . '"' : '') .
            ($this->ariaDisabled !== null && $this->ariaDisabled ?
                ' aria-disabled="true"' : '') .
            ($this->ariaHidden !== null && $this->ariaHidden ?
                ' aria-hidden="true"' : '') .
            ($this->ariaLabel !== null ?
                ' aria-label="' . $this->ariaLabel . '"' : '') .
            ($this->ariaLabelledby !== null ?
                ' aria-labelledby="' . $this->ariaLabelledby . '"' : '') .

        '';

        return $render;
    }

    /**
     * Render childs of a Component
     *
     * @param      null|Iterable<Component>     $childs  Childs to render
     * @param      null|string                  $format  The sprintf pattern to render item
     * @param      null|string                  $ignore  The type of child to ignore if present
     * @param      bool                         $inline  Keep all items inline
     */
    protected function renderChilds(?iterable $childs, ?string $format, ?string $ignore = null, bool $inline = false): string
    {
        $buffer = '';

        if ($childs !== null) {
            $first = true;
            $format ??= ($this->format ?? '%s');

            foreach ($childs as $child) {
                if ($child instanceof None) {
                    continue;
                }

                if ($ignore !== null && $child->getDefaultElement() === $ignore) {
                    continue;
                }

                if (!$first && $this->separator !== '') {
                    $buffer .= $this->separator;
                }

                $buffer .= sprintf($format, $inline ? rtrim($child->render(), "\n") : $child->render());
                $first = false;
            }
        }

        return $buffer;
    }

    /**
     * Render items of a Component
     *
     * @param      null|string  $format  The sprintf pattern to render item
     * @param      null|string  $ignore  The type of item to ignore if present
     * @param      bool         $inline  Keep all items inline (removing ending newline from each rendered items)
     */
    public function renderItems(?string $format = null, ?string $ignore = null, bool $inline = false): string
    {
        return $this->renderChilds($this->items, $format, $ignore, $inline);
    }

    /**
     * Render items of a Component
     *
     * @param      null|string  $format  The sprintf pattern to render item
     * @param      null|string  $ignore  The type of field to ignore if present
     * @param      bool         $inline  Keep all items inline (removing ending newline from each rendered items)
     */
    public function renderFields(?string $format = null, ?string $ignore = null, bool $inline = false): string
    {
        return $this->renderChilds($this->fields, $format, $ignore, $inline);
    }

    // Abstract methods

    /**
     * Renders the object.
     *
     * Must be provided by classes which extends this class
     */
    abstract public function render(): string;

    /**
     * Gets the default element.
     *
     * @return     string  The default HTML element.
     */
    abstract public function getDefaultElement(): string;
}
