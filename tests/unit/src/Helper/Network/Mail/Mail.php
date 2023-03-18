<?php
/**
 * Unit tests
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace tests\unit\Dotclear\Helper\Network\Mail;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'bootstrap.php']);

use atoum;

class Mail extends atoum
{
    public function testsendMail()
    {
        // Note user defined _mail() will be tested via MailSocket class, no need to define it for tests

        $that = $this;
        $this
            ->assert('mail')
            ->given($this->newTestedInstance())
                ->if($this->function->mail = function (string $to, string $subject, string $message, $headers, string $params = '') use ($that) {
                    $that
                        ->string($to)
                        ->isNotEmpty()
                        ->boolean(filter_var($to, FILTER_VALIDATE_EMAIL) !== false)
                        ->isTrue()
                        ->string($subject)
                        ->isNotEmpty()
                        ->string($message)
                        ->isNotEmpty()
                    ;

                    return true;
                })
                ->then
                    ->boolean($this->testedInstance->sendMail('contact@example.com', 'Subject', 'Content'))
                        ->isTrue()
                    ->function('mail')
                        ->wasCalled()
                        ->once()

            ->assert('mail failed')
            ->given($this->newTestedInstance())
                ->if($this->function->mail = fn () => false)
                ->then
                    ->exception(function () { $this->testedInstance->sendMail('contact', 'Subject', 'Content'); })
        ;

        $header = 'Content-Type: text/plain; charset=UTF-8;';
        $param  = '-fwebmaster@example.com';

        $this
            ->assert('mail with string header')
            ->given($this->newTestedInstance())
                ->if($this->function->mail = function (string $to, string $subject, string $message, $headers, string $params = '') use ($that) {
                    $that
                        ->string($to)
                        ->isNotEmpty()
                        ->boolean(filter_var($to, FILTER_VALIDATE_EMAIL) !== false)
                        ->isTrue()
                        ->string($subject)
                        ->isNotEmpty()
                        ->string($message)
                        ->isNotEmpty()
                        ->string($headers)
                        ->isEqualTo('Content-Type: text/plain; charset=UTF-8;')
                    ;

                    return true;
                })
                ->then
                    ->boolean($this->testedInstance->sendMail('contact@example.com', 'Subject', 'Content with string header', $header))
                        ->isTrue()
                    ->function('mail')
                        ->wasCalled()
                        ->once()
        ;

        $this
            ->assert('mail with array of headers')
            ->given($this->newTestedInstance())
                ->if($this->function->mail = function (string $to, string $subject, string $message, $headers, string $params = '') use ($that) {
                    $that
                        ->string($to)
                        ->isNotEmpty()
                        ->boolean(filter_var($to, FILTER_VALIDATE_EMAIL) !== false)
                        ->isTrue()
                        ->string($subject)
                        ->isNotEmpty()
                        ->string($message)
                        ->isNotEmpty()
                        ->string($headers)
                        ->isEqualTo('Content-Type: text/plain; charset=UTF-8;')
                    ;

                    return true;
                })
                ->then
                    ->boolean($this->testedInstance->sendMail('contact@example.com', 'Subject', 'Content with array of headers', [
                        'Content-Type: text/plain; charset=UTF-8;',
                    ]))
                        ->isTrue()
                    ->function('mail')
                        ->wasCalled()
                        ->once()
        ;

        $this
            ->assert('mail with param')
            ->given($this->newTestedInstance())
                ->if($this->function->mail = function (string $to, string $subject, string $message, $headers, string $params = '') use ($that) {
                    $that
                        ->string($to)
                        ->isNotEmpty()
                        ->boolean(filter_var($to, FILTER_VALIDATE_EMAIL) !== false)
                        ->isTrue()
                        ->string($subject)
                        ->isNotEmpty()
                        ->string($message)
                        ->isNotEmpty()
                        ->string($params)
                        ->isEqualTo('-fwebmaster@example.com')
                    ;

                    return true;
                })
                ->then
                    ->boolean($this->testedInstance->sendMail('contact@example.com', 'Subject', 'Content with param', null, $param))
                        ->isTrue()
                    ->function('mail')
                        ->wasCalled()
                        ->once()
        ;
    }

    public function testGetMX()
    {
        $mx = \Dotclear\Helper\Network\Mail\Mail::getMX('localhost');

        $this
            ->boolean($mx)
            ->isFalse()
        ;

        $mx = \Dotclear\Helper\Network\Mail\Mail::getMX('dotclear.org');
        if ($mx === false) {
            // Probably a network issue (not connected?)
            return;
        }

        $this
            ->array($mx)
            ->size->isGreaterThan(0)
            ->string(array_keys($mx)[0])
            ->isNotEmpty()
            ->integer(array_values($mx)[0])
            ->isGreaterThanOrEqualTo(0)
        ;
    }

    public function testB64Header()
    {
        $header = \Dotclear\Helper\Network\Mail\Mail::B64Header('dotclear');

        $this
            ->string($header)
            ->isEqualTo('dotclear')
        ;

        $header = \Dotclear\Helper\Network\Mail\Mail::B64Header('dotclear=');

        $this
            ->string($header)
            ->isEqualTo('=?UTF-8?B?ZG90Y2xlYXI9?=')
        ;

        $header = \Dotclear\Helper\Network\Mail\Mail::B64Header('génial');

        $this
            ->string($header)
            ->isEqualTo('=?UTF-8?B?Z8OpbmlhbA==?=')
        ;

        $header = \Dotclear\Helper\Network\Mail\Mail::B64Header('génial=');

        $this
            ->string($header)
            ->isEqualTo('=?UTF-8?B?Z8OpbmlhbD0=?=')
        ;

        $header = \Dotclear\Helper\Network\Mail\Mail::B64Header('des œufs', 'UTF-8');

        $this
            ->string($header)
            ->isEqualTo('=?UTF-8?B?ZGVzIMWTdWZz?=')
        ;

        $header = \Dotclear\Helper\Network\Mail\Mail::B64Header('des œufs', 'UTF-8');

        $this
            ->string($header)
            ->isEqualTo('=?UTF-8?B?ZGVzIMWTdWZz?=')
        ;
        $header = \Dotclear\Helper\Network\Mail\Mail::B64Header('des œufs', 'iso-8859-1');

        $this
            ->string($header)
            ->isEqualTo('=?iso-8859-1?B?ZGVzIMWTdWZz?=')
        ;

        $header = \Dotclear\Helper\Network\Mail\Mail::B64Header('des œufs', 'iso-8859-1');

        $this
            ->string($header)
            ->isEqualTo('=?iso-8859-1?B?ZGVzIMWTdWZz?=')
        ;
    }
}
