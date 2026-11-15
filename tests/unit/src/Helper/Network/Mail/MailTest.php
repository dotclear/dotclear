<?php

declare(strict_types=1);

namespace Dotclear\Helper\Network\Mail {
    use Dotclear\Tests\Helper\Network\Mail\MailTest;

    /**
     * Declare mail() function in same namespace as sendMail(), so it will be used before the native one
     *
     * @param  null|string|list<string> $additional_headers
     */
    function mail(
        string $to,
        string $subject,
        string $message,
        null|array|string $additional_headers = [],
        string $additional_params = ''
    ): bool {
        // Call test method to perform some test on given parameters
        return MailTest::mail($to, $subject, $message, $additional_headers, $additional_params);
    }

    function getmxrr(string $hostname, &$hosts, &$weights): bool
    {
        return MailTest::getMXRecords($hostname, $hosts, $weights);
    }
}

namespace Dotclear\Tests\Helper\Network\Mail {
    use Exception;
    use PHPUnit\Framework\TestCase;

    class MailTest extends TestCase
    {
        private static bool $failMail = false;

        public static function getMXRecords(string $hostname, &$hosts, &$weights): bool
        {
            if ($hostname === 'localhost') {
                $hosts   = [];
                $weights = [];

                return false;
            }

            $hosts   = ['mx-b.example.test', 'mx-a.example.test'];
            $weights = [20, 10];

            return true;
        }

        /**
         * Local mock of mail() function
         *
         * @param  null|string|list<string> $additional_headers
         */
        public static function mail(
            string $to,
            string $subject,
            string $message,
            null|array|string $additional_headers = null,
            string $additional_params = ''
        ): bool {
            if (self::$failMail) {
                return false;
            }

            if ($to === '') {
                throw new Exception('Unable to send email');
            }
            if ($subject === '') {
                throw new Exception('Unable to send email');
            }
            if ($message === '') {
                throw new Exception('Unable to send email');
            }
            if (!is_null($additional_headers)) {
                $expected_header = 'Content-Type: text/plain; charset=UTF-8;';
                if (is_string($additional_headers) && $additional_headers !== $expected_header
                    || is_array($additional_headers) && $additional_headers !== [] && $additional_headers[0] !== $expected_header
                ) {
                    throw new Exception('Unable to send email');
                }
            }
            if ($additional_params !== '' && $additional_params !== '-fwebmaster@example.com') {
                throw new Exception('Unable to send email');
            }
            if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
                throw new Exception('Unable to send email');
            }

            return true;
        }

        public function testSendMail(): void
        {
            // Note user defined _mail() will be tested via MailSocket class, no need to define it for tests

            $this->assertTrue(
                \Dotclear\Helper\Network\Mail\Mail::sendMail('contact@example.com', 'Subject', 'Content')
            );

            $header = 'Content-Type: text/plain; charset=UTF-8;';
            $param  = '-fwebmaster@example.com';

            $this->assertTrue(
                \Dotclear\Helper\Network\Mail\Mail::sendMail('contact@example.com', 'Subject', 'Content with string header', $header)
            );

            $this->assertTrue(
                \Dotclear\Helper\Network\Mail\Mail::sendMail('contact@example.com', 'Subject', 'Content with array of headers', [
                    'Content-Type: text/plain; charset=UTF-8;',
                ])
            );

            $this->assertTrue(
                \Dotclear\Helper\Network\Mail\Mail::sendMail('contact@example.com', 'Subject', 'Content with param', null, $param)
            );
        }

        public function testSendMailRejectsInvalidInputs(): void
        {
            foreach ([
                ['contact', 'Subject', 'Content', null, null],
                ['contact@example.com', '', 'Content', null, null],
                ['contact@example.com', 'Subject', '', null, null],
                ['contact@example.com', 'Subject', 'Content', 'Invalid header', null],
                ['contact@example.com', 'Subject', 'Content', null, '-invalid'],
            ] as [$to, $subject, $message, $headers, $params]) {
                try {
                    \Dotclear\Helper\Network\Mail\Mail::sendMail($to, $subject, $message, $headers, $params);
                    $this->fail('Expected invalid mail arguments to be rejected.');
                } catch (Exception $exception) {
                    $this->assertSame('Unable to send email', $exception->getMessage());
                }
            }
        }

        public function testSendMailRejectsTransportFailure(): void
        {
            self::$failMail = true;
            try {
                $this->expectException(Exception::class);
                $this->expectExceptionMessage('Unable to send email');
                \Dotclear\Helper\Network\Mail\Mail::sendMail('contact@example.com', 'Subject', 'Content');
            } finally {
                self::$failMail = false;
            }
        }

        public function testGetMX(): void
        {
            $this->assertFalse(\Dotclear\Helper\Network\Mail\Mail::getMX('localhost'));
            $this->assertSame(
                ['mx-a.example.test' => 10, 'mx-b.example.test' => 20],
                \Dotclear\Helper\Network\Mail\Mail::getMX('example.test')
            );
        }

        public function testB64Header(): void
        {
            $header = \Dotclear\Helper\Network\Mail\Mail::B64Header('dotclear');

            $this->assertEquals(
                'dotclear',
                $header
            );

            $header = \Dotclear\Helper\Network\Mail\Mail::B64Header('dotclear=');

            $this->assertEquals(
                '=?UTF-8?B?ZG90Y2xlYXI9?=',
                $header
            );

            $header = \Dotclear\Helper\Network\Mail\Mail::B64Header('génial');

            $this->assertEquals(
                '=?UTF-8?B?Z8OpbmlhbA==?=',
                $header
            );

            $header = \Dotclear\Helper\Network\Mail\Mail::B64Header('génial=');

            $this->assertEquals(
                '=?UTF-8?B?Z8OpbmlhbD0=?=',
                $header
            );

            $header = \Dotclear\Helper\Network\Mail\Mail::B64Header('des œufs', 'UTF-8');

            $this->assertEquals(
                '=?UTF-8?B?ZGVzIMWTdWZz?=',
                $header
            );

            $header = \Dotclear\Helper\Network\Mail\Mail::B64Header('des œufs', 'UTF-8');

            $this->assertEquals(
                '=?UTF-8?B?ZGVzIMWTdWZz?=',
                $header
            );
            $header = \Dotclear\Helper\Network\Mail\Mail::B64Header('des œufs', 'iso-8859-1');

            $this->assertEquals(
                '=?iso-8859-1?B?ZGVzIMWTdWZz?=',
                $header
            );

            $header = \Dotclear\Helper\Network\Mail\Mail::B64Header('des œufs', 'iso-8859-1');

            $this->assertEquals(
                '=?iso-8859-1?B?ZGVzIMWTdWZz?=',
                $header
            );
        }
    }
}
