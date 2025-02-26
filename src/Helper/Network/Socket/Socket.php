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
 * @class Socket
 *
 * This class handles network socket through an iterator.
 */
class Socket
{
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
     * Stream timeout
     *
     * @var int|null
     */
    protected $_stream_timeout;

    /**
     * Verify peer
     *
     * @var bool|null
     */
    protected $_verify_peer;

    /**
     * Class constructor
     *
     * @param string      $_host       Server host
     * @param int         $port        Server port
     * @param int         $timeout     Connection timeout in seconds
     */
    public function __construct(
        protected string $_host,
        int $port,
        int $timeout = 10
    ) {
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
    public function host(?string $host = null): bool|string
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
     * Get / Set stream timeout
     *
     * If <var>$timeout</var> is set, set {@link $_stream_timeout} and returns true.
     * Otherwise, returns {@link $_stream_timeout} value.
     *
     * @param null|int    $timeout            Connection timeout
     *
     * @return null|int|true
     */
    public function streamTimeout(?int $timeout = null)
    {
        if ($timeout) {
            $this->_stream_timeout = abs($timeout);

            return true;
        }

        return $this->_stream_timeout;
    }

    /**
     * Get / Set peer verification
     *
     * @param null|bool    $verify            Verify peer flag
     */
    public function verifyPeer(?bool $verify = null): ?bool
    {
        if (!is_null($verify)) {
            $this->_verify_peer = $verify;

            return true;
        }

        return $this->_verify_peer;
    }

    /**
     * Sets blocking or non-blocking mode on the socket.
     */
    public function setBlocking(bool $block): bool
    {
        if (!$this->isOpen()) {
            return false;
        }

        return $this->_handle && stream_set_blocking($this->_handle, $block);
    }

    /**
     * Open connection.
     *
     * Opens socket connection and Returns an object of type Iterator
     * which can be iterate with a simple foreach loop.
     *
     * @return    Iterator|false
     */
    public function open(): false|Iterator
    {
        $errno = $errstr = null;

        if (!is_null($this->_verify_peer) && !$this->_verify_peer) {
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer'      => false,
                    'verify_peer_name' => false,
                ],
            ]);
            $url    = $this->_transport . $this->_host . ':' . $this->_port;
            $handle = @stream_socket_client($url, $errno, $errstr, (float) $this->_timeout, STREAM_CLIENT_CONNECT, $context);
        } else {
            $handle = @fsockopen($this->_transport . $this->_host, $this->_port, $errno, $errstr, (float) $this->_timeout);
        }
        if (!$handle) {
            throw new Exception('Socket error: ' . $errstr . ' (' . $errno . ')' . $this->_transport . $this->_host);
        }
        $this->_handle = $handle;

        if ($this->_stream_timeout !== null) {
            stream_set_timeout($handle, $this->_stream_timeout);
        }

        return $this->iterator();
    }

    /**
     * Closes socket connection
     */
    public function close(): void
    {
        if ($this->isOpen()) {
            if ($this->_handle) {
                fclose($this->_handle);
            }
            $this->_handle = null;
        }
    }

    /**
     * Send data
     *
     * Sends data to current socket and returns an object of type
     * {@link Iterator} which can be iterate with a simple foreach loop.
     *
     * <var>$data</var> can be a string or an array of lines.
     *
     * Example:
     *
     * ```php
     * $s = new Socket('www.google.com',80,2);
     * $s->open();
     * $data = [
     *     'GET / HTTP/1.0'
     * ];
     * foreach($s->write($data) as $v) {
     *     echo $v."\n";
     * }
     * $s->close();
     * ```
     *
     * @param   string|array<string>    $data        Data to send
     *
     * @return    Iterator|false
     */
    public function write($data): false|Iterator
    {
        if (!$this->isOpen()) {
            return false;
        }

        if (is_array($data)) {
            $data = implode("\r\n", $data) . "\r\n\r\n";
        }

        if ($this->_handle) {
            fwrite($this->_handle, $data);
        }

        return $this->iterator();
    }

    /**
     * Flushes socket write buffer.
     */
    public function flush(): ?bool
    {
        if (!$this->isOpen()) {
            return false;
        }

        if ($this->_handle) {
            fflush($this->_handle);
        }

        return null;
    }

    /**
     * Iterator
     *
     * @return    Iterator|false
     */
    protected function iterator(): false|Iterator
    {
        if (!$this->isOpen() || is_null($this->_handle)) {
            return false;
        }

        return new Iterator($this->_handle);
    }

    /**
     * Returns true if socket connection is open.
     */
    public function isOpen(): bool
    {
        return is_resource($this->_handle);
    }
}
