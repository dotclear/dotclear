<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */

use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Color;
use Dotclear\Helper\Html\Form\Date;
use Dotclear\Helper\Html\Form\Datetime;
use Dotclear\Helper\Html\Form\Email;
use Dotclear\Helper\Html\Form\File;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Number;
use Dotclear\Helper\Html\Form\Option;
use Dotclear\Helper\Html\Form\Password;
use Dotclear\Helper\Html\Form\Radio;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Form\Time;
use Dotclear\Helper\Html\Form\Url;

/**
 * @class form and formSelectOption
 * @brief HTML Form helpers
 *
 * @deprecated  Since 2.26, use Dotclear\Helper\Html\Form\* instead
 */
class form
{
    /**
     * return an associative array of optional parameters of a class method
     *
     * @param  string  $class   class name
     * @param  string  $method  method name
     *
     * @return array<string, mixed>
     */
    private static function getDefaults(string $class, string $method): array
    {
        $options = [];
        $reflect = new ReflectionMethod($class, $method);
        foreach ($reflect->getParameters() as $param) {
            if ($param->isOptional()) {
                $options[$param->getName()] = $param->getDefaultValue();
            }
        }

        return $options;
    }

    /**
     * Select Box
     *
     * Returns HTML code for a select box.
     * $nid could be a string or an array of name and ID.
     * $data is an array with option titles keys and values in values
     * or an array of object of type {@link formSelectOption}. If $data is an array of
     * arrays, optgroups will be created.
     *
     * $default could be a string or an associative array of any of optional parameters:
     *
     * ```php
     * form::combo(['name', 'id'], $data, ['class' => 'maximal', 'extra_html' => 'data-language="php"']);
     * ```
     *
     * @param string|array{0: string, 1?: string}|null     $nid       The identifier
     * @param mixed                 $data        Select box data
     * @param mixed                 $default     Default value in select box | associative array of optional parameters
     * @param string                $class       Element class name
     * @param string                $tabindex    Element tabindex
     * @param boolean               $disabled    True if disabled
     * @param string                $extra_html  Extra HTML attributes
     *
     * @deprecated Since 2.26, use Dotclear/Helper/Html/Form/Select instead
     */
    public static function combo(
        $nid,
        $data,
        $default = '',
        ?string $class = '',
        ?string $tabindex = '',
        bool $disabled = false,
        ?string $extra_html = ''
    ): string {
        if (func_num_args() > 2 && is_array($default)) {
            // Cope with associative array of optional parameters
            $options = self::getDefaults(self::class, __FUNCTION__);
            $args    = [...$options, ...array_intersect_key($default, $options)];
            extract($args);
        }

        $component = new Select($nid);
        if ($class) {
            $component->class($class);
        }
        if ($tabindex) {
            $component->tabindex((int) $tabindex);
        }
        if ($disabled) {
            $component->disabled(true);
        }
        if ($extra_html) {
            $component->extra($extra_html);
        }
        $component->items($data);

        return $component->render($default);
    }

    /**
     * Radio button
     *
     * Returns HTML code for a radio button.
     * $nid could be a string or an array of name and ID.
     * $checked could be a boolean or an associative array of any of optional parameters
     *
     * @param string|array{0: string, 1?: string}|null     $nid       The identifier
     * @param mixed                 $value       Element value
     * @param mixed                 $checked     True if checked | associative array of optional parameters
     * @param string                $class       Element class name
     * @param string                $tabindex    Element tabindex
     * @param boolean               $disabled    True if disabled
     * @param string                $extra_html  Extra HTML attributes
     *
     * @deprecated Since 2.26, use Dotclear/Helper/Html/Form/Radio instead
     */
    public static function radio(
        $nid,
        $value,
        $checked = false,
        ?string $class = '',
        ?string $tabindex = '',
        bool $disabled = false,
        ?string $extra_html = ''
    ): string {
        if (func_num_args() > 2 && is_array($checked)) {
            // Cope with associative array of optional parameters
            $options = self::getDefaults(self::class, __FUNCTION__);
            $args    = [...$options, ...array_intersect_key($checked, $options)];
            extract($args);
        }

        $component = new Radio($nid, $checked);
        $component->value($value);
        if ($class) {
            $component->class($class);
        }
        if ($tabindex) {
            $component->tabindex((int) $tabindex);
        }
        if ($disabled) {
            $component->disabled(true);
        }
        if ($extra_html) {
            $component->extra($extra_html);
        }

        return $component->render();
    }

