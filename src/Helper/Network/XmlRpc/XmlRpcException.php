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
 * @class XmlRpcException
 *
 * XLM-RPC helpers
 */
class XmlRpcException extends Exception
{
    /**
     * @param string    $message        Exception message
     * @param int       $code           Exception code
     */
    public function __construct(string $message, int  $code = 0)
    {
        parent::__construct($message, $code);
    }
}
