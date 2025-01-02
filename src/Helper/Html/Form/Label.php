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
 * @class Label
 * @brief HTML Forms label creation helpers
 *
 * @method      $this for(string $id)
 * @method      $this text(string $text)
 * @method      $this prefix(string $prefix)
 * @method      $this suffix(string $suffix)
 *
 * @property    string $for
 * @property    string $text
 * @property    string $prefix
 * @property    string $suffix
 */
class Label extends Component
{
    private const DEFAULT_ELEMENT = 'label';

    // Position of linked component and position of text/label

    // OL_ = Outside label
    // IL_ = Inside label
    // T = Text from Label
    // F = field

    /**
     * Inside Label: Label < Text + Field >
     *
     * Put field inside label with label text before field (ex: Number: [ ])
     * Useful for input field, select, …
     *
     * @var        int
     */
    public const IL_TF = 0;

    /**
     * Inside Label: Label < Field + Text >
     *
     * Put field inside label with label text after field (ex: [] Active)
     * Useful for radio, checkbox, …
     *
     * @var        int
     */
    public const IL_FT = 1;

    /**
     * Outside Label: Label + Field
     *
     * Put field after label (for=field_id will be set automatically)
     *
     * @var        int
     */
    public const OL_TF = 2;

    /**
     * Outside Label: Field + Label
     *
     * Put field before label (for=field_id will be set automatically)
     *
     * @var        int
     */
    public const OL_FT = 3;

    /**
     * Position of linked component:
     *
     *   IL_TF = inside label, label text before component
     *   IL_FT = inside label, label text after component
     *   OL_TF = after label (for=field_id will be set automatically)
     *   OL_FT = before label (for=field_id will be set automatically)
     */
    private int $_position = self::IL_TF;

    /**
     * List of available positions
     *
     * @var array<int>
     */
    private array $_positions = [
        self::IL_TF,
        self::IL_FT,
        self::OL_TF,
        self::OL_FT,
    ];

    // Aliases (using TEXT)

    /**
     * Inside Label: Label < Text + Field >
     *
     * Put field inside label with label text before field (ex: Number: [ ])
     * Useful for input field, select, …
     *
     * @var        int
     */
    public const INSIDE_TEXT_BEFORE = self::IL_TF;

    /**
     * Inside Label: Label < Field + Text >
     *
     * Put field inside label with label text after field (ex: [] Active)
     * Useful for radio, checkbox, …
     *
     * @var        int
     */
    public const INSIDE_TEXT_AFTER = self::IL_FT;

    /**
     * Outside Label: Label + Field
     *
     * Put field after label (for=field_id will be set automatically)
     *
     * @var        int
     */
    public const OUTSIDE_TEXT_BEFORE = self::OL_TF;

    /**
     * Outside Label: Field + Label
     *
     * Put field before label (for=field_id will be set automatically)
     *
     * @var        int
     */
    public const OUTSIDE_TEXT_AFTER = self::OL_FT;

    // Aliases (using LABEL)

    /**
     * Inside Label: Label < Text + Field >
     *
     * Put field inside label with label text before field (ex: Number: [ ])
     * Useful for input field, select, …
     *
     * @var        int
     */
    public const INSIDE_LABEL_BEFORE = self::IL_TF;

    /**
     * Inside Label: Label < Field + Text >
     *
     * Put field inside label with label text after field (ex: [] Active)
     * Useful for radio, checkbox, …
     *
     * @var        int
     */
    public const INSIDE_LABEL_AFTER = self::IL_FT;

    /**
     * Outside Label: Label + Field
     *
     * Put field after label (for=field_id will be set automatically)
     *
     * @var        int
     */
    public const OUTSIDE_LABEL_BEFORE = self::OL_TF;

    /**
     * Outside Label: Field + Label
     *
     * Put field before label (for=field_id will be set automatically)
     *
     * @var        int
     */
    public const OUTSIDE_LABEL_AFTER = self::OL_FT;

    /**
     * Constructs a new instance.
     *
     * @param      string       $text      The text
     * @param      int          $position  The position
     * @param      null|string  $id        The identifier
     */
    public function __construct(string $text = '', int $position = self::IL_TF, ?string $id = null)
    {
        parent::__construct(self::class, self::DEFAULT_ELEMENT);

        if (in_array($position, $this->_positions)) {
            $this->_position = $position;
        }
        $this
            ->text($text);
        if ($id !== null) {
            $this->for($id);
        }
    }

    /**
     * Renders the HTML component.
     *
     * @param      null|string  $buffer  The buffer
     */
    public function render(?string $buffer = ''): string
    {
        /**
         * sprintf formats
         *
         * %1$s = label opening block
         * %2$s = text of label
         * %3$s = linked component
         * %4$s = label closing block
         *
         * @var        array<string>
         */
        $formats = [
            '<%1$s>%2$s %3$s</%4$s>', // Component inside label with label text before it
            '<%1$s>%3$s %2$s</%4$s>', // Component inside label with label text after it
            '<%1$s>%2$s</%4$s> %3$s', // Component after label (for attribute will be used)
            '%3$s <%1$s>%2$s</%4$s>', // Component before label (for attribute will be used)
        ];

        $start = ($this->getElement() ?? self::DEFAULT_ELEMENT);
        if ($this->_position !== self::IL_TF && $this->_position !== self::IL_FT && $this->for !== null) {
            $start .= ' for="' . $this->for . '"';
        }
        $start .= $this->renderCommonAttributes();

        $end = ($this->getElement() ?? self::DEFAULT_ELEMENT);

        // Cope with optional prefix/suffix
        $buffer = trim(implode(' ', [$this->prefix, $buffer ?: '', $this->suffix]));

        $format = $formats[$this->_position];
        // Cope with only label
        if ($buffer === '') {
            // Remove space separator before/after buffer
            $format = str_replace([' %3$s', '%3$s '], '%3$s', $format);
        }

        return sprintf($format, $start, $this->text, $buffer, $end);
    }

    /**
     * Sets the position.
     *
     * @param      int   $position  The position
     *
     * @return static    self instance, enabling to chain calls
     */
    public function setPosition(int $position = self::IL_TF): static
    {
        if (in_array($position, $this->_positions)) {
            $this->_position = $position;
        }

        return $this;
    }

    /**
     * Get the position.
     *
     * @return      int  The position
     */
    public function getPosition(): int
    {
        return $this->_position;
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
