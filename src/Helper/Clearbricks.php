<?php
/**
 * @package Clearbricks
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 * @version 2.0
 */

namespace Dotclear\Helper;

use Exception;

class Clearbricks
{
    /**
     * Old way autoload classes stack
     *
     * @var        array
     */
    public $stack = [];

    /**
     * Instance singleton
     */
    private static ?self $instance = null;

    public function __construct()
    {
        // Singleton mode
        if (self::$instance) {
            throw new Exception('Library can not be loaded twice.', 500);
        }

        define('CLEARBRICKS_VERSION', '2.0');

        self::$instance = $this;

        spl_autoload_register([$this, 'loadClass']);

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
     * @return     self
     *
     * @deprecated Since 2.26
     */
    public static function lib(): self
    {
        if (!self::$instance) {
            // Init singleton
            new self();
        }

        return self::$instance;
    }

    public function loadClass(string $name)
    {
        if (isset($this->stack[$name]) && is_file($this->stack[$name])) {
            require_once $this->stack[$name];
        }
    }

    /**
     * Add class(es) to autoloader stack
     *
     * @param      array  $stack  Array of class => file (strings)
     *
     * @deprecated Since 2.26, use namespaces instead
     */
    public function add(array $stack)
    {
        if (is_array($stack)) {
            $this->stack = array_merge($this->stack, $stack);
        }
    }

    /**
     * Autoload: register class(es)
     * Exemaple: Clearbricks::lib()->autoload(['class' => 'classfullpath'])
     *
     * @param      array  $stack  Array of class => file (strings)
     *
     * @deprecated Since 2.26, use namespaces instead
     */
    public function autoload(array $stack)
    {
        $this->add($stack);
    }
}
