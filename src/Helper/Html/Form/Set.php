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
 * @class Para
 * @brief Set (list of independant HTML elements) creation helpers
 *
 * Warning: there is no attributes (id, …)
 *
 * @method      $this items(array|Iterable $items)
 * @method      $this format(string $format)
 * @method      $this separator(string $separator)
 *
 * @property    array|Iterable $items
 * @property    string $format
 * @property    string $separator
 */
class Set extends Component
{
    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        parent::__construct(self::class);
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
        $buffer = '';
        $first  = true;
        $format ??= ($this->format ?? '%s');

        // Cope with items
        if (isset($this->items)) {
            foreach ($this->items as $item) {
                if (!$first && $this->separator) {
                    $buffer .= (string) $this->separator;
                }
                $buffer .= sprintf($format, $item->render());
                $first = false;
            }
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
        return '';
    }
}
