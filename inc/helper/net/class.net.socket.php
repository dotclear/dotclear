<?php
/**
 * @class netSocket
 * @brief Network base
 *
 * This class handles network socket through an iterator.
 *
 * @package Clearbricks
 * @subpackage Network
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class netSocket
{
    /**
     * Server host
     *
     * @var string
     */
    protected $_host;

    /**
     * Server port
     *
     * @var int
     */
    protected $_port;

    /**
     * Server transport
     *
     * @var string
     */
    protected $_transport = '';

    /**
     * Connection timeout
     *
     * @var int
     */
    protected $_timeout;

    /**
     * Resource handler
     *
     * @var resource|null
     */
    protected $_handle;

    /**
     * Class constructor
     *
     * @param string      $host        Server host
     * @param int         $port        Server port
     * @param int         $timeout     Connection timeout
     */
    public function __construct(string $host, int $port, int $timeout = 10)
    {
        $this->_host    = $host;
        $this->_port    = abs($port);
        $this->_timeout = abs($timeout);
    }

    /**
     * Object destructor
     *
     * Calls {@link close()} method
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Get / Set host
     *
     * If <var>$host</var> is set, set {@link $_host} and returns true.
     * Otherwise, returns {@link $_host} value.
     *
     * @param string    $host            Server host
     *
     * @return string|true
     */
    public function host(?string $host = null)
    {
        if ($host) {
            $this->_host = $host;

            return true;
        }

        return $this->_host;
    }

    /**
     * Get / Set port
     *
     * If <var>$port</var> is set, set {@link $_port} and returns true.
     * Otherwise, returns {@link $_port} value.
     *
     * @param int    $port            Server port
     *
     * @return int|true
     */
    public function port(?int $port = null)
    {
        if ($port) {
            $this->_port = abs($port);

            return true;
        }

        return $this->_port;
    }

    /**
     * Get / Set timeout
     *
     * If <var>$timeout</var> is set, set {@link $_timeout} and returns true.
     * Otherwise, returns {@link $_timeout} value.
     *
     * @param int    $timeout            Connection timeout
     *
     * @return int|true
     */
    public function timeout(?int $timeout = null)
    {
        if ($timeout) {
            $this->_timeout = abs($timeout);

            return true;
        }

        return $this->_timeout;
    }

    /**
     * Set blocking
     *
     * Sets blocking or non-blocking mode on the socket.
     *
     * @param   bool    $block
     *
     * @return    boolean
     */
    public function setBlocking(bool $block): bool
    {
        if (!$this->isOpen()) {
            return false;
        }

        return stream_set_blocking($this->_handle, $block);
    }

    /**
     * Open connection.
     *
     * Opens socket connection and Returns an object of type {@link netSocketIterator}
     * which can be iterate with a simple foreach loop.
     *
     * @return    netSocketIterator|bool
     */
    public function open()
    {
        $handle = @fsockopen($this->_transport . $this->_host, $this->_port, $errno, $errstr, $this->_timeout);
        if (!$handle) {
            throw new Exception('Socket error: ' . $errstr . ' (' . $errno . ')');
        }
        $this->_handle = $handle;

        return $this->iterator();
    }

    /**
     * Closes socket connection
     */
    public function close(): void
    {
        if ($this->isOpen()) {
            fclose($this->_handle);
            $this->_handle = null;
        }
    }

    /**
     * Send data
     *
     * Sends data to current socket and returns an object of type
     * {@link netSocketIterator} which can be iterate with a simple foreach loop.
     *
     * <var>$data</var> can be a string or an array of lines.
     *
     * Example:
     *
     * <code>
     * $s = new netSocket('www.google.com',80,2);
     * $s->open();
     * $data = [
     *     'GET / HTTP/1.0'
     * ];
     * foreach($s->write($data) as $v) {
     *     echo $v."\n";
     * }
     * $s->close();
     * </code>
     *
     * @param   string|array    $data        Data to send
     *
     * @return    netSocketIterator|false
     */
    public function write($data)
    {
        if (!$this->isOpen()) {
            return false;
        }

        if (is_array($data)) {
            $data = implode("\r\n", $data) . "\r\n\r\n";
        }

        fwrite($this->_handle, $data);

        return $this->iterator();
    }

    /**
     * Flush buffer
     *
     * Flushes socket write buffer.
     */
    public function flush()
    {
        if (!$this->isOpen()) {
            return false;
        }

        fflush($this->_handle);
    }

    /**
     * Iterator
     *
     * @return    netSocketIterator|false
     */
    protected function iterator()
    {
        if (!$this->isOpen()) {
            return false;
        }

        return new netSocketIterator($this->_handle);
    }

    /**
     * Is open
     *
     * Returns true if socket connection is open.
     *
     * @return    bool
     */
    public function isOpen()
    {
        return is_resource($this->_handle);
    }
}

/**
 * @class netSocketIterator
 * @brief Network socket iterator
 *
 * This class offers an iterator for network operations made with
 * {@link netSocket}.
 *
 * @see netSocket::write()
 */
class netSocketIterator implements Iterator
{
    /**
     * Socket resource handler
     *
     * @var resource
     */
    protected $_handle;

    /**
     * Current index position
     *
     * @var int
     */
    protected $_index;

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
    #[\ReturnTypeWillChange]
    public function rewind()
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
    #[\ReturnTypeWillChange]
    public function next()
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
     * @return string    Current socket response line
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return fgets($this->_handle, 4096);
    }
}
