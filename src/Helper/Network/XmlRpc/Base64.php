<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Helper\Network\XmlRpc;

/**
 * @class Base64
 *
 * XLM-RPC helpers
 */
class Base64
{
    /**
     * Base 64 decoded data
     *
     * @var string
     */
    protected $data;

    /**
     * Constructor
     *
     * Create a new instance of Base64.
     *
     * @param string        $data        Data
     */
    public function __construct(string $data)
    {
        $this->data = $data;
    }

    /**
     * XML Data
     *
     * Returns the XML fragment for XML-RPC message inclusion.
     *
     * @return string
     */
    public function getXml(): string
    {
        return '<base64>' . base64_encode($this->data) . '</base64>';
    }
}
