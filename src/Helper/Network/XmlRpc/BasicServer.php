<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Network\XmlRpc;

use Exception;

/**
 * @class BasicServer
 *
 * XLM-RPC Server
 *
 * This class library is fully based on Simon Willison's IXR library (http://scripts.incutio.com/xmlrpc/).
 *
 * This is the most basic XML-RPC server you can create. Built-in methods are:
 *
 * - system.getCapabilities
 * - system.listMethods
 * - system.multicall
 */
class BasicServer
{
    /**
     * Server methods
     *
     * @var array<string, mixed>
     */
    protected $callbacks = [];

    /**
     * Received data
     *
     * @var string
     */
    protected $data;

    /**
     * Server encoding
     *
     * @var string
     */
    protected $encoding;

    /**
     * Returned message
     *
     * @var Message
     */
    protected $message;

    /**
     * Server capabilities
     *
     * @var array<string, array<string, mixed>>
     */
    protected $capabilities;

    /**
     * Strict XML-RPC checks
     *
     * @var bool
     */
    public $strict_check = false;

    /**
     * Constructor
     *
     * @param mixed     $callbacks       Server callbacks
     * @param mixed     $data            Server data
     * @param string    $encoding        Server encoding
     */
    public function __construct($callbacks = false, $data = false, string $encoding = 'UTF-8')
    {
        $this->encoding = $encoding;
        $this->setCapabilities();
        if ($callbacks) {
            $this->callbacks = $callbacks;
        }
        $this->setCallbacks();
        $this->serve($data);
    }

    /**
     * Start XML-RPC Server
     *
     * This method starts the XML-RPC Server. It could take a data argument
     * which should be a valid XML-RPC raw stream. If data is not specified, it
     * take values from raw POST data.
     *
     * @param mixed    $data            XML-RPC raw stream
     */
    public function serve($data = false): void
    {
        $result = null;
        if (!$data) {
            try {
                # Check HTTP Method
                if ($_SERVER['REQUEST_METHOD'] != 'POST') {
                    throw new Exception('XML-RPC server accepts POST requests only.', 405);
                }

                # Check HTTP_HOST
                if (!isset($_SERVER['HTTP_HOST'])) {
                    throw new Exception('No Host Specified', 400);
                }

                $data = @file_get_contents('php://input');
                if (!$data) {
                    throw new Exception('No Message', 400);
                }

                if ($this->strict_check) {
                    # Check USER_AGENT
                    if (!isset($_SERVER['HTTP_USER_AGENT'])) {
                        throw new Exception('No User Agent Specified', 400);
                    }

                    # Check CONTENT_TYPE
                    if (!isset($_SERVER['CONTENT_TYPE']) || !str_starts_with((string) $_SERVER['CONTENT_TYPE'], 'text/xml')) {
                        throw new Exception('Invalid Content-Type', 400);
                    }

                    # Check CONTENT_LENGTH
                    if (!isset($_SERVER['CONTENT_LENGTH']) || $_SERVER['CONTENT_LENGTH'] != strlen($data)) {
                        throw new Exception('Invalid Content-Lenth', 400);
                    }
                }
            } catch (Exception $e) {
                if ($e->getCode() == 400) {
                    $this->head(400, 'Bad Request');
                } elseif ($e->getCode() == 405) {
                    $this->head(405, 'Method Not Allowed');
                    header('Allow: POST');
                }

                header('Content-Type: text/plain');
                echo $e->getMessage();
                exit;
            }
        }

        $this->message = new Message($data);

        try {
            $this->message->parse();

            if ($this->message->messageType != 'methodCall') {
                throw new XmlRpcException('Server error. Invalid xml-rpc. not conforming to spec. Request must be a methodCall', -32600);
            }

            $result = $this->call($this->message->methodName, $this->message->params);
        } catch (Exception $e) {
            $this->error($e);
        }

        # Encode the result
        $resultxml = (new Value($result))->getXml();

        # Create the XML
        $xml = "<methodResponse>\n" .
            "<params>\n" .
            "<param>\n" .
            "  <value>\n" .
            '   ' . $resultxml . "\n" .
            "  </value>\n" .
            "</param>\n" .
            "</params>\n" .
            '</methodResponse>';

        # Send it
        $this->output($xml);
    }

    /**
     * Send HTTP Headers
     *
     * This method sends a HTTP Header
     *
     * @param integer   $code           HTTP Status Code
     * @param string    $msg            Header message
     */
    protected function head(int $code, string $msg): void
    {
        $status_mode = preg_match('/cgi/', PHP_SAPI);

        if ($status_mode) {
            header('Status: ' . $code . ' ' . $msg);
        } else {
            header($msg, true, $code);
        }
    }

