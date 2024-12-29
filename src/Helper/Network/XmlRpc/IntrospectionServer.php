<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Network\XmlRpc;

/**
 * @class IntrospectionServer
 *
 * This class implements the most used type of XML-RPC Server.
 * It allows you to create classes inherited from this one and add methods
 * with {@link addCallback() addCallBack method}.
 *
 * This server class implements the following XML-RPC methods:
 * - system.methodSignature
 * - system.getCapabilities
 * - system.listMethods
 * - system.methodHelp
 * - system.multicall
 */
class IntrospectionServer extends BasicServer
{
    /**
     * Methods signature
     *
     * @var array<string, mixed>
     */
    protected $signatures;

    /**
     * Methods help
     *
     * @var array<string, string>
     */
    protected $help;

    /**
     * Constructor
     *
     * This method should be inherited to add new callbacks with
     * {@link addCallback()}.
     *
     * @param string    $encoding            Server encoding
     */
    public function __construct(string $encoding = 'UTF-8')
    {
        $this->encoding = $encoding;
        $this->setCallbacks();
        $this->setCapabilities();

        $this->capabilities['introspection'] = [
            'specUrl'     => 'http://xmlrpc.usefulinc.com/doc/reserved.html',
            'specVersion' => 1,
        ];

        $this->addCallback(
            'system.methodSignature',
            $this->methodSignature(...),
            ['array', 'string'],
            'Returns an array describing the return type and required parameters of a method'
        );

        $this->addCallback(
            'system.getCapabilities',
            $this->getCapabilities(...),
            ['struct'],
            'Returns a struct describing the XML-RPC specifications supported by this server'
        );

        $this->addCallback(
            'system.listMethods',
            $this->listMethods(...),
            ['array'],
            'Returns an array of available methods on this server'
        );

        $this->addCallback(
            'system.methodHelp',
            $this->methodHelp(...),
            ['string', 'string'],
            'Returns a documentation string for the specified method'
        );

        $this->addCallback(
            'system.multicall',
            $this->multiCall(...),
            ['struct', 'array'],
            'Returns result of multiple methods calls'
        );
    }

    /**
     * Add Server Callback
     *
     * This method creates a new XML-RPC method which references a class
     * callback. <var>$callback</var> should be a valid PHP callback.
     *
     * @param string                $method         Method name
     * @param callable              $callback       Method callback
     * @param array<int, mixed>     $args           Array of arguments type. The first is the returned one.
     * @param string                $help           Method help string
     */
    protected function addCallback(string $method, $callback, array $args, string $help = ''): void
    {
        $this->callbacks[$method]  = $callback;
        $this->signatures[$method] = $args;
        $this->help[$method]       = $help;
    }

    /**
     * Method call
     *
     * This method calls the callbacks function or method for the given XML-RPC
     * method <var>$methodname</var> with arguments in <var>$args</var> array.
     *
     * @param string        $methodname      Method name
     * @param mixed         $args            Arguments
     */
    protected function call(string $methodname, $args): mixed
    {
        // Make sure it's in an array
        $params = [];
        if ($args && !is_array($args)) {
            $params = [$args];
        } elseif (is_array($args)) {
            $params = $args;
        }

        // Over-rides default call method, adds signature check
        if (!$this->hasMethod($methodname)) {
            throw new XmlRpcException('Server error. Requested method "' . $methodname . '" not specified.', -32601);
        }

        $signature = $this->signatures[$methodname];

        if (!is_array($signature)) {
            throw new XmlRpcException('Server error. Wrong method signature', -36600);
        }

        array_shift($signature);

        // Check the number of arguments
        if (count($params) > count($signature)) {
            throw new XmlRpcException('Server error. Wrong number of method parameters', -32602);
        }

        // Check the argument types
        if (!$this->checkArgs($params, $signature)) {
            throw new XmlRpcException('Server error. Invalid method parameters', -32602);
        }

        // It passed the test - run the "real" method call
        return parent::call($methodname, $params);
    }

    /**
     * Method Arguments Check
     *
     * This method checks the validity of method arguments.
     *
     * @param array<int, mixed>     $args             Method given arguments
     * @param array<string, mixed>  $signature        Method defined arguments
     */
    protected function checkArgs(array $args, array $signature): bool
    {
        for ($i = 0, $j = count($args); $i < $j; $i++) {
            $arg  = array_shift($args);
            $type = array_shift($signature);

            switch ($type) {
                case 'int':
                case 'i4':
                    if (is_array($arg) || !is_int($arg)) {
                        return false;
                    }

                    break;
                case 'base64':
                case 'string':
                    if (!is_string($arg)) {
                        return false;
                    }

                    break;
                case 'boolean':
                    if ($arg !== false && $arg !== true) {
                        return false;
                    }

                    break;
                case 'float':
                case 'double':
                    if (!is_float($arg)) {
                        return false;
                    }

                    break;
                case 'date':
                case 'dateTime.iso8601':
                    if (!($arg instanceof Date)) {
                        return false;
                    }

                    break;
            }
        }

        return true;
    }

    /**
     * Method Signature
     *
     * This method return given XML-RPC method signature.
     *
     * @param string    $method        Method name
     *
     * @return array<mixed>
     */
    protected function methodSignature(string $method): array
    {
        if (!$this->hasMethod($method)) {
            throw new XmlRpcException('Server error. Requested method "' . $method . '" not specified.', -32601);
        }

        # We should be returning an array of types
        $types  = $this->signatures[$method];
        $return = [];

        foreach ($types as $type) {
            switch ($type) {
                case 'string':
                    $return[] = 'string';

                    break;
                case 'int':
                case 'i4':
                    $return[] = 42;

                    break;
                case 'double':
                    $return[] = 3.1415;

                    break;
                case 'dateTime.iso8601':
                    $return[] = new Date(time());

                    break;
                case 'boolean':
                    $return[] = true;

                    break;
                case 'base64':
                    $return[] = new Base64('base64');

                    break;
                case 'array':
                    $return[] = ['array'];

                    break;
                case 'struct':
                    $return[] = ['struct' => 'struct'];

                    break;
            }
        }

        return $return;
    }

    /**
     * Method Help
     *
     * This method return given XML-RPC method help string.
     *
     * @param string    $method        Method name
     */
    protected function methodHelp(string $method): string
    {
        return $this->help[$method];
    }
}
