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
 * @class Base64
 *
 * XLM-RPC helpers
 */
class Base64
{
    /**
     * Constructor
     *
     * Create a new instance of Base64.
     *
     * @param string        $data        Base 64 decoded data
     */
    public function __construct(
        protected string $data
    ) {
    }

    /**
     * XML Data
     *
     * Returns the XML fragment for XML-RPC message inclusion.
     */
    public function getXml(): string
    {
        return '<base64>' . base64_encode($this->data) . '</base64>';
    }
}
