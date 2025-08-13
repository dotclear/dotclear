<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Helper\WebAuthn\Util;

/**
 * @brief   WebAuthn byte buffer interface.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
interface ByteBufferInterface
{
    public static function useBase64UrlEncoding(bool $use): void;

    /**
     * Create Bytbuffer from binary string.
     *
     * @param   string  $binary     The binary string
     */
    public static function fromBinary(string $binary): ByteBufferInterface;

    /**
     * Create a ByteBuffer from a base64 url encoded string.
     */
    public static function fromBase64Url(string $base64url): ByteBufferInterface;

    /**
     * Create a ByteBuffer from a base64 url encoded string.
     */
    public static function fromHex(string $hex): ByteBufferInterface;

    /**
     * Create a random ByteBuffer.
     */
    public static function randomBuffer(int $length): ByteBufferInterface;

    public function getBytes(int $offset, int $length): string;

    public function getByteVal(int $offset): int;

    public function getJson(int $jsonFlags = 0): mixed;

    public function getLength(): int;

    public function getUint16Val(int $offset): int;

    public function getUint32Val(int $offset): int;

    public function getUint64Val(int $offset): int;

    public function getHalfFloatVal(int $offset): float;

    public function getFloatVal(int $offset): float;

    public function getDoubleVal(int $offset): float;

    /**
     * Get original binary buffer string.
     *
     * @return  string  The binary buffer
     */
    public function getBinaryString(): string;

    public function equals(string|ByteBufferInterface $buffer): bool;

    /**
     * Get binary in hex format.
     */
    public function getHex(): string;

    /**
     * Get UUID like format (used for attetsation AAGUID response).
     */
    public function getUUID(): string;

    /**
     * Check if binary is empty.
     */
    public function isEmpty(): bool;

    /**
     * jsonSerialize interface
     *
     * return binary data in RFC 1342-Like serialized string
     */
    public function jsonSerialize(): string;

    /**
     * Serializable-Interface.
     */
    public function serialize(): string;

    /**
     * Serializable-Interface.
     */
    public function unserialize(string $serialized): void;

    /*
     * (PHP 8 deprecates Serializable-Interface)
     *
     * @return  array<string,string>
     */
    //public function __serialize(): array;

    /*
     * object to string
     *
     * @return string
     */
    //public function __toString(): string;

    /*
     * (PHP 8 deprecates Serializable-Interface)
     *
     * @param array<string,string>  $data
     * @return void
     */
    //public function __unserialize(array $data): void;
}
