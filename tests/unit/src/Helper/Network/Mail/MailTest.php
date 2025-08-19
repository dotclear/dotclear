<?php

declare(strict_types=1);

namespace Dotclear\Helper\Network\Mail {
    use Dotclear\Tests\Helper\MailTest;

    // Declare mail() function in same namespace as sendMail(), so it will be used before the native one
    function mail(
        string $to,
        string $subject,
        string $message,
        array|string $additional_headers = [],
        string $additional_params = ''
    ): bool {
        // Call test method to perform some test on given parameters
        return MailTest::mail($to, $subject, $message, $additional_headers, $additional_params);
    }
}

namespace Dotclear\Tests\Helper {
    use Exception;
    use PHPUnit\Framework\TestCase;

    class MailTest extends TestCase
    {
        // Local mock of mail() function
        public static function mail(
            string $to,
            string $subject,
            string $message,
            array|string $additional_headers = [],
            string $additional_params = ''
        ): bool {
            if ($to === '') {
                throw new Exception('Unable to send email');
            }
            if ($subject === '') {
                throw new Exception('Unable to send email');
            }
            if ($message === '') {
                throw new Exception('Unable to send email');
            }
            if ($additional_headers !== [] && $additional_headers[0] !== 'Content-Type: text/plain; charset=UTF-8;') {
                throw new Exception('Unable to send email');
            }
            if ($additional_params !== '' && $additional_params !== '-fwebmaster@example.com') {
                throw new Exception('Unable to send email');
            }
            if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
                throw new Exception('Unable to send email');
            }

            return true;
        }

        public function testsendMail()
        {
            // Note user defined _mail() will be tested via MailSocket class, no need to define it for tests

            $this->assertTrue(
                \Dotclear\Helper\Network\Mail\Mail::sendMail('contact@example.com', 'Subject', 'Content')
            );

            $this->expectException(Exception::class);
            \Dotclear\Helper\Network\Mail\Mail::sendMail('contact', 'Subject', 'Content');
            $this->expectExceptionMessage(
                'Unable to send email'
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

        public function testGetMX()
        {
            $mx = \Dotclear\Helper\Network\Mail\Mail::getMX('localhost');

            $this->assertFalse(
                $mx
            );

            $mx = \Dotclear\Helper\Network\Mail\Mail::getMX('dotclear.org');
            if ($mx === false) {
                // Probably a network issue (not connected?)
                return;
            }

            $this->assertIsArray(
                $mx
            );
            $this->assertNotEquals(
                [],
                $mx
            );
            $this->assertNotEquals(
                '',
                array_keys($mx)[0]
            );
            $this->assertGreaterThanOrEqual(
                0,
                array_values($mx)[0]
            );
        }

        public function testB64Header()
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
