<?php
/**
 * @class form
 * @brief HTML Forms creation helpers
 *
 * @package Clearbricks
 * @subpackage Common
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class form
{
    /**
     * return id and name from given argument
     *
     * @param  string|array $nid   input argument
     * @param  string       $name  returned name
     * @param  string       $id    returned id
     *
     * @static
     * @access private
     */
    private static function getNameAndId($nid, &$name, &$id): void
    {
        if (is_array($nid)) {
            $name = $nid[0];
            $id   = !empty($nid[1]) ? $nid[1] : null;
        } else {
            $name = $id = $nid;
        }
    }

    /**
     * return an associative array of optional parameters of a class method
     *
     * @param  string  $class   class name
     * @param  string  $method  method name
     * @return array
     *
     * @static
     * @access private
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
     * **$nid** could be a string or an array of name and ID.
     * **$data** is an array with option titles keys and values in values
     * or an array of object of type {@link formSelectOption}. If **$data** is an array of
     * arrays, optgroups will be created.
     *
     * **$default** could be a string or an associative array of any of optional parameters:
     *
     * ```php
     * form::combo(['name', 'id'], $data, ['class' => 'maximal', 'extra_html' => 'data-language="php"']);
     * ```
     *
     * @uses formSelectOption
     *
     * @param string|array  $nid         Element ID and name
     * @param mixed         $data        Select box data
     * @param mixed         $default     Default value in select box | associative array of optional parameters
     * @param string        $class       Element class name
     * @param string        $tabindex    Element tabindex
     * @param boolean       $disabled    True if disabled
     * @param string        $extra_html  Extra HTML attributes
     *
     * @return string
     *
     * @static
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
        self::getNameAndId($nid, $name, $id);
        if (func_num_args() > 2 && is_array($default)) {
            // Cope with associative array of optional parameters
            $options = self::getDefaults(self::class, __FUNCTION__);
            $args    = array_merge($options, array_intersect_key($default, $options));
            extract($args);
        }

        return '<select name="' . $name . '" ' .

        ($id ? 'id="' . $id . '" ' : '') .
        ($class ? 'class="' . $class . '" ' : '') .
        ($tabindex ? 'tabindex="' . strval((int) $tabindex) . '" ' : '') .
        ($disabled ? 'disabled ' : '') .
        $extra_html .

        '>' . "\n" .
        self::comboOptions($data, $default) .
            '</select>' . "\n";
    }

    private static function comboOptions(array $data, $default): string
    {
        $res      = '';
        $option   = '<option value="%1$s"%3$s>%2$s</option>' . "\n";
        $optgroup = '<optgroup label="%1$s">' . "\n" . '%2$s' . "</optgroup>\n";

        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $res .= sprintf($optgroup, $k, self::comboOptions($v, $default));
            } elseif ($v instanceof formSelectOption) {
                $res .= $v->render($default);
            } else {
                $s = ((string) $v == (string) $default) ? ' selected="selected"' : '';
                $res .= sprintf($option, $v, $k, $s);
            }
        }

        return $res;
    }

    /**
     * Radio button
     *
     * Returns HTML code for a radio button.
     * $nid could be a string or an array of name and ID.
     * $checked could be a boolean or an associative array of any of optional parameters
     *
     * @param string|array   $nid         Element ID and name
     * @param mixed          $value       Element value
     * @param mixed          $checked     True if checked | associative array of optional parameters
     * @param string         $class       Element class name
     * @param string         $tabindex    Element tabindex
     * @param boolean        $disabled    True if disabled
     * @param string         $extra_html  Extra HTML attributes
     *
     * @return string
     *
     * @static
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
        self::getNameAndId($nid, $name, $id);
        if (func_num_args() > 2 && is_array($checked)) {
            // Cope with associative array of optional parameters
            $options = self::getDefaults(self::class, __FUNCTION__);
            $args    = array_merge($options, array_intersect_key($checked, $options));
            extract($args);
        }

        return '<input type="radio" name="' . $name . '" value="' . $value . '" ' .

            ($id ? 'id="' . $id . '" ' : '') .
            ($checked ? 'checked ' : '') .
            ($class ? 'class="' . $class . '" ' : '') .
            ($tabindex ? 'tabindex="' . strval((int) $tabindex) . '" ' : '') .
            ($disabled ? 'disabled ' : '') .
            $extra_html .

            '/>' . "\n";
    }

    /**
     * Checkbox
     *
     * Returns HTML code for a checkbox.
     * $nid could be a string or an array of name and ID.
     * $checked could be a boolean or an associative array of any of optional parameters
     *
     * @param string|array   $nid         Element ID and name
     * @param mixed          $value       Element value
     * @param mixed          $checked     True if checked | associative array of optional parameters
     * @param string         $class       Element class name
     * @param string         $tabindex    Element tabindex
     * @param boolean        $disabled    True if disabled
     * @param string         $extra_html  Extra HTML attributes
     *
     * @return string
     *
     * @static
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
        self::getNameAndId($nid, $name, $id);
        if (func_num_args() > 2 && is_array($checked)) {
            // Cope with associative array of optional parameters
            $options = self::getDefaults(self::class, __FUNCTION__);
            $args    = array_merge($options, array_intersect_key($checked, $options));
            extract($args);
        }

        return '<input type="checkbox" name="' . $name . '" value="' . $value . '" ' .

            ($id ? 'id="' . $id . '" ' : '') .
            ($checked ? 'checked ' : '') .
            ($class ? 'class="' . $class . '" ' : '') .
            ($tabindex ? 'tabindex="' . strval((int) $tabindex) . '" ' : '') .
            ($disabled ? 'disabled ' : '') .
            $extra_html .

            ' />' . "\n";
    }

    /**
     * Input field
     *
     * Returns HTML code for an input field.
     * $nid could be a string or an array of name and ID.
     * $default could be a string or an associative array of any of optional parameters
     *
     * @param string|array  $nid          Element ID and name
     * @param integer       $size         Element size
     * @param integer       $max          Element maxlength
     * @param mixed         $default      Element value | associative array of optional parameters
     * @param string        $class        Element class name
     * @param string        $tabindex     Element tabindex
     * @param boolean       $disabled     True if disabled
     * @param string        $extra_html   Extra HTML attributes
     * @param boolean       $required     Element is required
     * @param string        $type         Input type
     * @param string        $autocomplete Autocomplete attributes if relevant
     *
     * @return string
     *
     * @static
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
        self::getNameAndId($nid, $name, $id);
        if (func_num_args() > 3 && is_array($default)) {
            // Cope with associative array of optional parameters
            $options = self::getDefaults(self::class, __FUNCTION__);
            $args    = array_merge($options, array_intersect_key($default, $options));
            extract($args);
        }

        return '<input type="' . $type . '" name="' . $name . '" ' .

            ($id ? 'id="' . $id . '" ' : '') .
            ($size ? 'size="' . $size . '" ' : '') .
            ($max ? 'maxlength="' . $max . '" ' : '') .
            ($default || $default === '0' ? 'value="' . $default . '" ' : '') .
            ($class ? 'class="' . $class . '" ' : '') .
            ($tabindex ? 'tabindex="' . strval((int) $tabindex) . '" ' : '') .
            ($disabled ? 'disabled ' : '') .
            ($required ? 'required ' : '') .
            ($autocomplete ? 'autocomplete="' . $autocomplete . '" ' : '') .
            $extra_html .

            ' />' . "\n";
    }

    /**
     * Password field
     *
     * Returns HTML code for a password field.
     * $nid could be a string or an array of name and ID.
     * $default could be a string or an associative array of any of optional parameters
     *
     * @uses form::field
     *
     * @param string|array  $nid         Element ID and name
     * @param integer       $size        Element size
     * @param integer       $max         Element maxlength
     * @param mixed         $default     Element value | associative array of optional parameters
     * @param string        $class       Element class name
     * @param string        $tabindex    Element tabindex
     * @param boolean       $disabled    True if disabled
     * @param string        $extra_html  Extra HTML attributes
     * @param boolean       $required    Element is required
     * @param string        $autocomplete Autocomplete attributes if relevant (new-password/current-password)
     *
     * @return string
     *
     * @static
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
            $args    = array_merge($options, array_intersect_key($default, $options));
            extract($args);
        }

        return self::field(
            $nid,
            $size,
            $max,
            $default,
            $class,
            $tabindex,
            $disabled,
            $extra_html,
            $required,
            'password',
            $autocomplete
        );
    }

    /**
     * HTML5 Color field
     *
     * Returns HTML code for an input color field.
     * $nid could be a string or an array of name and ID.
     * $size could be a integer or an associative array of any of optional parameters
     *
     * @uses form::field
     *
     * @param string|array   $nid         Element ID and name
     * @param mixed          $size        Element size | associative array of optional parameters
     * @param integer        $max         Element maxlength
     * @param string         $default     Element value
     * @param string         $class       Element class name
     * @param string         $tabindex    Element tabindex
     * @param boolean        $disabled    True if disabled
     * @param string         $extra_html  Extra HTML attributes
     * @param boolean        $required    Element is required
     * @param string         $autocomplete Autocomplete attributes if relevant
     *
     * @return string
     *
     * @static
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
            $args    = array_merge($options, array_intersect_key($size, $options));
            extract($args);
        }

        return self::field(
            $nid,
            $size,
            $max,
            $default,
            $class,
            $tabindex,
            $disabled,
            $extra_html,
            $required,
            'color',
            $autocomplete
        );
    }

    /**
     * HTML5 Email field
     *
     * Returns HTML code for an input email field.
     * $nid could be a string or an array of name and ID.
     * $size could be a integer or an associative array of any of optional parameters
     *
     * @uses form::field
     *
     * @param string|array   $nid          Element ID and name
     * @param mixed          $size         Element size | associative array of optional parameters
     * @param integer        $max          Element maxlength
     * @param string         $default      Element value
     * @param string         $class        Element class name
     * @param string         $tabindex     Element tabindex
     * @param boolean        $disabled     True if disabled
     * @param string         $extra_html   Extra HTML attributes
     * @param boolean        $required     Element is required
     * @param string         $autocomplete Autocomplete attributes if relevant
     *
     * @return string
     *
     * @static
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
            $args    = array_merge($options, array_intersect_key($size, $options));
            extract($args);
        }

        return self::field(
            $nid,
            $size,
            $max,
            $default,
            $class,
            $tabindex,
            $disabled,
            $extra_html,
            $required,
            'email',
            $autocomplete
        );
    }

    /**
     * HTML5 URL field
     *
     * Returns HTML code for an input (absolute) URL field.
     * $nid could be a string or an array of name and ID.
     * $size could be a integer or an associative array of any of optional parameters
     *
     * @uses form::field
     *
     * @param string|array   $nid          Element ID and name
     * @param mixed          $size         Element size | associative array of optional parameters
     * @param integer        $max          Element maxlength
     * @param string         $default      Element value
     * @param string         $class        Element class name
     * @param string         $tabindex     Element tabindex
     * @param boolean        $disabled     True if disabled
     * @param string         $extra_html   Extra HTML attributes
     * @param boolean        $required     Element is required
     * @param string         $autocomplete Autocomplete attributes if relevant
     *
     * @return string
     *
     * @static
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
            $args    = array_merge($options, array_intersect_key($size, $options));
            extract($args);
        }

        return self::field(
            $nid,
            $size,
            $max,
            $default,
            $class,
            $tabindex,
            $disabled,
            $extra_html,
            $required,
            'url',
            $autocomplete
        );
    }

    /**
     * HTML5 Datetime (local) field
     *
     * Returns HTML code for an input datetime field.
     * $nid could be a string or an array of name and ID.
     * $size could be a integer or an associative array of any of optional parameters
     *
     * @uses form::field
     *
     * @param string|array   $nid          Element ID and name
     * @param mixed          $size         Element size | associative array of optional parameters
     * @param integer        $max          Element maxlength
     * @param string         $default      Element value (in YYYY-MM-DDThh:mm format)
     * @param string         $class        Element class name
     * @param string         $tabindex     Element tabindex
     * @param boolean        $disabled     True if disabled
     * @param string         $extra_html   Extra HTML attributes
     * @param boolean        $required     Element is required
     * @param string         $autocomplete Autocomplete attributes if relevant
     *
     * @return string
     *
     * @static
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
            $args    = array_merge($options, array_intersect_key($size, $options));
            extract($args);
        }
        // Cope with unimplemented input type for some browser (type="text" + pattern + placeholder)
        if (strpos(strtolower($extra_html), 'pattern=') === false) {
            $extra_html .= ' pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}"';
        }
        if (strpos(strtolower($extra_html), 'placeholder') === false) {
            $extra_html .= ' placeholder="1962-05-13T14:45"';
        }

        return self::field(
            $nid,
            $size,
            $max,
            $default,
            $class,
            $tabindex,
            $disabled,
            $extra_html,
            $required,
            'datetime-local',
            $autocomplete
        );
    }

    /**
     * HTML5 Date field
     *
     * Returns HTML code for an input date field.
     * $nid could be a string or an array of name and ID.
     * $size could be a integer or an associative array of any of optional parameters
     *
     * @uses form::field
     *
     * @param string|array   $nid          Element ID and name
     * @param mixed          $size         Element size | associative array of optional parameters
     * @param integer        $max          Element maxlength
     * @param string         $default      Element value (in YYYY-MM-DD format)
     * @param string         $class        Element class name
     * @param string         $tabindex     Element tabindex
     * @param boolean        $disabled     True if disabled
     * @param string         $extra_html   Extra HTML attributes
     * @param boolean        $required     Element is required
     * @param string         $autocomplete Autocomplete attributes if relevant
     *
     * @return string
     *
     * @static
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
            $args    = array_merge($options, array_intersect_key($size, $options));
            extract($args);
        }
        // Cope with unimplemented input type for some browser (type="text" + pattern + placeholder)
        if (strpos(strtolower($extra_html), 'pattern=') === false) {
            $extra_html .= ' pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}"';
        }
        if (strpos(strtolower($extra_html), 'placeholder') === false) {
            $extra_html .= ' placeholder="1962-05-13"';
        }

        return self::field(
            $nid,
            $size,
            $max,
            $default,
            $class,
            $tabindex,
            $disabled,
            $extra_html,
            $required,
            'date',
            $autocomplete
        );
    }

    /**
     * HTML5 Time (local) field
     *
     * Returns HTML code for an input time field.
     * $nid could be a string or an array of name and ID.
     * $size could be a integer or an associative array of any of optional parameters
     *
     * @uses form::field
     *
     * @param string|array   $nid          Element ID and name
     * @param mixed          $size         Element size | associative array of optional parameters
     * @param integer        $max          Element maxlength
     * @param string         $default      Element value (in hh:mm format)
     * @param string         $class        Element class name
     * @param string         $tabindex     Element tabindex
     * @param boolean        $disabled     True if disabled
     * @param string         $extra_html   Extra HTML attributes
     * @param boolean        $required     Element is required
     * @param string         $autocomplete Autocomplete attributes if relevant
     *
     * @return string
     *
     * @static
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
            $args    = array_merge($options, array_intersect_key($size, $options));
            extract($args);
        }
        // Cope with unimplemented input type for some browser (type="text" + pattern + placeholder)
        if (strpos(strtolower($extra_html), 'pattern=') === false) {
            $extra_html .= ' pattern="[0-9]{2}:[0-9]{2}"';
        }
        if (strpos(strtolower($extra_html), 'placeholder') === false) {
            $extra_html .= ' placeholder="14:45"';
        }

        return self::field(
            $nid,
            $size,
            $max,
            $default,
            $class,
            $tabindex,
            $disabled,
            $extra_html,
            $required,
            'time',
            $autocomplete
        );
    }

    /**
     * HTML5 file field
     *
     * Returns HTML code for an input file field.
     * $nid could be a string or an array of name and ID.
     * $default could be a integer or an associative array of any of optional parameters
     *
     * @param string|array   $nid         Element ID and name
     * @param mixed          $default     Element value | associative array of optional parameters
     * @param string         $class       Element class name
     * @param string         $tabindex    Element tabindex
     * @param boolean        $disabled    True if disabled
     * @param string         $extra_html  Extra HTML attributes
     * @param boolean        $required    Element is required
     *
     * @return string
     *
     * @static
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
        self::getNameAndId($nid, $name, $id);
        if (func_num_args() > 1 && is_array($default)) {
            // Cope with associative array of optional parameters
            $options = self::getDefaults(self::class, __FUNCTION__);
            $args    = array_merge($options, array_intersect_key($default, $options));
            extract($args);
        }

        return '<input type="file" ' . '" name="' . $name . '" ' .

            ($id ? 'id="' . $id . '" ' : '') .
            ($default || $default === '0' ? 'value="' . $default . '" ' : '') .
            ($class ? 'class="' . $class . '" ' : '') .
            ($tabindex ? 'tabindex="' . strval((int) $tabindex) . '" ' : '') .
            ($disabled ? 'disabled ' : '') .
            ($required ? 'required ' : '') .
            $extra_html .

            ' />' . "\n";
    }

    /**
     * HTML5 number input field
     *
     * Returns HTML code for an number input field.
     * $nid could be a string or an array of name and ID.
     * $min could be a string or an associative array of any of optional parameters
     *
     * @param string|array   $nid          Element ID and name
     * @param mixed          $min          Element min value (may be negative) | associative array of optional parameters
     * @param integer        $max          Element max value (may be negative)
     * @param string         $default      Element value
     * @param string         $class        Element class name
     * @param string         $tabindex     Element tabindex
     * @param boolean        $disabled     True if disabled
     * @param string         $extra_html   Extra HTML attributes
     * @param boolean        $required     Element is required
     * @param string         $autocomplete Autocomplete attributes if relevant
     *
     * @return string
     *
     * @static
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
        self::getNameAndId($nid, $name, $id);
        if (func_num_args() > 1 && is_array($min)) {
            // Cope with associative array of optional parameters
            $options = self::getDefaults(self::class, __FUNCTION__);
            $args    = array_merge($options, array_intersect_key($min, $options));
            extract($args);
        }

        return '<input type="number" name="' . $name . '" ' .

            ($id ? 'id="' . $id . '" ' : '') .
            ($min !== null ? 'min="' . $min . '" ' : '') .
            ($max !== null ? 'max="' . $max . '" ' : '') .
            ($default || $default === '0' ? 'value="' . $default . '" ' : '') .
            ($class ? 'class="' . $class . '" ' : '') .
            ($tabindex ? 'tabindex="' . strval((int) $tabindex) . '" ' : '') .
            ($disabled ? 'disabled ' : '') .
            ($required ? 'required ' : '') .
            ($autocomplete ? 'autocomplete="' . $autocomplete . '" ' : '') .
            $extra_html .

            ' />' . "\n";
    }

    /**
     * Textarea
     *
     * Returns HTML code for a textarea.
     * $nid could be a string or an array of name and ID.
     * $default could be a string or an associative array of any of optional parameters
     *
     * @param string|array  $nid          Element ID and name
     * @param integer       $cols         Number of columns
     * @param integer       $rows         Number of rows
     * @param mixed         $default      Element value | associative array of optional parameters
     * @param string        $class        Element class name
     * @param string        $tabindex     Element tabindex
     * @param boolean       $disabled     True if disabled
     * @param string        $extra_html   Extra HTML attributes
     * @param boolean       $required     Element is required
     * @param string        $autocomplete Autocomplete attributes if relevant
     *
     * @return string
     *
     * @static
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
        self::getNameAndId($nid, $name, $id);
        if (func_num_args() > 3 && is_array($default)) {
            // Cope with associative array of optional parameters
            $options = self::getDefaults(self::class, __FUNCTION__);
            $args    = array_merge($options, array_intersect_key($default, $options));
            extract($args);
        }

        return '<textarea cols="' . $cols . '" rows="' . $rows . '" name="' . $name . '" ' .

            ($id ? 'id="' . $id . '" ' : '') .
            ($tabindex != '' ? 'tabindex="' . strval((int) $tabindex) . '" ' : '') .
            ($class ? 'class="' . $class . '" ' : '') .
            ($disabled ? 'disabled ' : '') .
            ($required ? 'required ' : '') .
            ($autocomplete ? 'autocomplete="' . $autocomplete . '" ' : '') .
            $extra_html . '>' . $default . '</textarea>' . "\n";
    }

    /**
     * Hidden field
     *
     * Returns HTML code for an hidden field. $nid could be a string or an array of
     * name and ID.
     *
     * @param string|array  $nid    Element ID and name
     * @param mixed         $value  Element value
     *
     * @return string
     *
     * @static
     */
    public static function hidden($nid, $value): string
    {
        self::getNameAndId($nid, $name, $id);

        return '<input type="hidden" name="' . $name . '" value="' . $value . '" ' .

            ($id ? 'id="' . $id . '" ' : '') .

            ' />' . "\n";
    }
}

/**
 * @class formSelectOption
 * @brief HTML Forms creation helpers
 *
 * @package Clearbricks
 * @subpackage Common
 */
class formSelectOption
{
    public $name;       ///< string Option name
    public $value;      ///< mixed  Option value
    public $class_name; ///< string Element class name
    public $html;       ///< string Extra HTML attributes
    /**
     * sprintf template for option
     * @access private
     */
    private string $option = '<option value="%1$s"%3$s>%2$s</option>' . "\n";

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
        $this->name       = $name;
        $this->value      = $value;
        $this->class_name = $class_name;
        $this->html       = $html;
    }

    /**
     * Option renderer
     *
     * Returns option HTML code
     *
     * @param string  $default  Value of selected option
     * @return string
     */
    public function render(?string $default): string
    {
        $attr = $this->html ? ' ' . $this->html : '';
        $attr .= $this->class_name ? ' class="' . $this->class_name . '"' : '';

        if ($this->value == $default) {
            $attr .= ' selected';
        }

        return sprintf($this->option, $this->value, $this->name, $attr) . "\n";
    }
}
