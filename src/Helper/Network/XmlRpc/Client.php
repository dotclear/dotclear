<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Network\XmlRpc;

use Dotclear\Helper\Network\HttpClient;
use Exception;

/**
 * @class Client
 *
 * XML-RPC Client
 *
 * This class library is fully based on Simon Willison's IXR library (http://scripts.incutio.com/xmlrpc/).
 *
 * Basic XML-RPC Client.
 */
class Client extends HttpClient
{
    /**
     * XML-RPC Request object
     *
     * @var Request
     */
    protected $request;

    /**
     * XML-RPC Message object
     *
     * @var Message
     */
    protected $message;

    /**
     * Constructor
     *
     * Creates a new instance. <var>$url</var> is the XML-RPC Server end point.
     *
     * @param string        $url            Service URL
     */
    public function __construct(string $url)
    {
        $ssl  = false;
        $host = '';
        $port = 0;
        $path = '';
        $user = '';
        $pass = '';

        if (!static::readUrl($url, $ssl, $host, $port, $path, $user, $pass)) {
            return;
        }

        parent::__construct($host, $port);
        $this->useSSL($ssl);
        $this->setAuthorization($user, $pass);

        $this->path       = $path;
        $this->user_agent = 'Dotclear XML/RPC Client';
    }

    /**
     * XML-RPC Query
     *
     * This method calls the given query (first argument) on XML-RPC Server.
     * All other arguments of this method are XML-RPC method arguments.
     * This method throws an exception if XML-RPC method returns an error or
     * returns the server's response.
     *
     * Example:
     * ```php
     * use Dotclear\Helper\Network\XmlRpc\Client;
     * $o = new Client('http://example.com/xmlrpc');
     * $r = $o->query('method1','hello','world');
     * ```
     *
     * @param array<string, mixed>      $args
     *
     * @return mixed
     */
    public function query(string $method, ...$args)
    {
        $this->request = new Request($method, $args);

        $this->doRequest();

        if ($this->status !== 200) {
            throw new Exception('HTTP Error. ' . $this->status . ' ' . $this->status_string);
        }

        # Now parse what we've got back
        $this->message = new Message($this->content);
        $this->message->parse();

        # Is the message a fault?
        if ($this->message->messageType === 'fault') {
            throw new XmlRpcException($this->message->faultString, $this->message->faultCode);
        }

        return $this->message->params[0];
    }

    /**
     * Builds an request.
     *
     * Overloading HttpClient::buildRequest method, we don't need all the stuff of HTTP client.
     *
     * @return     array<string>  The request.
     */
    protected function buildRequest(): array
    {
        $path = $this->proxy_host ? $this->getRequestURL() : $this->path;

        return [
            'POST ' . $path . ' HTTP/1.0',
            'Host: ' . $this->host,
            'Content-Type: text/xml',
            'User-Agent: ' . $this->user_agent,
            'Content-Length: ' . $this->request->getLength(),
            '',
            $this->request->getXML(),
        ];
    }
}
