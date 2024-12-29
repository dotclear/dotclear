<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Network\Mail;

use Dotclear\Helper\Text;
use Exception;

/**
 * @class SocketMail
 *
 * Send email through socket
 */
class MailSocket
{
    /**
     * Socket handle
     *
     * @var        resource|null|false
     */
    public static $fp;

    /**
     * Connection timeout (in seconds)
     */
    public static int $timeout = 10;

    /**
     * SMTP Relay to user
     */
    public static ?string $smtp_relay = null;

    /**
     * Send email through socket
     *
     * This static method sends an email through a simple socket connection.
     * If {@link $smtp_relay} is set, it will be used as a relay to send the
     * email. Instead, email is sent directly to MX host of domain.
     *
     * @param string                $to             Email destination
     * @param string                $subject        Email subject
     * @param string                $message        Email message
     * @param string|array<string>  $headers        Email headers
     *
     * @throws Exception
     */
    public static function mail(string $to, string $subject, string $message, $headers = null): void
    {
        if (!is_null($headers) && !is_array($headers)) {
            $headers = [$headers];
        }
        $from = self::getFrom($headers);

        $from_host = explode('@', $from);
        $from_host = $from_host[1];

        $to_host = explode('@', $to);
        $to_host = $to_host[1];

        if (self::$smtp_relay != null) {
            $mx = [gethostbyname(self::$smtp_relay) => 1];
        } else {
            $mx = Mail::getMX($to_host);
        }

        if ($mx !== false) {
            foreach (array_keys($mx) as $mx_host) {
                self::$fp = @fsockopen($mx_host, 25, $errno, $errstr, self::$timeout);

                if (self::$fp !== false) {
                    break;
                }
            }
        }

        if (!is_resource(self::$fp)) {
            self::$fp = null;

            throw new Exception('Unable to open socket');
        }

        # We need to read the first line
        fgets(self::$fp);

        $data = '';
        # EHLO cmd
        if (!self::cmd('EHLO ' . $from_host, $data)) {
            self::quit();

            throw new Exception($data);
        }

        # MAIL FROM: <...>
        if (!self::cmd('MAIL FROM: <' . $from . '>', $data)) {
            self::quit();

            throw new Exception($data);
        }

        # RCPT TO: <...>
        if (!self::cmd('RCPT TO: <' . $to . '>', $data)) {
            self::quit();

            throw new Exception($data);
        }

        # Compose mail and send it with DATA
        $buffer = 'Return-Path: <' . $from . ">\r\n" .
            'To: <' . $to . ">\r\n" .
            'Subject: ' . $subject . "\r\n";

        if ($headers) {
            foreach ($headers as $header) {
                $buffer .= $header . "\r\n";
            }
        }

        $buffer .= "\r\n\r\n" . $message;

        if (!self::sendMessage($buffer, $data)) {
            self::quit();

            throw new Exception($data);
        }

        self::quit();
    }

    /**
     * Gets the from.
     *
     * @param      array<string>      $headers  The headers
     *
     * @throws     Exception
     *
     * @return     string     The from.
     */
    private static function getFrom(?array $headers): string
    {
        if (!is_null($headers)) {
            // Try to find a from:… in header(s)
            foreach ($headers as $header) {
                $from = '';

                if (preg_match('/^from: (.+?)$/msi', $header, $m)) {
                    $from = trim($m[1]);
                }

                if (preg_match('/(?:<)(.+?)(?:$|>)/si', $from, $m)) {
                    $from = trim($m[1]);
                } elseif (preg_match('/^(.+?)\(/si', $from, $m)) {
                    $from = trim($m[1]);
                } elseif (!Text::isEmail($from)) {
                    $from = '';
                }

                if ($from !== '') {
                    return $from;
                }
            }
        }

        // Is a from set in configuration options ?
        $from = trim((string) ini_get('sendmail_from'));
        if ($from !== '') {
            return $from;
        }

        throw new Exception('No valid from e-mail address');
    }

    /**
     * Send SMTP command
     *
     * @param      string  $out    The out
     * @param      string  $data   The received data
     */
    private static function cmd(string $out, string &$data = ''): bool
    {
        if (self::$fp) {
            fwrite(self::$fp, $out . "\r\n");
        }
        $data = self::data();

        return str_starts_with($data, '250');
    }

    /**
     * Get data from opened stream
     */
    private static function data(): string
    {
        $buffer = '';
        if (self::$fp) {
            stream_set_timeout(self::$fp, 2);

            for ($i = 0; $i < 2; $i++) {
                $buffer .= fgets(self::$fp, 1024);
            }
        }

        return $buffer;
    }

    /**
     * Sends a message body.
     *
     * @param      string  $msg    The message
     * @param      string  $data   The data
     */
    private static function sendMessage(string $msg, string &$data): bool
    {
        $msg .= "\r\n.";

        self::cmd('DATA', $data);

        if (!str_starts_with($data, '354')) {
            return false;
        }

        return self::cmd($msg, $data);
    }

    /**
     * Send QUIT command and close socket handle
     */
    private static function quit(): void
    {
        self::cmd('QUIT');
        if (self::$fp) {
            fclose(self::$fp);
        }
        self::$fp = null;
    }
}
