<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper;

/**
 * @class Crypt
 *
 * Cryptographic helpers
 */
class Crypt
{
    /**
     * SHA1 or MD5 + HMAC
     *
     * Returns an HMAC encoded value of <var>$data</var>, using the said <var>$key</var>
     * and <var>$hashfunc</var> as hash method (sha1 or md5 are accepted if hash_hmac function not exists.)
     *
     * @param    string    $key         Hash key
     * @param    string    $data        Data
     * @param    string    $hashfunc    Hash function (md5 or sha1)
     */
    public static function hmac(string $key, string $data, string $hashfunc = 'sha1'): string
    {
        if (function_exists('hash_hmac')) {
            if (!in_array($hashfunc, hash_hmac_algos())) {
                $hashfunc = 'sha1';
            }

            return hash_hmac($hashfunc, $data, $key);
        }

        return self::hmacLegacy($key, $data, $hashfunc);
    }

    /**
     * Legacy hmac method
     *
     * @param      string  $key       The key
     * @param      string  $data      The data
     * @param      string  $hashfunc  The hashfunc
     */
    public static function hmacLegacy(string $key, string $data, string $hashfunc = 'sha1'): string
    {
        // Legacy way
        if ($hashfunc !== 'sha1') {
            $hashfunc = 'md5';
        }
        $blocksize = 64;
        if (strlen($key) > $blocksize) {
            $key = pack('H*', $hashfunc($key));
        }
        $key  = str_pad($key, $blocksize, chr(0x00));
        $ipad = str_repeat(chr(0x36), $blocksize);
        $opad = str_repeat(chr(0x5c), $blocksize);
        $hmac = pack('H*', $hashfunc(($key ^ $opad) . pack('H*', $hashfunc(($key ^ $ipad) . $data))));

        return bin2hex($hmac);
    }

    /**
     * Password generator
     *
     * Returns an n characters random password.
     *
     * @param      integer $length required length
     */
    public static function createPassword(int $length = 8): string
    {
        // First shuffle charset random time (from 1 to 10)
        $charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890$!@';
        for ($x = 1; $x <= random_int(1, 10); $x++) {
            $charset = str_shuffle($charset);
        }

        // Then generate a random password from the resulting charset
        $password = '';
        for ($s = 1; $s <= $length; $s++) {
            $password .= substr($charset, random_int(0, strlen($charset) - 1), 1);
        }

        return $password;
    }
}