    /**
     * Checkbox
     *
     * Returns HTML code for a checkbox.
     * $nid could be a string or an array of name and ID.
     * $checked could be a boolean or an associative array of any of optional parameters
     *
     * @param string|array{0: string, 1?: string}|null     $nid       The identifier
     * @param mixed                 $value       Element value
     * @param mixed                 $checked     True if checked | associative array of optional parameters
     * @param string                $class       Element class name
     * @param string                $tabindex    Element tabindex
     * @param boolean               $disabled    True if disabled
     * @param string                $extra_html  Extra HTML attributes
     *
     * @deprecated Since 2.26, use Dotclear/Helper/Html/Form/Checkbox instead
     */
    public static function checkbox(
        $nid,
        $value,
        $checked = false,
        ?string $class = '',
        ?string $tabindex = '',
        bool $disabled = false,
        ?string $extra_html = ''
    ): string {
        if (func_num_args() > 2 && is_array($checked)) {
            // Cope with associative array of optional parameters
            $options = self::getDefaults(self::class, __FUNCTION__);
            $args    = [...$options, ...array_intersect_key($checked, $options)];
            extract($args);
        }

        $component = new Checkbox($nid, $checked);
        $component->value($value);
        if ($class) {
            $component->class($class);
        }
        if ($tabindex) {
            $component->tabindex((int) $tabindex);
        }
        if ($disabled) {
            $component->disabled(true);
        }
        if ($extra_html) {
            $component->extra($extra_html);
        }

        return $component->render();
    }

    /**
     * Input field
     *
     * Returns HTML code for an input field.
     * $nid could be a string or an array of name and ID.
     * $default could be a string or an associative array of any of optional parameters
     *
     * @param string|array{0: string, 1?: string}|null     $nid       The identifier
     * @param integer               $size         Element size
     * @param integer               $max          Element maxlength
     * @param mixed                 $default      Element value | associative array of optional parameters
     * @param string                $class        Element class name
     * @param string                $tabindex     Element tabindex
     * @param boolean               $disabled     True if disabled
     * @param string                $extra_html   Extra HTML attributes
     * @param boolean               $required     Element is required
     * @param string                $type         Input type
     * @param string                $autocomplete Autocomplete attributes if relevant
     *
     * @deprecated Since 2.26, use Dotclear/Helper/Html/Form/Input instead
     */
    public static function field(
        $nid,
        ?int $size,
        ?int $max,
        $default = '',
        ?string $class = '',
        ?string $tabindex = '',
        bool $disabled = false,
        ?string $extra_html = '',
        bool $required = false,
        ?string $type = 'text',
        ?string $autocomplete = ''
    ): string {
        if (func_num_args() > 3 && is_array($default)) {
            // Cope with associative array of optional parameters
            $options = self::getDefaults(self::class, __FUNCTION__);
            $args    = [...$options, ...array_intersect_key($default, $options)];
            extract($args);
        }

        $component = new Input($nid, $type ?? 'text');
        if ($default || $default === '0') {
            $component->value($default);
        }
        if ($size) {
            $component->size($size);
        }
        if ($max) {
            $component->maxlength($max);
        }
        if ($class) {
            $component->class($class);
        }
        if ($tabindex) {
            $component->tabindex((int) $tabindex);
        }
        if ($disabled) {
            $component->disabled(true);
        }
        if ($required) {
            $component->required(true);
        }
        if ($autocomplete) {
            $component->autocomplete($autocomplete);
        }
        if ($extra_html) {
            $component->extra($extra_html);
        }

        return $component->render();
    }

