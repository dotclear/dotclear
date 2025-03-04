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
 * @class Select
 * @brief HTML Forms select creation helpers
 *
 * @method      $this items(array<int|string, Component|string|array<int, Component|string>>|Iterable<int|string, Component|string|array<int, Component|string>> $items)
 *
 * @property    array<int|string, Component|string|array<int, Component|string>>|Iterable<int|string, Component|string|array<int, Component|string>> $items
 */
class Select extends Component
{
    private const DEFAULT_ELEMENT = 'select';

    /**
     * Constructs a new instance.
     *
     * @param      string|array{0: string, 1?: string}|null     $id             The identifier
     * @param      string                                       $element        The element
     * @param      bool                                         $renderLabel    Render label if present
     */
    public function __construct(
        $id = null,
        ?string $element = null,
        private bool $renderLabel = true
    ) {
        parent::__construct(self::class, $element ?? self::DEFAULT_ELEMENT);
        $this->renderLabel = $renderLabel;
        if ($id !== null) {
            $this->setIdentifier($id);
        }
    }

    /**
     * Renders the HTML component (including select options).
     *
     * @param      null|string  $default   The default value
     */
    public function render(?string $default = null): string
    {
        if (!$this->checkMandatoryAttributes()) {
            if (!App::config()->cliMode() && App::config()->devMode() && App::config()->debugMode()) {
                return '<!-- ' . static::class . ': ' . 'Select without id and name (provide at least one of them)' . ' -->' . "\n";
            }

            return '';
        }

        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . $this->renderCommonAttributes() . '>' . "\n";

        if ($this->items !== null) {
            foreach ($this->items as $item => $value) {
                if ($value instanceof None) {
                    continue;
                }

                $current = $this->default ?? $default ?? null;
                if ($current !== null) {
                    $current = (string) $current;
                }

                if ($value instanceof Option || $value instanceof Optgroup) {
                    /* @phpstan-ignore-next-line */
                    $buffer .= $value->render($current);
                } elseif (is_array($value)) {
                    /* @phpstan-ignore-next-line */
                    $buffer .= (new Optgroup((string) $item))->items($value)->render($current);
                } else {
                    /* @phpstan-ignore-next-line */
                    $buffer .= (new Option((string) $item, (string) $value))->render($current);
                }
            }
        }

        $buffer .= '</' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . '>' . "\n";

        if ($this->renderLabel && $this->label !== null) {
            $render = true;
            if ($this->id !== null) {
                $this->label->for = $this->id;
            } elseif ($this->label->getPosition() === Label::OL_FT || $this->label->getPosition() === Label::OL_TF) {
                // Do not render label if select is outside label and there is no id for select
                $render = false;
            }
            if ($render) {
                $buffer = $this->label->render($buffer);
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
        return self::DEFAULT_ELEMENT;
    }
}
