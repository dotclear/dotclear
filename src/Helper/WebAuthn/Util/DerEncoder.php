<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Util;

use Dotclear\Interface\Helper\WebAuthn\Util\DerEncoderInterface;

/**
 * @brief   WebAuthn DER helper.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class DerEncoder implements DerEncoderInterface
{
    public static function encodeEC2(string $key_u2f): string
    {
        return static::sequence(
            static::sequence(
                static::oid("\x2A\x86\x48\xCE\x3D\x02\x01") . // OID 1.2.840.10045.2.1 ecPublicKey
                static::oid("\x2A\x86\x48\xCE\x3D\x03\x01\x07")  // 1.2.840.10045.3.1.7 prime256v1
            ) .
            static::bitString($key_u2f)
        );
    }

    public static function encodeOKP(string $key_x): string
    {
        return static::sequence(
            static::sequence(
                static::oid("\x2B\x65\x70") // OID 1.3.101.112 curveEd25519 (EdDSA 25519 signature algorithm)
            ) .
            static::bitString($key_x)
        );
    }

    public static function encodeRSA(string $key_n, string $key_e): string
    {
        return static::sequence(
            static::sequence(
                static::oid("\x2A\x86\x48\x86\xF7\x0D\x01\x01\x01") . // OID 1.2.840.113549.1.1.1 rsaEncryption
                static::nullValue()
            ) .
            static::bitString(
                static::sequence(
                    static::unsignedInteger($key_n) .
                    static::unsignedInteger($key_e)
                )
            )
        );
    }

    protected static function length(int $len): string
    {
        if ($len < 128) {
            return chr($len);
        }
        $lenBytes = '';
        while ($len > 0) {
            $lenBytes = chr($len % 256) . $lenBytes;
            $len      = intdiv($len, 256);
        }

        return chr(0x80 | strlen($lenBytes)) . $lenBytes;
    }

    protected static function sequence(string $contents): string
    {
        return "\x30" . static::length(strlen($contents)) . $contents;
    }

    protected static function oid(string $encoded): string
    {
        return "\x06" . static::length(strlen($encoded)) . $encoded;
    }

    protected static function bitString(string $bytes): string
    {
        return "\x03" . static::length(strlen($bytes) + 1) . "\x00" . $bytes;
    }

    protected static function nullValue(): string
    {
        return "\x05\x00";
    }

    protected static function unsignedInteger(string $bytes): string
    {
        $len = strlen($bytes);

        // Remove leading zero bytes
        for ($i = 0; $i < ($len - 1); $i++) {
            if (ord($bytes[$i]) !== 0) {
                break;
            }
        }
        if ($i !== 0) {
            $bytes = substr($bytes, $i);
        }

        // If most significant bit is set, prefix with another zero to prevent it being seen as negative number
        if ((ord($bytes[0]) & 0x80) !== 0) {
            $bytes = "\x00" . $bytes;
        }

        return "\x02" . static::length(strlen($bytes)) . $bytes;
    }
}