    /**
     * Password field
     *
     * Returns HTML code for a password field.
     * $nid could be a string or an array of name and ID.
     * $default could be a string or an associative array of any of optional parameters
     *
     * @param string|array{0: string, 1?: string}|null     $nid       The identifier
     * @param integer               $size        Element size
     * @param integer               $max         Element maxlength
     * @param mixed                 $default     Element value | associative array of optional parameters
     * @param string                $class       Element class name
     * @param string                $tabindex    Element tabindex
     * @param boolean               $disabled    True if disabled
     * @param string                $extra_html  Extra HTML attributes
     * @param boolean               $required    Element is required
     * @param string                $autocomplete Autocomplete attributes if relevant (new-password/current-password)
     *
     * @deprecated Since 2.26, use Dotclear/Helper/Html/Form/Password instead
     */
    public static function password(
        $nid,
        int $size,
        ?int $max,
        $default = '',
        ?string $class = '',
        ?string $tabindex = '',
        bool $disabled = false,
        ?string $extra_html = '',
        bool $required = false,
        ?string $autocomplete = ''
    ): string {
        if (func_num_args() > 3 && is_array($default)) {
            // Cope with associative array of optional parameters
            $options = self::getDefaults(self::class, __FUNCTION__);
            $args    = [...$options, ...array_intersect_key($default, $options)];
            extract($args);
        }

        $component = new Password($nid);
        if ($default || $default === '0') {
            $component->value($default);
        }
        if ($size !== 0) {
            $component->size($size);
        }
        if ($max) {
            $component->maxlength($max);
        }
        if ($class) {
            $component->class($class);
        }
        if ($tabindex) {
            $component->tabindex((int) $tabindex);
        }
        if ($disabled) {
            $component->disabled(true);
        }
        if ($required) {
            $component->required(true);
        }
        if ($autocomplete) {
            $component->autocomplete($autocomplete);
        }
        if ($extra_html) {
            $component->extra($extra_html);
        }

        return $component->render();
    }

    /**
     * HTML5 Color field
     *
     * Returns HTML code for an input color field.
     * $nid could be a string or an array of name and ID.
     * $size could be a integer or an associative array of any of optional parameters
     *
     * @param string|array{0: string, 1?: string}|null     $nid       The identifier
     * @param mixed                 $size        Element size | associative array of optional parameters
     * @param integer               $max         Element maxlength
     * @param string                $default     Element value
     * @param string                $class       Element class name
     * @param string                $tabindex    Element tabindex
     * @param boolean               $disabled    True if disabled
     * @param string                $extra_html  Extra HTML attributes
     * @param boolean               $required    Element is required
     * @param string                $autocomplete Autocomplete attributes if relevant
     *
     * @deprecated Since 2.26, use Dotclear/Helper/Html/Form/Color instead
     */
    public static function color(
        $nid,
        $size = 7,
        ?int $max = 7,
        ?string $default = '',
        ?string $class = '',
        ?string $tabindex = '',
        bool $disabled = false,
        ?string $extra_html = '',
        bool $required = false,
        ?string $autocomplete = ''
    ): string {
        if (func_num_args() > 1 && is_array($size)) {
            // Cope with associative array of optional parameters
            $options = self::getDefaults(self::class, __FUNCTION__);
            $args    = [...$options, ...array_intersect_key($size, $options)];
            extract($args);
        }

        $component = new Color($nid);
        if ($default || $default === '0') {
            $component->value($default);
        }
        if ($size) {
            $component->size($size);
        }
        if ($max) {
            $component->maxlength($max);
        }
        if ($class) {
            $component->class($class);
        }
        if ($tabindex) {
            $component->tabindex((int) $tabindex);
        }
        if ($disabled) {
            $component->disabled(true);
        }
        if ($required) {
            $component->required(true);
        }
        if ($autocomplete) {
            $component->autocomplete($autocomplete);
        }
        if ($extra_html) {
            $component->extra($extra_html);
        }

        return $component->render();
    }

