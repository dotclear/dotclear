<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\OAuth2\Client;

/**
 * @brief   oAuth2 object descriptor helper.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
abstract class Descriptor
{
    /**
     * Default object configuration.
     *
     * @var     array<string, mixed>    CONFIGURATION
     */
    public const CONFIGURATION = [];

    /**
     * Required configuration keys.
     *
     * @var     string[]    REQUIREMENTS
     */
    public const REQUIREMENTS = [];

    /**
     * Required configuration keys.
     *
     * @var     string[]    MUSTFILLED
     */
    public const MUSTFILLED = [];

    /**
     * Object instance configuration.
     *
     * @var     array<string, mixed>    $properties
     */
    protected array $properties = [];

    /**
     * Create new object instance.
     *
     * @param   array<string, mixed>    $config     The object configuration
     */
    public function __construct(array $config = [])
    {
        $properties = static::CONFIGURATION;
        foreach ($properties as $key => $value) {
            if (isset($config[$key]) && gettype($config[$key]) === gettype($value)) {
                $properties[$key] = $config[$key];
            } elseif (in_array($key, static::REQUIREMENTS)) {
                throw new Exception\InvalidClient($key);
            }
        }

        $this->properties = $properties;
    }

    /**
     * Get object value.
     *
     * @return  mixed   The value
     */
    public function get(string $key): mixed
    {
        return $this->properties[$key] ?? null;
    }

    /**
     * Check if object is configured.
     *
     * @return  bool    True if object is configured
     */
    public function isConfigured(): bool
    {
        foreach (static::MUSTFILLED as $key) {
            if (empty($this->get($key))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get object properties.
     *
     * @return  array<string, mixed>   The object properties
     */
    public function getConfiguration(): array
    {
        return $this->properties;
    }
}
