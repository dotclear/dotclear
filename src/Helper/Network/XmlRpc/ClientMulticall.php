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
 * @class ClientMulticall
 *
 * Multicall XML-RPC Client
 *
 * This class library is fully based on Simon Willison's IXR library (http://scripts.incutio.com/xmlrpc/).
 *
 * Multicall client using system.multicall method of server.
 */
class ClientMulticall extends Client
{
    /**
     * Stack of methods to be called
     *
     * @var        array<int, array<string, mixed>>
     */
    protected $calls = [];

    /**
     * Add call to stack
     *
     * This method adds a method call for the given query (first argument) to
     * calls stack.
     * All other arguments of this method are XML-RPC method arguments.
     *
     * Example:
     * ```php
     * use Dotclear\Helper\Network\XmlRpc\ClientMulticall;
     * $o = new Client('http://example.com/xmlrpc');
     * $o->addCall('method1','hello','world');
     * $o->addCall('method2','foo','bar');
     * $r = $o->query();
     * ```
     *
     * @param mixed     $args
     */
    public function addCall(string $method, ...$args): void
    {
        $this->calls[] = [
            'methodName' => $method,
            'params'     => $args,
        ];
    }

    /**
     * XML-RPC Query
     *
     * This method sends calls stack to XML-RPC system.multicall method.
     * See {@link BasicServer::multiCall()} for details and links about it.
     *
     * @param string    $method (not used, use ::addCall() before invoking ::query())
     * @param mixed     $args
     *
     * @return mixed
     */
    public function query(string $method = '', ...$args)
    {
        # Prepare multicall, then call the parent::query() method
        return parent::query('system.multicall', $this->calls);
    }
}