    /**
     * HTML5 Email field
     *
     * Returns HTML code for an input email field.
     * $nid could be a string or an array of name and ID.
     * $size could be a integer or an associative array of any of optional parameters
     *
     * @param string|array{0: string, 1?: string}|null     $nid       The identifier
     * @param mixed                 $size         Element size | associative array of optional parameters
     * @param integer               $max          Element maxlength
     * @param string                $default      Element value
     * @param string                $class        Element class name
     * @param string                $tabindex     Element tabindex
     * @param boolean               $disabled     True if disabled
     * @param string                $extra_html   Extra HTML attributes
     * @param boolean               $required     Element is required
     * @param string                $autocomplete Autocomplete attributes if relevant
     *
     * @deprecated Since 2.26, use Dotclear/Helper/Html/Form/Email instead
     */
    public static function email(
        $nid,
        $size = 20,
        ?int $max = 255,
        ?string $default = '',
        ?string $class = '',
        ?string $tabindex = '',
        bool $disabled = false,
        ?string $extra_html = '',
        bool $required = false,
        ?string $autocomplete = ''
    ): string {
        if (func_num_args() > 1 && is_array($size)) {
            // Cope with associative array of optional parameters
            $options = self::getDefaults(self::class, __FUNCTION__);
            $args    = [...$options, ...array_intersect_key($size, $options)];
            extract($args);
        }

        $component = new Email($nid);
        if ($default || $default === '0') {
            $component->value($default);
        }
        if ($size) {
            $component->size($size);
        }
        if ($max) {
            $component->maxlength($max);
        }
        if ($class) {
            $component->class($class);
        }
        if ($tabindex) {
            $component->tabindex((int) $tabindex);
        }
        if ($disabled) {
            $component->disabled(true);
        }
        if ($required) {
            $component->required(true);
        }
        if ($autocomplete) {
            $component->autocomplete($autocomplete);
        }
        if ($extra_html) {
            $component->extra($extra_html);
        }

        return $component->render();
    }

    /**
     * HTML5 URL field
     *
     * Returns HTML code for an input (absolute) URL field.
     * $nid could be a string or an array of name and ID.
     * $size could be a integer or an associative array of any of optional parameters
     *
     * @param string|array{0: string, 1?: string}|null     $nid       The identifier
     * @param mixed                 $size         Element size | associative array of optional parameters
     * @param integer               $max          Element maxlength
     * @param string                $default      Element value
     * @param string                $class        Element class name
     * @param string                $tabindex     Element tabindex
     * @param boolean               $disabled     True if disabled
     * @param string                $extra_html   Extra HTML attributes
     * @param boolean               $required     Element is required
     * @param string                $autocomplete Autocomplete attributes if relevant
     *
     * @deprecated Since 2.26, use Dotclear/Helper/Html/Form/Url instead
     */
    public static function url(
        $nid,
        $size = 20,
        ?int $max = 255,
        ?string $default = '',
        ?string $class = '',
        ?string $tabindex = '',
        bool $disabled = false,
        ?string $extra_html = '',
        bool $required = false,
        ?string $autocomplete = ''
    ): string {
        if (func_num_args() > 1 && is_array($size)) {
            // Cope with associative array of optional parameters
            $options = self::getDefaults(self::class, __FUNCTION__);
            $args    = [...$options, ...array_intersect_key($size, $options)];
            extract($args);
        }

        $component = new Url($nid);
        if ($default || $default === '0') {
            $component->value($default);
        }
        if ($size) {
            $component->size($size);
        }
        if ($max) {
            $component->maxlength($max);
        }
        if ($class) {
            $component->class($class);
        }
        if ($tabindex) {
            $component->tabindex((int) $tabindex);
        }
        if ($disabled) {
            $component->disabled(true);
        }
        if ($required) {
            $component->required(true);
        }
        if ($autocomplete) {
            $component->autocomplete($autocomplete);
        }
        if ($extra_html) {
            $component->extra($extra_html);
        }

        return $component->render();
    }

