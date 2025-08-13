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
 * @brief   oAuth2 client provider scope class.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class Scope
{
    /**
     * The scope
     *
     * @var     string[]    $scope
     */
    public readonly array $scope;

    /**
     * Scope constructor.
     *
     * This is the scope required by the provider.
     *
     * @param   string|string[]     $scope          The scope
     * @param   string[]            $default_scope  The default scope
     * @param   string              $delimiter      The scope delimiter
     */
    public function __construct(string|array $scope = '', public readonly array $default_scope = [], public readonly string $delimiter = ',')
    {
        $this->scope = array_unique(array_merge($this->default_scope, is_array($scope) ? array_values($scope) : $this->toArray($scope)));
    }

    /**
     * Check if given scope are in provider required scope.
     *
     * @param   string|string[]     $scope  The scope
     *
     * @return  bool    True if all given scope are in required scope
     */
    public function inRequiredScope(string|array $scope): bool
    {
        if (!is_array($scope)) {
            $scope = $this->toArray($scope, $this->scope);
        }

        foreach ($scope as $check) {
            if (!in_array($check, $this->scope)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if provider required scope are in given scope.
     *
     * @param   string|string[]     $scope  The scope
     *
     * @return  bool    True if all required scope are in given scope
     */
    public function inScope(string|array $scope): bool
    {
        if (!is_array($scope)) {
            $scope = $this->toArray($scope, $this->scope);
        }

        foreach ($this->scope as $check) {
            if (!in_array($check, $scope)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Convert scope to string.
     *
     * Return current scope if no scope given.
     *
     * @param   null|string[]   $scope  The scope
     *
     * @return  string  The scope
     */
    public function toString(?array $scope = null): string
    {
        return implode($this->delimiter, $scope ?? $this->scope);
    }

    /**
     * Convert scope to array.
     *
     * Return current scope if no scope given.
     *
     * @param   null|string     $scope      The scope
     * @param   string[]        $default    The default scope
     *
     * @return  string[]    The scope
     */
    public function toArray(?string $scope = null, array $default = []): array
    {
        return is_null($scope) ? $default : explode($this->delimiter ?: ',', $scope);
    }
}
