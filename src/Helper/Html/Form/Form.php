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
 * @class Form
 * @brief HTML Forms form creation helpers
 *
 * @method      $this action(null|string $action)
 * @method      $this method(string $action)
 * @method      $this fields(array<int|string, Component>|Iterable<int|string, Component> $fields)
 * @method      $this items(array<int|string, Component>|Iterable<int|string, Component> $items)
 * @method      $this separator(string $separator)
 * @method      $this enctype(string $enctype)
 *
 * @property    null|string $action
 * @property    string $method
 * @property    array<int|string, Component>|Iterable<int|string, Component> $fields
 * @property    array<int|string, Component>|Iterable<int|string, Component> $items
 * @property    string $separator
 * @property    string $enctype
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
     */
    public function render(?string $format = null): string
    {
        if (!$this->checkMandatoryAttributes()) {
            if (!App::config()->cliMode() && App::config()->devMode() && App::config()->debugMode()) {
                return '<!-- ' . static::class . ': ' . 'Form without id and name (provide at least one of them)' . ' -->' . "\n";
            }

            return '';
        }

        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) .
            ($this->action !== null ? ' action="' . $this->action . '"' : '') .
            ($this->method !== null ? ' method="' . $this->method . '"' : '') .
            ($this->enctype !== null ? ' enctype="' . $this->enctype . '"' : '') .
            $this->renderCommonAttributes() . '>' . "\n";

        $first = true;
        $format ??= ($this->format ?? '%s');

        // Cope with fields
        if ($this->fields !== null) {
            foreach ($this->fields as $field) {
                if ($field instanceof None) {
                    continue;
                }
                if (!$first && $this->separator) {
                    $buffer .= (string) $this->separator;
                }
                $buffer .= sprintf($format, $field->render());
                $first = false;
            }
        }

        // Cope with items
        if ($this->items !== null) {
            $first = true;
            foreach ($this->items as $item) {
                if ($item instanceof None) {
                    continue;
                }
                if (!$first && $this->separator) {
                    $buffer .= (string) $this->separator;
                }
                $buffer .= sprintf($format, $item->render());
                $first = false;
            }
        }

        $buffer .= '</' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . '>' . "\n";

        if (($this->action === null || $this->method === null) && (!App::config()->cliMode() && App::config()->devMode() && App::config()->debugMode())) {
            $buffer .= '<!-- ' . static::class . ': ' . 'Form without action or method, is this deliberate?' . ' -->' . "\n";
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
