<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Network\Mail;

use Exception;

/**
 * @class Mail
 *
 * Mail helpers
 */
class Mail
{
    /**
     * Send email
     *
     * Sends email to destination. If a function called _mail() exists it will
     * be used instead of PHP mail() function. _mail() function should have the
     * same signature. Headers could be provided as a string or an array.
     *
     * @param string                $to           Email destination
     * @param string                $subject      Email subject
     * @param string                $message      Email message
     * @param string|array<string>  $headers      Email headers
     * @param string                $params       UNIX mail additionnal parameters
     *
     * @return boolean                        true on success
     */
    public static function sendMail(string $to, string $subject, string $message, $headers = null, ?string $params = null): bool
    {
        /**
         * User defined mail function
         *
         * @var callable|null  $user_defined_mail
         */
        $user_defined_mail = function_exists('_mail') ? '_mail' : null;

        $eol = trim((string) ini_get('sendmail_path')) !== '' ? "\n" : "\r\n";

        if (is_array($headers)) {
            $headers = implode($eol, $headers);
        } elseif (!is_string($headers)) {
            $headers = [];
        }

        if (is_null($user_defined_mail)) {
            if (!@mail($to, $subject, $message, $headers, (string) $params)) {
                throw new Exception('Unable to send email');
            }
        } else {
            $user_defined_mail($to, $subject, $message, $headers, (string) $params);
        }

        return true;
    }

    /**
     * Get Host MX
     *
     * Returns MX records sorted by weight for a given host.
     *
     * @param string    $host        Hostname
     *
     * @return array<string, mixed>|false
     */
    public static function getMX(string $host): false|array
    {
        if (!getmxrr($host, $mx_hosts, $mx_weights) || count($mx_hosts) === 0) {
            return false;
        }

        $res = array_combine($mx_hosts, $mx_weights);
        asort($res);

        return $res;
    }

    /**
     * B64 header
     *
     * Encodes given string as a base64 mail header.
     *
     * @param string   $str     String to encode
     * @param string   $charset Charset (default UTF-8)
     */
    public static function B64Header(string $str, string $charset = 'UTF-8'): string
    {
        if (!preg_match('/[^\x00-\x3C\x3E-\x7E]/', $str)) {
            return $str;
        }

        return '=?' . $charset . '?B?' . base64_encode($str) . '?=';
    }
}
