<?php

declare(strict_types=1);

/**
 * @class formComponent
 * @brief HTML Forms creation helpers
 *
 * @package Clearbricks
 * @subpackage html.form
 *
 * @since 1.2 First time this was introduced.
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
abstract class formComponent
{
    private $_type;     // Component type
    private $_element;  // HTML element
    private $_data;     // Custom component properties (see __get() and __set())

    public function __construct(?string $type = null, ?string $_element = null)
    {
        $this->_type    = $type ?? __CLASS__;
        $this->_element = $_element;
        $this->_data    = [];
    }

    /**
     * Call statically new instance
     *
     * Use formXxx::init(...$args) to statically create a new instance
     *
     * @return object New formXxx instance
     */
    public static function init(...$args)
    {
        $class = get_called_class();

        /* @phpstan-ignore-next-line */
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
        return array_key_exists($property, $this->_data) ? $this->_data[$property] : null;
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
        $this->_data[$property] = $value;

        return $this;
    }

    /**
     * Magic isset method
     *
     * @param      string  $property  The property
     *
     * @return     bool
     */
    public function __isset(string $property): bool
    {
        return isset($this->_data[$property]);
    }

    /**
     * Magic unset method
     *
     * @param      string  $property  The property
     */
    public function __unset(string $property): void
    {
        unset($this->_data[$property]);
    }

    /**
     * Magic call method
     *
     * If the method exists, call it and return it's return value
     * If not, if there is no argument ($argument empty array), assume that it's a get
     * If not, assume that's is a set (value = $argument[0])
     *
     * @param      string  $method     The property
     * @param      array   $arguments  The arguments
     *
     * @return     mixed   method called, property value (or null), self
     */
    public function __call(string $method, $arguments)
    {
        // Cope with known methods
        if (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], $arguments);
        }

        // Unknown method
        if (!count($arguments)) {
            // No argument, assume its a get
            if (array_key_exists($method, $this->_data)) {
                return $this->_data[$method];
            }

            return null;    // @phpstan-ignore-line
        }
        // Argument here, assume its a set
        $this->_data[$method] = $arguments[0];

        return $this;   // @phpstan-ignore-line
    }

    /**
     * Magic invoke method
     *
     * Return rendering of component
     *
     * @return     string
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
        return $this->_type;
    }

    /**
     * Sets the type of component
     *
     * @param      string  $type   The type
     *
     * @return     self
     */
    public function setType(string $type)
    {
        $this->_type = $type;

        return $this;
    }

    /**
     * Gets the HTML element
     *
     * @return     null|string  The element.
     */
    public function getElement(): ?string
    {
        return $this->_element;
    }

    /**
     * Sets the HTML element
     *
     * @param      string  $element  The element
     *
     * @return     self
     */
    public function setElement(string $element)
    {
        $this->_element = $element;

        return $this;
    }

    /**
     * Attaches the label.
     *
     * @param      formLabel|null  $label     The label
     * @param      int|null        $position  The position
     *
     * @return     self
     */
    public function attachLabel(?formLabel $label = null, ?int $position = null)
    {
        if ($label) {
            $this->label($label);
            $label->for($this->id);
            if ($position !== null) {
                $label->setPosition($position);
            }
        } elseif (isset($this->label)) {
            unset($this->label);
        }

        return $this;
    }

    /**
     * Detaches the label from this component
     *
     * @return     self
     */
    public function detachLabel()
    {
        if (isset($this->label)) {
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
     * @param      string|array|null $identifier (string or array)
     *
     * @return     self
     */
    public function setIdentifier($identifier)
    {
        if (is_string($identifier)) {
            $this->name = $identifier;
            $this->id   = $identifier;
        } elseif (is_array($identifier)) {
            $this->name = $identifier[0];
            if (isset($identifier[1])) {
                $this->id = $identifier[1];
            }
        }

        return $this;
    }

    /**
     * Check mandatory attributes in properties, at least name or id must be present
     *
     * @return     bool
     */
    public function checkMandatoryAttributes(): bool
    {
        // Check for mandatory info
        return (isset($this->name) || isset($this->id));
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
     *          value           => string value.
     *          default         => string default value (will be used if value is not provided).
     *          checked         => boolean checked.
     *
     *          accesskey       => string accesskey (character(s) space separated).
     *          autocomplete    => string autocomplete type.
     *          autofocus       => boolean autofocus.
     *          class           => string (or array of string) class(es).
     *          contenteditable => boolean content editable.
     *          dir             => string direction.
     *          disabled        => boolean disabled.
     *          form            => string form id.
     *          lang            => string lang.
     *          list            => string list id.
     *          max             => int max value.
     *          maxlength       => int max length.
     *          min             => int min value.
     *          readonly        => boolean readonly.
     *          required        => boolean required.
     *          pattern         => string pattern.
     *          placeholder     => string placeholder.
     *          size            => int size.
     *          spellcheck      => boolean spellcheck.
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
     *
     * @return     string
     */
    public function renderCommonAttributes(bool $includeValue = true): string
    {
        $render = '' .

            // Type (used for input component)
            (isset($this->type) ?
                ' type="' . $this->type . '"' : '') .

            // Identifier
            (isset($this->name) ?
                 ' name="' . $this->name . '"' : '') .
            (isset($this->id) ?
                ' id="' . $this->id . '"' : '') .

            // Value
            // - $this->default will be used as value if exists and $this->value does not
            ($includeValue && array_key_exists('value', $this->_data) ?
                ' value="' . $this->value . '"' : '') .
            ($includeValue && !array_key_exists('value', $this->_data) && array_key_exists('default', $this->_data) ?
                ' value="' . $this->default . '"' : '') .
            (isset($this->checked) && $this->checked ?
                ' checked' : '') .

            // Common attributes
            (isset($this->accesskey) ?
                ' accesskey="' . $this->accesskey . '"' : '') .
            (isset($this->autocapitalize) ?
                ' autocapitalize="' . $this->autocapitalize . '"' : '') .
            (isset($this->autocomplete) ?
                ' autocomplete="' . $this->autocomplete . '"' : '') .
            (isset($this->autocorrect) ?
                ' autocorrect="' . $this->autocorrect . '"' : '') .
            (isset($this->autofocus) && $this->autofocus ?
                ' autofocus' : '') .
            (isset($this->class) ?
                ' class="' . (is_array($this->class) ? implode(' ', $this->class) : $this->class) . '"' : '') .
            (isset($this->contenteditable) && $this->contenteditable ?
                ' contenteditable' : '') .
            (isset($this->dir) ?
                ' dir="' . $this->dir . '"' : '') .
            (isset($this->disabled) && $this->disabled ?
                ' disabled' : '') .
            (isset($this->form) ?
                ' form="' . $this->form . '"' : '') .
            (isset($this->lang) ?
                ' lang="' . $this->lang . '"' : '') .
            (isset($this->list) ?
                ' list="' . $this->list . '"' : '') .
            (isset($this->max) ?
                ' max="' . strval((int) $this->max) . '"' : '') .
            (isset($this->maxlength) ?
                ' maxlength="' . strval((int) $this->maxlength) . '"' : '') .
            (isset($this->min) ?
                ' min="' . strval((int) $this->min) . '"' : '') .
            (isset($this->pattern) ?
                ' pattern="' . $this->pattern . '"' : '') .
            (isset($this->placeholder) ?
                ' placeholder="' . $this->placeholder . '"' : '') .
            (isset($this->readonly) && $this->readonly ?
                ' readonly' : '') .
            (isset($this->required) && $this->required ?
                ' required' : '') .
            (isset($this->size) ?
                ' size="' . strval((int) $this->size) . '"' : '') .
            (isset($this->spellcheck) ?
                ' spellcheck="' . ($this->spellcheck ? 'true' : 'false') . '"' : '') .
            (isset($this->tabindex) ?
                ' tabindex="' . strval((int) $this->tabindex) . '"' : '') .
            (isset($this->title) ?
                ' title="' . $this->title . '"' : '') .

        '';

        if (isset($this->data) && is_array($this->data)) {
            // Data attributes
            foreach ($this->data as $key => $value) {
                $render .= ' data-' . $key . '="' . $value . '"';
            }
        }

        if (isset($this->extra)) {
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