    /**
     * HTML5 Datetime (local) field
     *
     * Returns HTML code for an input datetime field.
     * $nid could be a string or an array of name and ID.
     * $size could be a integer or an associative array of any of optional parameters
     *
     * @param string|array{0: string, 1?: string}|null     $nid       The identifier
     * @param mixed                 $size         Element size | associative array of optional parameters
     * @param integer               $max          Element maxlength
     * @param string                $default      Element value (in YYYY-MM-DDThh:mm format)
     * @param string                $class        Element class name
     * @param string                $tabindex     Element tabindex
     * @param boolean               $disabled     True if disabled
     * @param string                $extra_html   Extra HTML attributes
     * @param boolean               $required     Element is required
     * @param string                $autocomplete Autocomplete attributes if relevant
     *
     * @deprecated Since 2.26, use Dotclear/Helper/Html/Form/Datetime instead
     */
    public static function datetime(
        $nid,
        $size = 16,
        ?int $max = 16,
        ?string $default = '',
        ?string $class = '',
        ?string $tabindex = '',
        bool $disabled = false,
        ?string $extra_html = '',
        bool $required = false,
        ?string $autocomplete = ''
    ): string {
        if (func_num_args() > 1 && is_array($size)) {
            // Cope with associative array of optional parameters
            $options = self::getDefaults(self::class, __FUNCTION__);
            $args    = [...$options, ...array_intersect_key($size, $options)];
            extract($args);
        }

        $component = new Datetime($nid);
        if ($default || $default === '0') {
            $component->value($default);
        }
        if ($size) {
            $component->size($size);
        }
        if ($max) {
            $component->maxlength($max);
        }
        if ($class) {
            $component->class($class);
        }
        if ($tabindex) {
            $component->tabindex((int) $tabindex);
        }
        if ($disabled) {
            $component->disabled(true);
        }
        if ($required) {
            $component->required(true);
        }
        if ($autocomplete) {
            $component->autocomplete($autocomplete);
        }
        if ($extra_html) {
            $component->extra($extra_html);
        }

        return $component->render();
    }

    /**
     * HTML5 Date field
     *
     * Returns HTML code for an input date field.
     * $nid could be a string or an array of name and ID.
     * $size could be a integer or an associative array of any of optional parameters
     *
     * @param string|array{0: string, 1?: string}|null     $nid       The identifier
     * @param mixed                 $size         Element size | associative array of optional parameters
     * @param integer               $max          Element maxlength
     * @param string                $default      Element value (in YYYY-MM-DD format)
     * @param string                $class        Element class name
     * @param string                $tabindex     Element tabindex
     * @param boolean               $disabled     True if disabled
     * @param string                $extra_html   Extra HTML attributes
     * @param boolean               $required     Element is required
     * @param string                $autocomplete Autocomplete attributes if relevant
     *
     * @deprecated Since 2.26, use Dotclear/Helper/Html/Form/Date instead
     */
    public static function date(
        $nid,
        $size = 10,
        ?int $max = 10,
        ?string $default = '',
        ?string $class = '',
        ?string $tabindex = '',
        bool $disabled = false,
        ?string $extra_html = '',
        bool $required = false,
        ?string $autocomplete = ''
    ): string {
        if (func_num_args() > 1 && is_array($size)) {
            // Cope with associative array of optional parameters
            $options = self::getDefaults(self::class, __FUNCTION__);
            $args    = [...$options, ...array_intersect_key($size, $options)];
            extract($args);
        }

        $component = new Date($nid);
        if ($default || $default === '0') {
            $component->value($default);
        }
        if ($size) {
            $component->size($size);
        }
        if ($max) {
            $component->maxlength($max);
        }
        if ($class) {
            $component->class($class);
        }
        if ($tabindex) {
            $component->tabindex((int) $tabindex);
        }
        if ($disabled) {
            $component->disabled(true);
        }
        if ($required) {
            $component->required(true);
        }
        if ($autocomplete) {
            $component->autocomplete($autocomplete);
        }
        if ($extra_html) {
            $component->extra($extra_html);
        }

        return $component->render();
    }

