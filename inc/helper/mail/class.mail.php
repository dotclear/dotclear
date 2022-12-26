<?php
/**
 * @class mail
 * @brief Email utilities
 *
 * @package Clearbricks
 * @subpackage Mail
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class mail
{
    /**
     * Send email
     *
     * Sends email to destination. If a function called _mail() exists it will
     * be used instead of PHP mail() function. _mail() function should have the
     * same signature. Headers could be provided as a string or an array.
     *
     * @param string            $to           Email destination
     * @param string            $subject      Email subject
     * @param string            $message      Email message
     * @param string|array      $headers      Email headers
     * @param string            $params       UNIX mail additionnal parameters
     *
     * @return boolean                        true on success
     */
    public static function sendMail(string $to, string $subject, string $message, $headers = null, ?string $params = null): bool
    {
        /**
         * User defined mail function
         *
         * @var callable  $user_defined_mail
         */
        $user_defined_mail = function_exists('_mail') ? '_mail' : null;

        $eol = trim((string) ini_get('sendmail_path')) ? "\n" : "\r\n";

        if (is_array($headers)) {
            $headers = implode($eol, $headers);
        }

        if ($user_defined_mail == null) {
            if (!@mail($to, $subject, $message, $headers, $params)) {
                throw new Exception('Unable to send email');
            }
        } else {
            $user_defined_mail($to, $subject, $message, $headers, $params);
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
     * @return array|false
     */
    public static function getMX(string $host)
    {
        if (!getmxrr($host, $mx_hosts, $mx_weights) || count($mx_hosts)) {
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
     *
     * @return string
     */
    public static function B64Header(string $str, string $charset = 'UTF-8'): string
    {
        if (!preg_match('/[^\x00-\x3C\x3E-\x7E]/', $str)) {
            return $str;
        }

        return '=?' . $charset . '?B?' . base64_encode($str) . '?=';
    }
}
