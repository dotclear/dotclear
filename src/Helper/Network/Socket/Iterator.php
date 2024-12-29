<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Network\Socket;

use Exception;

/**
 * @class Iterator
 *
 * This class offers an iterator for network operations made with Socket
 *
 * @implements \Iterator<string|false>
 */
class Iterator implements \Iterator
{
    /**
     * Socket resource handler
     *
     * @var resource
     */
    protected $_handle;

    /**
     * Current index position
     */
    protected int $_index;

    /**
     * Constructor
     *
     * @param resource    $handle        Socket resource handler
     */
    public function __construct(&$handle)
    {
        if (!is_resource($handle)) {
            throw new Exception('Handle is not a resource');
        }
        $this->_handle = &$handle;
        $this->_index  = 0;
    }

    /* Iterator methods
    --------------------------------------------------- */
    /**
     * Rewind
     */
    public function rewind(): void
    {
        # Nothing
    }

    /**
     * Valid
     *
     * @return bool    True if EOF of handler
     */
    public function valid(): bool
    {
        return !feof($this->_handle);
    }

    /**
     * Move index forward
     */
    public function next(): void
    {
        $this->_index++;
    }

    /**
     * Current index
     *
     * @return int    Current index
     */
    public function key(): int
    {
        return $this->_index;
    }

    /**
     * Current value
     *
     * @return string|false    Current socket response line
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return fgets($this->_handle, 4096);
    }
}
