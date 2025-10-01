<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper;

use Exception;
use PHPUnit\Framework\TestCase;

class RestServerTest extends TestCase
{
    // Rest functions

    public static function restTrueFn(): bool
    {
        return true;
    }

    public static function restErrorFn(): bool
    {
        throw new Exception('Error Processing Request');
    }

    /**
     * @return array<string, bool>
     */
    public static function restJsonFn(): array
    {
        $ret = [
            'check' => false,
            'ret'   => true,
        ];

        return $ret;
    }

    public static function restXmlFn(): \Dotclear\Helper\Html\XmlTag
    {
        $rsp = new \Dotclear\Helper\Html\XmlTag('test');

        $rsp->check = false;
        $rsp->ret   = true;

        return $rsp;
    }

    public static function restTrueFnParam(mixed $param = null): bool
    {
        if ($param !== null) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public static function restJsonFnParam(mixed $param = null): array
    {
        $ret = [
            'check' => false,
            'ret'   => true,
        ];
        if ($param !== null) {
            $ret['param'] = $param;
        }

        return $ret;
    }

    public static function restXmlFnParam(mixed $param = null): \Dotclear\Helper\Html\XmlTag
    {
        $rsp = new \Dotclear\Helper\Html\XmlTag('test');

        if ($param !== null) {
            $rsp->param = $param;
        }
        $rsp->check = false;
        $rsp->ret   = true;

        return $rsp;
    }

    // Rest server helpers

    protected function prepareServer(bool $withParam = true): \Dotclear\Helper\RestServer
    {
        $rest = new \Dotclear\Helper\RestServer();

        $rest->addFunction('TrueFn', self::restTrueFn(...));
        $rest->addFunction('JsonFn', self::restJsonFn(...));
        $rest->addFunction('XmlFn', self::restXmlFn(...));
        $rest->addFunction('ErrorFn', self::restErrorFn(...));
        if ($withParam) {
            $rest->addFunction('TrueFnParam', self::restTrueFnParam(...));
            $rest->addFunction('JsonFnParam', self::restJsonFnParam(...));
            $rest->addFunction('XmlFnParam', self::restXmlFnParam(...));
        }

        return $rest;
    }

    protected function runServer(
        \Dotclear\Helper\RestServer $rest,
        ?string $fn = null,
        string &$response = '',
        string $encoding = 'UTF-8',
        int $format = \Dotclear\Helper\RestServer::DEFAULT_RESPONSE,
        mixed $param = null,
    ): bool {
        // Set function
        if ($fn !== null && $fn !== '') {
            $_REQUEST['f'] = $fn;
        }

        // Run server
        ob_start();
        $ret      = $rest->serve($encoding, $format, $param);
        $response = (string) ob_get_contents();
        ob_end_clean();

        return $ret;
    }

    // Tests

    public function test(): void
    {
        $rest = new \Dotclear\Helper\RestServer();

        $this->assertEquals(
            new \Dotclear\Helper\Html\XmlTag('rsp'),
            $rest->rsp
        );
        $this->assertNull(
            $rest->json
        );
    }

    public function testAddFunction(): void
    {
        $rest = $this->prepareServer(false);

        $this->assertNull(
            $rest->json
        );
        $this->assertEquals(
            [
                'TrueFn'  => self::restTrueFn(...),
                'JsonFn'  => self::restJsonFn(...),
                'XmlFn'   => self::restXmlFn(...),
                'ErrorFn' => self::restErrorFn(...),
            ],
            $rest->functions
        );
    }

    public function testServe(): void
    {
        $rest = $this->prepareServer();
        $res  = '';

        $ret = $this->runServer($rest, 'TrueFn', $res);

        $this->assertTrue(
            $ret
        );
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8" ?>' . "\n" . '<rsp status="ok">1</rsp>',
            $res
        );
    }

    public function testServeJson(): void
    {
        $rest = $this->prepareServer();
        $res  = '';

        $ret = $this->runServer($rest, 'JsonFn', $res, 'UTF-8', \Dotclear\Helper\RestServer::JSON_RESPONSE);

        $this->assertTrue(
            $ret
        );
        $this->assertEquals(
            '{"success":true,"payload":{"check":false,"ret":true}}',
            $res
        );
    }

