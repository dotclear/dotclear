<?php

namespace Dotclear\Helper\Network\Mail {
    use Dotclear\Tests\Helper\MailSocketTest;

    // Mock some natives PHP functions used in tested methods

    function fsockopen(
        string $hostname,
        int $port = -1,
        ?int &$error_code = null,
        ?string &$error_message = null,
        ?float $timeout = null
    ): mixed {
        return MailSocketTest::fsockopen($hostname, $port, $error_code, $error_message, $timeout);
    }

    function fgets(mixed $stream, ?int $length = null): string|false
    {
        return MailSocketTest::fgets($stream, $length);
    }

    function stream_set_timeout(mixed $stream, int $seconds, int $microseconds = 0): bool
    {
        return MailSocketTest::stream_set_timeout($stream, $seconds, $microseconds);
    }

    function fclose(mixed $stream): bool
    {
        return MailSocketTest::fclose($stream);
    }
}

namespace Dotclear\Tests\Helper {
    use Exception;
    use PHPUnit\Framework\TestCase;

    class MailSocketTest extends TestCase
    {
        private static string $output;
        private static int $fgets_counter;
        private static array $fgets_socket;
        private static string $commands;
        private static $handle;
        private static bool $nosocket;

        protected function setUp(): void
        {
            self::$output        = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'dc-temp-test-' . bin2hex(random_bytes(8)) . '.txt';
            self::$fgets_counter = 0;
            self::$fgets_socket  = [
                // Init
                '220 mail.dotclear.net Dotclear MAIL Service ready at Fri, 13 Aug 2003 12:42:17 +0200 EHLO dotclear.org',
                '',
                // Response to HELO command
                '250-mail.dotclear.net Hello [127.0.0.1]',
                '',
                // Response to MAIL FROM command
                '250 2.1.0 Sender OK',
                '',
                // Response to RCPT TO command
                '250 2.1.5 Recipient OK',
                '',
                // Response to DATA command
                '354 Start mail input; end with <CRLF>.<CRLF>',
                '',
                // Response to end of DATA
                '250 2.6.0 <id@mail.dotclear.net> [InternalId=id, Hostname=mail.dotclear.net] Queued mail for delivery',
                '',
                // Response to QUIT command
                '221 2.0.0 Service closing transmission channel',
                '',
            ];
            self::$commands = <<<EOT
                EHLO dotclear.org
                MAIL FROM: <contact@dotclear.org>
                RCPT TO: <security@dotclear.org>
                DATA
                Return-Path: <contact@dotclear.org>
                To: <security@dotclear.org>
                Subject: Subject
                From: contact@dotclear.org


                Content
                .
                QUIT

                EOT;
            self::$nosocket = false;
        }

        protected function tearDown(): void
        {
            // Final Cleanup
            if (file_exists(self::$output)) {
                unlink(self::$output);
            }
        }

        public static function fsockopen(
            string $hostname,
            int $port = -1,
            ?int &$error_code = null,
            ?string &$error_message = null,
            ?float $timeout = null
        ): mixed {
            if (self::$nosocket) {
                return false;
            }

            self::$handle = \fopen(self::$output, 'wb');

            return self::$handle;
        }

        public static function fgets(mixed $stream, ?int $length = null): string|false
        {
            return self::$fgets_socket[self::$fgets_counter++];
        }

        public static function stream_set_timeout(mixed $stream, int $seconds, int $microseconds = 0): bool
        {
            return true;
        }

        public static function fclose(mixed $stream): bool
        {
            return \fclose(self::$handle);
        }

        public function testMail()
        {
            $header_from = 'From: contact@dotclear.org';

            $ret = \Dotclear\Helper\Network\Mail\MailSocket::mail('security@dotclear.org', 'Subject', 'Content', $header_from);

            $this->assertNotFalse(
                $ret
            );
            $this->assertEquals(
                str_replace("\n", "\r\n", self::$commands),
                file_get_contents(self::$output)
            );

            self::$nosocket = true;
            $this->expectException(Exception::class);
            $ret = \Dotclear\Helper\Network\Mail\MailSocket::mail('contact@example.com', 'Subject', 'Content');
        }
    }
}