    /**
     * Method call
     *
     * This method calls the given XML-RPC method with arguments.
     *
     * @param string                        $methodname      Method name
     * @param array<int|string, mixed>      $args            Method arguments
     *
     * @return mixed
     */
    protected function call(string $methodname, array $args)
    {
        if (!$this->hasMethod($methodname)) {
            throw new XmlRpcException('server error. requested method "' . $methodname . '" does not exist.', -32601);
        }

        $method = $this->callbacks[$methodname];

        # Perform the callback and send the response
        if (!is_callable($method)) {
            throw new XmlRpcException('server error. internal requested function for "' . $methodname . '" does not exist.', -32601);
        }

        return call_user_func_array($method, $args);
    }

    /**
     * XML-RPC Error
     *
     * This method create an XML-RPC error message from a PHP Exception object.
     * You should avoid using this in your own method and throw exceptions
     * instead.
     *
     * @param Exception    $e            Exception object
     * @return never
     */
    protected function error(Exception $e)
    {
        $this->output(
            "<methodResponse>\n" .
            "  <fault>\n" .
            "    <value>\n" .
            "      <struct>\n" .
            "        <member>\n" .
            "          <name>faultCode</name>\n" .
            '          <value><int>' . $e->getCode() . "</int></value>\n" .
            "        </member>\n" .
            "        <member>\n" .
            "          <name>faultString</name>\n" .
            '          <value><string>' . $e->getMessage() . "</string></value>\n" .
            "        </member>\n" .
            "      </struct>\n" .
            "    </value>\n" .
            "  </fault>\n" .
            "</methodResponse>\n"
        );
    }

    /**
     * Output response
     *
     * This method sends the whole XML-RPC response through HTTP.
     *
     * @param string    $xml            XML Content
     * @return never
     */
    protected function output(string $xml): void
    {
        $xml = '<?xml version="1.0" encoding="' . $this->encoding . '"?>' . "\n" . $xml;

        header('Connection: close');
        header('Content-Length: ' . strlen($xml));
        header('Content-Type: text/xml');
        header('Date: ' . date('r'));

        echo $xml;

        exit;
    }

    /**
     * XML-RPC Server has method?
     *
     * Returns true if the server has the given method <var>$method</var>
     *
     * @param string    $method        Method name
     *
     * @return bool
     */
    protected function hasMethod(string $method): bool
    {
        return in_array($method, array_keys($this->callbacks));
    }

    /**
     * Server Capabilities
     *
     * This method initiates the server capabilities:
     * - xmlrpc
     * - faults_interop
     * - system.multicall
     */
    protected function setCapabilities(): void
    {
        # Initialises capabilities array
        $this->capabilities = [
            'xmlrpc' => [
                'specUrl'     => 'http://www.xmlrpc.com/spec',
                'specVersion' => 1,
            ],
            'faults_interop' => [
                'specUrl'     => 'http://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php',
                'specVersion' => 20_010_516,
            ],
            'system.multicall' => [
                'specUrl'     => 'http://www.xmlrpc.com/discuss/msgReader$1208',
                'specVersion' => 1,
            ],
        ];
    }

    /**
     * Server Methods
     *
     * This method creates the three main server's methods:
     * - system.getCapabilities
     * - system.listMethods
     * - system.multicall
     *
     * @see getCapabilities()
     * @see listMethods()
     * @see multiCall()
     */
    protected function setCallbacks(): void
    {
        $this->callbacks['system.getCapabilities'] = $this->getCapabilities(...);
        $this->callbacks['system.listMethods']     = $this->listMethods(...);
        $this->callbacks['system.multicall']       = $this->multiCall(...);
    }

    /**
     * Server Capabilities
     *
     * Returns server capabilities
     *
     * @return array<string, array<string, mixed>>
     */
    protected function getCapabilities(): array
    {
        return $this->capabilities;
    }

    /**
     * Server methods
     *
     * Returns all server methods
     *
     * @return array<string>
     */
    protected function listMethods(): array
    {
        # Returns a list of methods - uses array_reverse to ensure user defined
        # methods are listed before server defined methods
        return array_reverse(array_keys($this->callbacks));
    }

    /**
     * Multicall
     *
     * This method handles a multi-methods call
     *
     *  @see http://www.xmlrpc.com/discuss/msgReader$1208
     *
     * @param array<array<string, mixed>>        $methodcalls        Array of methods
     *
     * @return array<int|string, mixed>
     */
    protected function multiCall(array $methodcalls): array
    {
        $return = [];
        foreach ($methodcalls as $call) {
            $method = $call['methodName'];
            $params = $call['params'];

            try {
                if ($method == 'system.multicall') {
                    throw new XmlRpcException('Recursive calls to system.multicall are forbidden', -32600);
                }

                $result   = $this->call($method, $params);
                $return[] = [$result];
            } catch (Exception $e) {
                $return[] = [
                    'faultCode'   => $e->getCode(),
                    'faultString' => $e->getMessage(),
                ];
            }
        }

        return $return;
    }
}
