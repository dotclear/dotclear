<?php

/**
 * @package     Dotclear
 *    
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Util;

use Dotclear\Helper\WebAuthn\Exception\ByteBufferException;
use Dotclear\Interface\Helper\WebAuthn\Util\ByteBufferInterface;
use JsonSerializable;
use Serializable;

/**
 * @brief   WebAuthn byte buffer handler.
 *
 * Modified version from Thomas Bleeker under MIT license :
 * https://github.com/madwizard-thomas/webauthn-server/blob/master/src/Format/ByteBuffer.php
 * Modified version from Lukas Buchs under MIT license :
 * https://github.com/lbuchs/WebAuthn/blob/master/src/Binary/ByteBuffer.php
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class ByteBuffer implements JsonSerializable, Serializable, ByteBufferInterface
{
    /**
     * Use base 64 URL encoding.
     *
     * @var     bool    $useBase64UrlEncoding
     */
    public static bool $useBase64UrlEncoding = false;

    /**
     * The binary string length.
     *
     * @var     int     $length
     */
    private $length;

    /**
     * Create a new byte buffer instance.
     *
     * @param   string  $data   The binary string
     */
    public function __construct(
        private string $data = ''
    ) {
        $this->length = strlen($this->data);
    }

    public static function useBase64UrlEncoding(bool $use): void
    {
        static::$useBase64UrlEncoding = $use;
    }

    /**
     * Create Bytbuffer from binary string.
     *
     * @param   string  $binary     The binary string
     *
     * @return  ByteBuffer
     */
    public static function fromBinary(string $binary): ByteBufferInterface
    {
        return new ByteBuffer($binary);
    }

    /**
     * Create a ByteBuffer from a base64 url encoded string.
     *
     * @param   string  $base64url
     *
     * @return  ByteBuffer
     */
    public static function fromBase64Url(string $base64url): ByteBufferInterface
    {
        $bin = (string) base64_decode(strtr($base64url, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($base64url)) % 4));
        if ($bin === '') {
            throw new ByteBufferException('Invalid base64 url string');
        }

        return new ByteBuffer($bin);
    }

    /**
     * Create a ByteBuffer from a base64 url encoded string.
     *
     * @param   string  $hex
     *
     * @return  ByteBuffer
     */
    public static function fromHex(string $hex): ByteBufferInterface
    {
        $bin = hex2bin($hex);
        if ($bin === false) {
            throw new ByteBufferException('Invalid hex string');
        }
        return new ByteBuffer($bin);
    }

    /**
     * Create a random ByteBuffer.
     *
     * @param   int     $length
     *
     * @return  ByteBuffer
     */
    public static function randomBuffer(int $length): ByteBufferInterface
    {
        if (function_exists('random_bytes')) {

            return new ByteBuffer(random_bytes($length < 1 ? 32 : $length));
        } else if (function_exists('openssl_random_pseudo_bytes')) {

            return new ByteBuffer(openssl_random_pseudo_bytes($length));
        }

        throw new ByteBufferException('Cannot generate random bytes');
    }

    public function getBytes(int $offset, int $length): string
    {
        if ($offset < 0 || $length < 0 || ($offset + $length > $this->length)) {
            throw new ByteBufferException('Invalid offset or length');
        }

        return substr($this->data, $offset, $length);
    }

    public function getByteVal(int $offset): int
    {
        if ($offset < 0 || $offset >= $this->length) {
            throw new ByteBufferException('Invalid offset');
        }
        return ord(substr($this->data, $offset, 1));
    }

    public function getJson(int $jsonFlags = 0): mixed
    {
        $data = json_decode($this->getBinaryString(), null, 512, $jsonFlags);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ByteBufferException(json_last_error_msg());
        }

        return $data;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function getUint16Val(int $offset): int
    {
        if ($offset < 0 || ($offset + 2) > $this->length) {
            throw new ByteBufferException('Invalid offset');
        }

        $val = unpack('n', $this->data, $offset);
        if (!isset($val[1])) {
            throw new ByteBufferException('Invalid offset');
        }

        return $val[1];
    }

    public function getUint32Val(int $offset): int
    {
        if ($offset < 0 || ($offset + 4) > $this->length) {
            throw new ByteBufferException('Invalid offset');
        }

        $val = (int) unpack('N', $this->data, $offset);
        if (!isset($val[1]) || $val[1] < 0) {
            // Signed integer overflow causes signed negative numbers
            throw new ByteBufferException('Value out of integer range.');
        }

        return $val[1];
    }

    public function getUint64Val(int $offset): int
    {
        if (PHP_INT_SIZE < 8) {
            throw new ByteBufferException('64-bit values not supported by this system');
        }

        if ($offset < 0 || ($offset + 8) > $this->length) {
            throw new ByteBufferException('Invalid offset');
        }

        $val = unpack('J', $this->data, $offset);
        if (!isset($val[1]) || $val[1] < 0) {
            // Signed integer overflow causes signed negative numbers
            throw new ByteBufferException('Value out of integer range.');
        }

        return $val[1];
    }

    public function getHalfFloatVal(int $offset): float
    {
        //FROM spec pseudo decode_half(unsigned char *halfp)
        $half = $this->getUint16Val($offset);

        $exp = ($half >> 10) & 0x1f;
        $mant = $half & 0x3ff;

        if ($exp === 0) {
            $val = $mant * (2 ** -24);
        } elseif ($exp !== 31) {
            $val = ($mant + 1024) * (2 ** ($exp - 25));
        } else {
            $val = ($mant === 0) ? INF : NAN;
        }

        return ($half & 0x8000) ? -$val : $val;
    }

    public function getFloatVal(int $offset): float
    {
        if ($offset < 0 || ($offset + 4) > $this->length) {
            throw new ByteBufferException('Invalid offset');
        }
        
        $val = unpack('G', $this->data, $offset);
        if (!isset($val[1])) {
            throw new ByteBufferException('Invalid offset');
        }

        return $val[1];
    }

    public function getDoubleVal(int $offset): float
    {
        if ($offset < 0 || ($offset + 8) > $this->length) {
            throw new ByteBufferException('Invalid offset');
        }

        $val = unpack('E', $this->data, $offset);
        if (!isset($val[1])) {
            throw new ByteBufferException('Invalid offset');
        }

        return $val[1];
    }

    public function getBinaryString(): string
    {
        return $this->data;
    }

    public function equals(string|ByteBufferInterface $buffer): bool
    {
        $data = is_object($buffer) ? $buffer->getBinaryString() : $buffer;

        return $data === $this->getBinaryString();
    }

    public function getHex(): string
    {
        return bin2hex($this->data);
    }

    public function getUUID(): string
    {
        $s = str_split(bin2hex($this->data), 4);

        return vsprintf('%s-%s-%s-%s-%s', [$s[0].$s[1], $s[2], $s[3], $s[4], $s[5].$s[6].$s[7]]);
    }

    public function isEmpty(): bool
    {
        return $this->length === 0;
    }

    public function jsonSerialize(): string
    {
        if (ByteBuffer::$useBase64UrlEncoding) {
            return rtrim(strtr(base64_encode($this->data), '+/', '-_'), '=');

        } else {
            return '=?BINARY?B?' . base64_encode($this->data) . '?=';
        }
    }

    public function serialize(): string
    {
        return serialize($this->data);
    }

    public function unserialize(string $serialized): void
    {
        $this->data   = unserialize($serialized);
        $this->length = strlen($this->data);
    }

    /**
     * (PHP 8 deprecates Serializable-Interface)
     *
     * @return  array<string,string>
     */
    public function __serialize(): array
    {
        return [
            'data' => serialize($this->data)
        ];
    }

    /**
     * object to string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getHex();
    }

    /**
     * (PHP 8 deprecates Serializable-Interface)
     *
     * @param array<string,string>  $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        if ($data !== [] && isset($data['data'])) {
            $this->data   = unserialize($data['data']);
            $this->length = strlen($this->data);
        }
    }
}
