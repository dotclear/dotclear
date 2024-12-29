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
 * @class Request
 *
 * XLM-RPC helpers
 */
class Request
{
    /**
     * Request XML string
     *
     * @var string
     */
    public $xml;

    /**
     * Constructor
     *
     * @param string                                        $method     Method name
     * @param array<int|string, array<int|string, mixed>>   $args       Method arguments
     */
    public function __construct(
        public string $method,
        public array $args
    ) {
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
     */
    public function getLength(): int
    {
        return strlen($this->xml);
    }

    /**
     * Request XML
     *
     * Returns request XML version.
     */
    public function getXml(): string
    {
        return $this->xml;
    }
}