    public function testServeXml(): void
    {
        $rest = $this->prepareServer();
        $res  = '';

        $ret = $this->runServer($rest, 'XmlFn', $res, 'UTF-8', \Dotclear\Helper\RestServer::XML_RESPONSE);

        $this->assertTrue(
            $ret
        );
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8" ?>' . "\n" . '<rsp status="ok"><test check="" ret="1"/></rsp>',
            $res
        );
    }

    public function testServeWithWrongFormat(): void
    {
        $rest = $this->prepareServer();
        $res  = '';

        $ret = $this->runServer($rest, 'TrueFn', $res, 'UTF-8', -42);

        $this->assertTrue(
            $ret
        );
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8" ?>' . "\n" . '<rsp status="ok">1</rsp>',
            $res
        );
    }

    public function testServeWithAnotherEncoding(): void
    {
        $rest = $this->prepareServer();
        $res  = '';

        $ret = $this->runServer($rest, 'TrueFn', $res, 'ISO-8859-1');

        $this->assertTrue(
            $ret
        );
        $this->assertEquals(
            '<?xml version="1.0" encoding="ISO-8859-1" ?>' . "\n" . '<rsp status="ok">1</rsp>',
            $res
        );
    }

    public function testServeWithParam(): void
    {
        $rest = $this->prepareServer();
        $res  = '';

        $ret = $this->runServer($rest, 'TrueFnParam', $res, 'UTF-8', \Dotclear\Helper\RestServer::XML_RESPONSE, 'myparam');

        $this->assertTrue(
            $ret
        );
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8" ?>' . "\n" . '<rsp status="ok">0</rsp>',
            $res
        );
    }

    public function testServeWithWrongFn(): void
    {
        $rest = $this->prepareServer();
        $res  = '';

        $ret = $this->runServer($rest, 'FalseFn', $res);

        $this->assertFalse(
            $ret
        );
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8" ?>' . "\n" . '<rsp status="failed"><message>Function does not exist</message></rsp>',
            $res
        );
    }

    public function testServeWithErrorFn(): void
    {
        $rest = $this->prepareServer();
        $res  = '';

        $ret = $this->runServer($rest, 'ErrorFn', $res);

        $this->assertFalse(
            $ret
        );
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8" ?>' . "\n" . '<rsp status="failed"><message>Error Processing Request</message></rsp>',
            $res
        );
    }

    public function testServeWithNoFn(): void
    {
        $rest = $this->prepareServer();
        $res  = '';

        $ret = $this->runServer($rest, '', $res);

        $this->assertFalse(
            $ret
        );
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8" ?>' . "\n" . '<rsp status="failed"><message>No function given</message></rsp>',
            $res
        );
    }

    public function testServeWithWrongFnJson(): void
    {
        $rest = $this->prepareServer();
        $res  = '';

        $ret = $this->runServer($rest, 'FalseFn', $res, 'UTF-8', \Dotclear\Helper\RestServer::JSON_RESPONSE);

        $this->assertFalse(
            $ret
        );
        $this->assertEquals(
            '{"success":false,"message":"Function does not exist"}',
            $res
        );
    }

    public function testServeWithErrorFnJson(): void
    {
        $rest = $this->prepareServer();
        $res  = '';

        $ret = $this->runServer($rest, 'ErrorFn', $res, 'UTF-8', \Dotclear\Helper\RestServer::JSON_RESPONSE);

        $this->assertFalse(
            $ret
        );
        $this->assertEquals(
            '{"success":false,"message":"Error Processing Request"}',
            $res
        );
    }

    public function testServeWithNoFnJson(): void
    {
        $rest = $this->prepareServer();
        $res  = '';

        $ret = $this->runServer($rest, null, $res, 'UTF-8', \Dotclear\Helper\RestServer::JSON_RESPONSE);

        $this->assertFalse(
            $ret
        );
        $this->assertEquals(
            '{"success":false,"message":"No function given"}',
            $res
        );
    }

    public function testServeRestRequests(): void
    {
        $rest = $this->prepareServer();

        $this->assertTrue(
            $rest->serveRestRequests()
        );
    }
}
