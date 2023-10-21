<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Helper\Html\Form;

/**
 * @class Label
 * @brief HTML Forms label creation helpers
 *
 * @method      $this for(string $id)
 * @method      $this text(string $text)
 *
 * @property    string $for
 * @property    string $text
 */
class Label extends Component
{
    private const DEFAULT_ELEMENT = 'label';

    // Position of linked component and position of text/label

    /**
     * Put field inside label with label text before field (ex: Number: [ ])
     * Useful for input field, select, …
     *
     * @var        int
     */
    public const INSIDE_TEXT_BEFORE = 0;

    /**
     * Put field inside label with label text after field (ex: [] Active)
     * Useful for radio, checkbox, …
     *
     * @var        int
     */
    public const INSIDE_TEXT_AFTER = 1;

    /**
     * Put field after label (for=field_id will be set automatically)
     *
     * @var        int
     */
    public const OUTSIDE_LABEL_BEFORE = 2;

    /**
     * Put field before label (for=field_id will be set automatically)
     *
     * @var        int
     */
    public const OUTSIDE_LABEL_AFTER = 3;

    // Aliases

    public const INSIDE_LABEL_BEFORE = 0;
    public const INSIDE_LABEL_AFTER  = 1;
    public const OUTSIDE_TEXT_BEFORE = 2;
    public const OUTSIDE_TEXT_AFTER  = 3;

    /**
     * Position of linked component:
     *
     *   INSIDE_TEXT_BEFORE   = inside label, label text before component
     *   INSIDE_TEXT_AFTER    = inside label, label text after component
     *   OUTSIDE_LABEL_BEFORE = after label (for=field_id will be set automatically)
     *   OUTSIDE_LABEL_AFTER  = before label (for=field_id will be set automatically)
     *
     *   @var int
     */
    private int $_position = self::INSIDE_TEXT_BEFORE;

    /**
     * List of available positions
     *
     * @var array<int>
     */
    private array $_positions = [
        self::INSIDE_TEXT_BEFORE,
        self::INSIDE_TEXT_AFTER,
        self::OUTSIDE_LABEL_BEFORE,
        self::OUTSIDE_LABEL_AFTER,
    ];

    /**
     * Constructs a new instance.
     *
     * @param      string       $text      The text
     * @param      int          $position  The position
     * @param      null|string  $id        The identifier
     */
    public function __construct(string $text = '', int $position = self::INSIDE_TEXT_BEFORE, ?string $id = null)
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
     *
     * @return     string
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
        /* @phpstan-ignore-next-line */
        if ($this->_position !== self::INSIDE_TEXT_BEFORE && $this->_position !== self::INSIDE_TEXT_AFTER && isset($this->for)) {
            $start .= ' for="' . $this->for . '"';
        }
        $start .= $this->renderCommonAttributes();

        $end = ($this->getElement() ?? self::DEFAULT_ELEMENT);

        return sprintf($formats[$this->_position], $start, $this->text, $buffer ?: '', $end);
    }

    /**
     * Sets the position.
     *
     * @param      int   $position  The position
     *
     * @return  self
     */
    public function setPosition(int $position = self::INSIDE_TEXT_BEFORE)
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
