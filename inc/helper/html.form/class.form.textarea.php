<?php

declare(strict_types=1);

/**
 * @class formTextarea
 * @brief HTML Forms textarea creation helpers
 *
 * @package Clearbricks
 * @subpackage html.form
 *
 * @since 1.2 First time this was introduced.
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class formTextarea extends formComponent
{
    private const DEFAULT_ELEMENT = 'textarea';

    /**
     * Constructs a new instance.
     *
     * @param      mixed  $id     The identifier
     * @param      string $value  The value
     */
    public function __construct($id = null, ?string $value = null)
    {
        parent::__construct(__CLASS__, self::DEFAULT_ELEMENT);
        if ($id !== null) {
            $this->setIdentifier($id);
        }
        if ($value !== null) {
            $this->value = $value;
        }
    }

    /**
     * Renders the HTML component (including the associated label if any).
     *
     * @param      null|string  $extra  The extra
     *
     * @return     string
     */
    public function render(?string $extra = null): string
    {
        if (!$this->checkMandatoryAttributes()) {
            return '';
        }

        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . ($extra ?? '') . $this->renderCommonAttributes(false) .
            (isset($this->cols) ? ' cols="' . strval((int) $this->cols) . '"' : '') .
            (isset($this->rows) ? ' rows="' . strval((int) $this->rows) . '"' : '') .
            '>' .
            ($this->value               ?? '') .
            '</' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . '>' . "\n";

        if (isset($this->label) && isset($this->id)) {
            $this->label->for = $this->id;
            $buffer           = $this->label->render($buffer);
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
