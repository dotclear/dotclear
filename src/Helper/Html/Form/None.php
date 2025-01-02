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
 * @class None
 * @brief HTML Forms none (void content) creation helpers
 */
class None extends Component
{
    private const DEFAULT_ELEMENT = '';

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        parent::__construct(self::class, self::DEFAULT_ELEMENT);
    }

    /**
     * Renders the HTML component.
     */
    public function render(): string
    {
        return '';
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
