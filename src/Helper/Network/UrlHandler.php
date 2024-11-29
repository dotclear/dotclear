<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Network;

use Exception;

/**
 * @class UrlHandler
 */
class UrlHandler
{
    /**
     * Stack of URL types (name)
     *
     * @var array<string, array<string, mixed>>
     */
    protected $types = [];

    /**
     * Default handler, used if requested type handler not registered
     *
     * @var callable|null
     */
    protected $default_handler;

    /**
     * Stack of error handlers
     *
     * @var        array<string, callable>    Array of callable
     */
    protected $error_handlers = [];

    /**
     * Current handler
     *
     * @var        string
     */
    public $type = 'default';

    /**
     * Constructs a new instance.
     *
     * @param      string  $mode   The URL mode, should be 'path_info' or 'query_string'
     */
    public function __construct(
        public string $mode = 'path_info'
    ) {
    }

    /**
     * Register an URL handler
     *
     * @param      string       $type            The URI type
     * @param      string       $url             The base URI
     * @param      string       $representation  The URI representation (regex, string)
     * @param      callable     $handler         The handler
     */
    public function register(string $type, string $url, string $representation, $handler): void
    {
        $this->types[$type] = [
            'url'            => $url,
            'representation' => $representation,
            'handler'        => $handler,
        ];
    }

    /**
     * Register the default URL handler
     *
     * @param      callable  $handler  The handler
     */
    public function registerDefault($handler): void
    {
        $this->default_handler = $handler;
    }

    /**
     * Register an error handler (prepend at the begining of the error handler stack)
     *
     * @param      callable  $handler  The handler
     */
    public function registerError($handler): void
    {
        array_unshift($this->error_handlers, $handler);
    }

    /**
     * Unregister an URL handler
     *
     * @param      string  $type   The type
     */
    public function unregister(string $type): void
    {
        if (isset($this->types[$type])) {
            unset($this->types[$type]);
        }
    }

    /**
     * Gets the current type.
     *
     * @return     string  The type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Sets the current type.
     *
     * @param      string  $type   The type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Gets the current mode.
     *
     * @return     string  The mode.
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * Sets the current mode.
     *
     * @param      string  $mode   The mode
     */
    public function setMode(string $mode): void
    {
        $this->mode = $mode;
    }

    /**
     * Gets the registered URL handlers.
     *
     * @return     array<string, array<string, mixed>>  The types.
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * Gets the base URI of an URL handler.
     *
     * @param      string  $type   The type
     *
     * @return     string
     */
    public function getBase(string $type): string
    {
        if (isset($this->types[$type])) {
            return $this->types[$type]['url'];
        }

        return '';
    }

    /**
     * Gets the document using an URL handler.
     */
    public function getDocument(): void
    {
        $type = $args = '';

        if ($this->mode === 'path_info') {
            $part = substr($_SERVER['PATH_INFO'], 1);
        } else {
            $part = '';

            $query_string = $this->parseQueryString();

            # Recreates some _GET and _REQUEST pairs
            if (!empty($query_string)) {
                foreach ($_GET as $k => $v) {
                    if (isset($_REQUEST[$k])) {
                        unset($_REQUEST[$k]);
                    }
                }
                $_GET     = $query_string;
                $_REQUEST = array_merge($query_string, $_REQUEST);

                foreach ($query_string as $k => $v) {
                    if ($v === null) {
                        $part = $k;
                        unset($_GET[$k], $_REQUEST[$k]);
                    }

                    break;
                }
            }
        }

        $_SERVER['URL_REQUEST_PART'] = $part;

        $this->getArgs($part, $type, $args);

        if (!$type) {
            $this->type = 'default';
            $this->callDefaultHandler($args);
        } else {
            $this->type = $type;
            $this->callHandler($type, $args);
        }
    }

    /**
     * Gets the arguments from an URI
     *
     * @param      string  $part   The part
     * @param      mixed   $type   The type
     * @param      mixed   $args   The arguments
     */
    public function getArgs(string $part, &$type, &$args): void
    {
        if ($part == '') {
            $type = null;
            $args = null;

            return;
        }

        $this->sortTypes();

        foreach ($this->types as $k => $v) {
            $repr = $v['representation'];
            if ($repr == $part) {
                $type = $k;
                $args = null;

                return;
            } elseif (preg_match('#' . $repr . '#', (string) $part, $m)) {
                $type = $k;
                $args = $m[1] ?? null;

                return;
            }
        }

        // No type, pass args to default
        $args = $part;
    }

    /**
     * Call an URL handler callback
     *
     * @param      callable         $handler  The handler
     * @param      string           $args     The arguments
     * @param      string           $type     The URL handler type
     */
    protected function callHelper($handler, ?string $args = null, string $type = 'default'): void
    {
        if (!is_callable($handler)) {   // @phpstan-ignore-line
            throw new Exception('Unable to call function');
        }

        try {
            call_user_func($handler, $args);
        } catch (Exception $e) {
            foreach ($this->error_handlers as $err_handler) {
                if (call_user_func($err_handler, $args, $type, $e) === true) {
                    return;
                }
            }

            // propagate exception, as it has not been processed by handlers
            throw $e;
        }
    }

    /**
     * Call an registered URL handler callback
     *
     * @param      string     $type   The type
     * @param      string     $args   The arguments
     *
     * @throws     Exception
     */
    public function callHandler(string $type, ?string $args = null): void
    {
        if (!isset($this->types[$type])) {
            throw new Exception('Unknown URL type');
        }

        $this->callHelper($this->types[$type]['handler'], $args, $type);
    }

    /**
     * Call the default handler callback
     *
     * @param      string  $args   The arguments
     *
     * @throws     Exception
     */
    public function callDefaultHandler(?string $args = null): void
    {
        if (!isset($this->default_handler)) {
            throw new Exception('Undefined default URL handler');
        }

        $this->callHelper($this->default_handler, $args, 'default');
    }

    /**
     * Parse query string part of server URI
     *
     * @return     array<string, string|null>
     */
    protected function parseQueryString(): array
    {
        $res = [];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $parameters = explode('&', $_SERVER['QUERY_STRING']);
            foreach ($parameters as $parameter) {
                $elements = explode('=', $parameter, 2);

                // Decode the parameter's name
                $elements[0] = rawurldecode($elements[0]);
                if (!isset($elements[1])) {
                    // No parameter value
                    $res[$elements[0]] = null;
                } else {
                    // Decode parameter's value
                    $res[$elements[0]] = urldecode($elements[1]);
                }
            }
        }

        return $res;
    }

    /**
     * Sort registered URL on their representations descending order
     */
    protected function sortTypes(): void
    {
        $representations = [];
        foreach ($this->types as $k => $v) {
            $representations[$k] = $v['url'];
        }
        array_multisort($representations, SORT_DESC, $this->types);
    }
}
