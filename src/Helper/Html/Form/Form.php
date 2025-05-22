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
 * @method      $this fields(Iterable<int|string, Component> $fields)
 * @method      $this items(Iterable<int|string, Component> $items)
 * @method      $this separator(string $separator)
 * @method      $this enctype(string $enctype)
 *
 * @property    null|string $action
 * @property    string $method
 * @property    Iterable<int|string, Component> $fields
 * @property    Iterable<int|string, Component> $items
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
    public function __construct(string|array|null $id = null, ?string $element = null)
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

        // Cope with fields and items
        $buffer .= implode((string) $this->separator, array_filter([
            $this->renderFields($format),
            $this->renderItems($format),
        ]));

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