    /**
     * HTML5 Time (local) field
     *
     * Returns HTML code for an input time field.
     * $nid could be a string or an array of name and ID.
     * $size could be a integer or an associative array of any of optional parameters
     *
     * @param string|array{0: string, 1?: string}|null     $nid       The identifier
     * @param mixed                 $size         Element size | associative array of optional parameters
     * @param integer               $max          Element maxlength
     * @param string                $default      Element value (in hh:mm format)
     * @param string                $class        Element class name
     * @param string                $tabindex     Element tabindex
     * @param boolean               $disabled     True if disabled
     * @param string                $extra_html   Extra HTML attributes
     * @param boolean               $required     Element is required
     * @param string                $autocomplete Autocomplete attributes if relevant
     *
     * @deprecated Since 2.26, use Dotclear/Helper/Html/Form/Time instead
     */
    public static function time(
        $nid,
        $size = 5,
        ?int $max = 5,
        ?string $default = '',
        ?string $class = '',
        ?string $tabindex = '',
        bool $disabled = false,
        ?string $extra_html = '',
        bool $required = false,
        ?string $autocomplete = ''
    ): string {
        if (func_num_args() > 1 && is_array($size)) {
            // Cope with associative array of optional parameters
            $options = self::getDefaults(self::class, __FUNCTION__);
            $args    = [...$options, ...array_intersect_key($size, $options)];
            extract($args);
        }

        $component = new Time($nid);
        if ($default || $default === '0') {
            $component->value($default);
        }
        if ($size) {
            $component->size($size);
        }
        if ($max) {
            $component->maxlength($max);
        }
        if ($class) {
            $component->class($class);
        }
        if ($tabindex) {
            $component->tabindex((int) $tabindex);
        }
        if ($disabled) {
            $component->disabled(true);
        }
        if ($required) {
            $component->required(true);
        }
        if ($autocomplete) {
            $component->autocomplete($autocomplete);
        }
        if ($extra_html) {
            $component->extra($extra_html);
        }

        return $component->render();
    }

    /**
     * HTML5 file field
     *
     * Returns HTML code for an input file field.
     * $nid could be a string or an array of name and ID.
     * $default could be a integer or an associative array of any of optional parameters
     *
     * @param string|array{0: string, 1?: string}|null     $nid       The identifier
     * @param mixed                 $default     Element value | associative array of optional parameters
     * @param string                $class       Element class name
     * @param string                $tabindex    Element tabindex
     * @param boolean               $disabled    True if disabled
     * @param string                $extra_html  Extra HTML attributes
     * @param boolean               $required    Element is required
     *
     * @deprecated Since 2.26, use Dotclear/Helper/Html/Form/File instead
     */
    public static function file(
        $nid,
        $default = '',
        ?string $class = '',
        ?string $tabindex = '',
        bool $disabled = false,
        ?string $extra_html = '',
        bool $required = false
    ): string {
        if (func_num_args() > 1 && is_array($default)) {
            // Cope with associative array of optional parameters
            $options = self::getDefaults(self::class, __FUNCTION__);
            $args    = [...$options, ...array_intersect_key($default, $options)];
            extract($args);
        }

        $component = new File($nid);
        if ($default || $default === '0') {
            $component->value($default);
        }
        if ($class) {
            $component->class($class);
        }
        if ($tabindex) {
            $component->tabindex((int) $tabindex);
        }
        if ($disabled) {
            $component->disabled(true);
        }
        if ($required) {
            $component->required(true);
        }
        if ($extra_html) {
            $component->extra($extra_html);
        }

        return $component->render();
    }

