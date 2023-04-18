<?php
/**
 * @class Request
 *
 * XLM-RPC helpers
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Helper\Network\XmlRpc;

class Request
{
    /**
     * Request method name
     *
     * @var string
     */
    public $method;

    /**
     * Request method arguments
     *
     * @var array
     */
    public $args;

    /**
     * Request XML string
     *
     * @var string
     */
    public $xml;

    /**
     * Constructor
     *
     * @param string        $method     Method name
     * @param array         $args       Method arguments
     */
    public function __construct(string $method, array $args)
    {
        $this->method = $method;
        $this->args   = $args;

        $this->xml = '<?xml version="1.0"?>' . "\n" .
        '<methodCall>' . "\n" .
        '  <methodName>' . $this->method . '</methodName>' . "\n" .
        '  <params>' . "\n";

        foreach ($this->args as $arg) {
            $this->xml .= '    <param><value>' . (new Value($arg))->getXml() . '</value></param>' . "\n";
        }

        $this->xml .= '  </params>' . "\n";
        $this->xml .= '</methodCall>';
    }

    /**
     * Request length
     *
     * Returns {@link $xml} content length.
     *
     * @return int
     */
    public function getLength(): int
    {
        return strlen($this->xml);
    }

    /**
     * Request XML
     *
     * Returns request XML version.
     *
     * @return string
     */
    public function getXml(): string
    {
        return $this->xml;
    }
}
