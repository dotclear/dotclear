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

class MailSocket extends atoum
{
    public function testMail()
    {
        $header_from = 'From: contact@dotclear.org';
        $header_type = 'Content-Type: text/plain; charset=UTF-8;';

        $output = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'dc-temp-test-' . bin2hex(random_bytes(8)) . '.txt';

        $fgets_counter = 0;
        $fgets_socket  = [
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
        $commands = <<<EOT
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

        $that = $this;
        $this
            ->assert('mail')
            ->given($this->newTestedInstance())
                // Mock fsockopen()
                ->given($this->function->fsockopen = fn (string $hostname, int $port = -1, ?int & $error_code = null, ?string & $error_message = null, ?float $timeout = null) => fopen($output, 'wb'))
                // Mock fgets()
                ->given($this->function->fgets = function ($fp, ?int $length = null) use ($that, &$fgets_counter, $fgets_socket) {
                    return $fgets_socket[$fgets_counter++];
                })
                // Mock stream_set_timeout()
                ->given($this->function->stream_set_timeout = true)
                // Mock fclose()
                ->given($this->function->fclose = fn ($fp) => \fclose($fp))
                // Test method
                ->then
                    ->variable($this->testedInstance->mail('security@dotclear.org', 'Subject', 'Content', $header_from))
                        ->isNotFalse()
                    // Should read $output and verify its content
                    ->string(file_get_contents($output))
                        ->isEqualTo(str_replace("\n", "\r\n", $commands))

            ->assert('mail no from')
            ->given($this->newTestedInstance())
                ->given($this->function->fsockopen = false)
                ->then
                    ->exception(function () { $this->testedInstance->mail('contact@example.com', 'Subject', 'Content'); })
        ;

        // Final Cleanup
        if (file_exists($output)) {
            unlink($output);
        }
    }
}
