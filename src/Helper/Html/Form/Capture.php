<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Html\Form;

use Exception;

/**
 * @class Capture
 * @brief HTML Forms capture (capture echo) creation helpers
 */
class Capture extends Component
{
    private const DEFAULT_ELEMENT = '';

    /**
     * Capture buffer
     */
    private string $capture = '';

    /**
     * Constructs a new instance.
     *
     * @param      callable         $method     The method
     * @param      array<mixed>     $arguments  The arguments
     */
    public function __construct(callable $method, array $arguments = [])
    {
        parent::__construct(self::class, self::DEFAULT_ELEMENT);

        try {
            // Start to capture output
            ob_start();

            // Call given method with its arguments
            $method(...$arguments);

            // Get output generated above
            $this->capture = (string) ob_get_contents();

            // Stop capturing output
            ob_end_clean();
        } catch (Exception) {
            // Stop capturing output
            ob_end_clean();
        }
    }

    /**
     * Renders the HTML component.
     */
    public function render(): string
    {
        return $this->capture;
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
