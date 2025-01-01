<?php

/**
 * @package Clearbricks
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 * @version 2.0
 */

namespace Dotclear\Helper;

use Exception;

class Clearbricks
{
    /**
     * Old way autoload classes stack
     *
     * @var        array<string, string>
     */
    public $stack = [];

    /**
     * Instance singleton
     */
    private static self $instance;

    public function __construct()
    {
        // Singleton mode
        if (isset(self::$instance)) {
            throw new Exception('Library can not be loaded twice.', 500);
        }

        define('CLEARBRICKS_VERSION', '2.0');

        self::$instance = $this;

        spl_autoload_register($this->loadClass(...));

        // Load old CB classes
        $legacy_form_root = implode(DIRECTORY_SEPARATOR, [__DIR__, 'Html', 'Form']);

        $this->add([
            // Common helpers legacy classes
            'form'             => $legacy_form_root . DIRECTORY_SEPARATOR . 'Legacy.php',
            'formSelectOption' => $legacy_form_root . DIRECTORY_SEPARATOR . 'Legacy.php',
        ]);
    }

    /**
     * Get Clearbricks singleton instance
     *
     * @deprecated Since 2.26
     */
    public static function lib(): self
    {
        if (!isset(self::$instance)) {
            // Init singleton
            new self();
        }

        return self::$instance;
    }

    /**
     * Loads a class.
     *
     * @param      string  $name   The name
     */
    public function loadClass(string $name): void
    {
        if (isset($this->stack[$name]) && is_file($this->stack[$name])) {
            require_once $this->stack[$name];
        }
    }

    /**
     * Add class(es) to autoloader stack
     *
     * @param      array<string, string>  $stack  Array of class => file (strings)
     *
     * @deprecated Since 2.26, use namespaces instead
     */
    public function add(array $stack): void
    {
        $this->stack = [...$this->stack, ...$stack];
    }

    /**
     * Autoload: register class(es)
     * Exemaple: Clearbricks::lib()->autoload(['class' => 'classfullpath'])
     *
     * @param      array<string, string>  $stack  Array of class => file (strings)
     *
     * @deprecated Since 2.26, use namespaces instead
     */
    public function autoload(array $stack): void
    {
        $this->add($stack);
    }
}