    /**
     * HTML5 number input field
     *
     * Returns HTML code for an number input field.
     * $nid could be a string or an array of name and ID.
     * $min could be a string or an associative array of any of optional parameters
     *
     * @param string|array{0: string, 1?: string}|null     $nid       The identifier
     * @param mixed                 $min          Element min value (may be negative) | associative array of optional parameters
     * @param integer               $max          Element max value (may be negative)
     * @param string                $default      Element value
     * @param string                $class        Element class name
     * @param string                $tabindex     Element tabindex
     * @param boolean               $disabled     True if disabled
     * @param string                $extra_html   Extra HTML attributes
     * @param boolean               $required     Element is required
     * @param string                $autocomplete Autocomplete attributes if relevant
     *
     * @deprecated Since 2.26, use Dotclear/Helper/Html/Form/Number instead
     */
    public static function number(
        $nid,
        $min = null,
        ?int $max = null,
        ?string $default = '',
        ?string $class = '',
        ?string $tabindex = '',
        bool $disabled = false,
        ?string $extra_html = '',
        bool $required = false,
        ?string $autocomplete = ''
    ): string {
        if (func_num_args() > 1 && is_array($min)) {
            // Cope with associative array of optional parameters
            $options = self::getDefaults(self::class, __FUNCTION__);
            $args    = [...$options, ...array_intersect_key($min, $options)];
            extract($args);
        }

        $component = new Number($nid, $min, $max);
        if ($default || $default === '0') {
            $component->value($default);
        }
        if ($class) {
            $component->class($class);
        }
        if ($tabindex) {
            $component->tabindex((int) $tabindex);
        }
        if ($disabled) {
            $component->disabled(true);
        }
        if ($required) {
            $component->required(true);
        }
        if ($autocomplete) {
            $component->autocomplete($autocomplete);
        }
        if ($extra_html) {
            $component->extra($extra_html);
        }

        return $component->render();
    }

    /**
     * Textarea
     *
     * Returns HTML code for a textarea.
     * $nid could be a string or an array of name and ID.
     * $default could be a string or an associative array of any of optional parameters
     *
     * @param string|array{0: string, 1?: string}|null     $nid       The identifier
     * @param integer               $cols         Number of columns
     * @param integer               $rows         Number of rows
     * @param mixed                 $default      Element value | associative array of optional parameters
     * @param string                $class        Element class name
     * @param string                $tabindex     Element tabindex
     * @param boolean               $disabled     True if disabled
     * @param string                $extra_html   Extra HTML attributes
     * @param boolean               $required     Element is required
     * @param string                $autocomplete Autocomplete attributes if relevant
     *
     * @deprecated Since 2.26, use Dotclear/Helper/Html/Form/Textarea instead
     */
    public static function textArea(
        $nid,
        int $cols,
        int $rows,
        $default = '',
        ?string $class = '',
        ?string $tabindex = '',
        bool $disabled = false,
        ?string $extra_html = '',
        bool $required = false,
        ?string $autocomplete = ''
    ): string {
        if (func_num_args() > 3 && is_array($default)) {
            // Cope with associative array of optional parameters
            $options = self::getDefaults(self::class, __FUNCTION__);
            $args    = [...$options, ...array_intersect_key($default, $options)];
            extract($args);
        }

        $component = new Textarea($nid, $default);
        $component
            ->cols($cols)
            ->rows($rows);
        if ($tabindex != '') {
            $component->tabindex((int) $tabindex);
        }
        if ($class) {
            $component->class($class);
        }
        if ($disabled) {
            $component->disabled(true);
        }
        if ($required) {
            $component->required(true);
        }
        if ($autocomplete) {
            $component->autocomplete($autocomplete);
        }
        if ($extra_html) {
            $component->extra($extra_html);
        }

        return $component->render();
    }

    /**
     * Hidden field
     *
     * Returns HTML code for an hidden field. $nid could be a string or an array of
     * name and ID.
     *
     * @param string|array{0: string, 1?: string}|null      $nid        The identifier
     * @param mixed                                         $value      Element value
     *
     * @deprecated Since 2.26, use Dotclear/Helper/Html/Form/Hidden instead
     */
    public static function hidden($nid, $value): string
    {
        return (new Hidden($nid, $value))->render();
    }
}

/**
 * @class formSelectOption
 * @brief HTML Forms creation helpers
 *
 * @package Clearbricks
 * @subpackage Common
 *
 * @deprecated Since 2.26, use Dotclear/Helper/Html/Form/Option instead
 */
class formSelectOption extends Option
{
    /**
     * Option constructor
     *
     * @param string  $name        Option name
     * @param mixed   $value       Option value
     * @param string  $class_name  Element class name
     * @param string  $html        Extra HTML attributes
     */
    public function __construct(string $name, $value, string $class_name = '', string $html = '')
    {
        parent::__construct($name, strval($value));
        if ($class_name !== '') {
            $this->class($class_name);
        }
        if ($html !== '') {
            $this->extra($html);
        }
    }
}
