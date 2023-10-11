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
 * @class Form
 * @brief HTML Forms form creation helpers
 */
class Form extends Component
{
    private const DEFAULT_ELEMENT = 'form';

    /**
     * Constructs a new instance.
     *
     * @param      string|array{0: string, 1?: string}|null     $id       The identifier
     * @param      string                                       $element  The element
     */
    public function __construct($id = null, ?string $element = null)
    {
        parent::__construct(self::class, $element ?? self::DEFAULT_ELEMENT);
        if ($id !== null) {
            $this->setIdentifier($id);
        }
    }

    /**
     * Renders the HTML component.
     *
     * @param   string  $format     sprintf() format applied for each items/fields ('%s' by default)
     *
     * @return     string
     */
    public function render(?string $format = null): string
    {
        if (!$this->checkMandatoryAttributes()) {
            return '';
        }

        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) .
            (isset($this->action) ? ' action="' . $this->action . '"' : '') .
            (isset($this->method) ? ' method="' . $this->method . '"' : '') .
            $this->renderCommonAttributes() . '>' . "\n";

        $first = true;
        $format ??= ($this->format ?? '%s');

        // Cope with fields
        if (isset($this->fields) && is_array($this->fields)) {
            foreach ($this->fields as $field) {
                if (!$first && $this->separator) {  // @phpstan-ignore-line
                    $buffer .= (string) $this->separator;
                }
                $buffer .= sprintf($format, $field->render());
                $first = false;
            }
        }

        // Cope with items
        if (isset($this->items) && is_array($this->items)) {
            $first = true;
            foreach ($this->items as $item) {
                if (!$first && $this->separator) {  // @phpstan-ignore-line
                    $buffer .= (string) $this->separator;
                }
                $buffer .= sprintf($format, $item->render());
                $first = false;
            }
        }

        $buffer .= '</' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . '>' . "\n";

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
