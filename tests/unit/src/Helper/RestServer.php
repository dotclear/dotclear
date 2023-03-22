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

namespace tests\unit\Dotclear\Helper;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'bootstrap.php']);

use atoum;
use Exception;

/**
 * @tags RestServer
 */
class RestServer extends atoum
{
    public static function restTrueFn(array $get = [], array $post = [])
    {
        return true;
    }

    public static function restErrorFn(array $get = [], array $post = [])
    {
        throw new Exception('Error Processing Request');

        return false;
    }

    public static function restJsonFn(array $get = [], array $post = [])
    {
        $ret = [
            'check' => false,
            'ret'   => true,
        ];

        return $ret;
    }

    public static function restXmlFn(array $get = [], array $post = [])
    {
        $rsp = new \Dotclear\Helper\Html\XmlTag('test');

        $rsp->check = false;
        $rsp->ret   = true;

        return $rsp;
    }

    public static function restTrueFnParam($param = null, array $get = [], array $post = [])
    {
        if ($param !== null) {
            return false;
        }

        return true;
    }

    public static function restJsonFnParam($param = null, array $get = [], array $post = [])
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

    public static function restXmlFnParam($param = null, array $get = [], array $post = [])
    {
        $rsp = new \Dotclear\Helper\Html\XmlTag('test');

        if ($param !== null) {
            $rsp->param = $param;
        }
        $rsp->check = false;
        $rsp->ret   = true;

        return $rsp;
    }

    public function test()
    {
        $rest = new \Dotclear\Helper\RestServer();

        $this
            ->object($rest)
            ->isNotNull()
            ->variable($rest->rsp)
            ->isEqualTo(new \Dotclear\Helper\Html\XmlTag('rsp'))
            ->variable($rest->json)
            ->isNull()
        ;
    }

    protected static function prepareServer(bool $withParam = true): \Dotclear\Helper\RestServer
    {
        $rest = new \Dotclear\Helper\RestServer();

        $rest->addFunction('TrueFn', [self::class, 'restTrueFn']);
        $rest->addFunction('JsonFn', [self::class, 'restJsonFn']);
        $rest->addFunction('XmlFn', [self::class, 'restXmlFn']);
        $rest->addFunction('ErrorFn', [self::class, 'restErrorFn']);
        if ($withParam) {
            $rest->addFunction('TrueFnParam', [self::class, 'restTrueFnParam']);
            $rest->addFunction('JsonFnParam', [self::class, 'restJsonFnParam']);
            $rest->addFunction('XmlFnParam', [self::class, 'restXmlFnParam']);
        }

        return $rest;
    }

    public function testAddFunction()
    {
        $rest = self::prepareServer(false);

        $this
            ->variable($rest->rsp)
            ->isNotNull()
            ->variable($rest->json)
            ->isNull()
            ->array($rest->functions)
            ->isEqualTo([
                'TrueFn'  => [self::class, 'restTrueFn'],
                'JsonFn'  => [self::class, 'restJsonFn'],
                'XmlFn'   => [self::class, 'restXmlFn'],
                'ErrorFn' => [self::class, 'restErrorFn'],
            ])
        ;
    }

    protected static function runServer(
        \Dotclear\Helper\RestServer $rest,
        ?string $fn = null,
        string &$response = '',
        string $encoding = 'UTF-8',
        int $format = \Dotclear\Helper\RestServer::DEFAULT_RESPONSE,
        $param = null,
    ): bool {
        // Set function
        if ($fn !== null) {
            $_REQUEST['f'] = $fn;
        }

        // Run server
        ob_start();
        $ret      = $rest->serve($encoding, $format, $param);
        $response = (string) ob_get_contents();
        ob_end_clean();

        return $ret;
    }

    public function testServe()
    {
        $rest = self::prepareServer();
        $res  = '';

        $this
            ->given($ret = self::runServer($rest, 'TrueFn', $res))
            ->then()
            ->boolean($ret)
            ->isTrue()
            ->string($res)
            ->isEqualTo('<?xml version="1.0" encoding="UTF-8" ?>' . "\n" . '<rsp status="ok">1</rsp>')
        ;
    }

    public function testServeJson()
    {
        $rest = self::prepareServer();
        $res  = '';

        $this
            ->given($ret = self::runServer($rest, 'JsonFn', $res, 'UTF-8', \Dotclear\Helper\RestServer::JSON_RESPONSE))
            ->then()
            ->boolean($ret)
            ->isTrue()
            ->string($res)
            ->isEqualTo('{"success":true,"payload":{"check":false,"ret":true}}')
        ;
    }

    public function testServeXml()
    {
        $rest = self::prepareServer();
        $res  = '';

        $this
            ->given($ret = self::runServer($rest, 'XmlFn', $res, 'UTF-8', \Dotclear\Helper\RestServer::XML_RESPONSE))
            ->then()
            ->boolean($ret)
            ->isTrue()
            ->string($res)
            ->isEqualTo('<?xml version="1.0" encoding="UTF-8" ?>' . "\n" . '<rsp status="ok"><test check="" ret="1"/></rsp>')
        ;
    }

    public function testServeWithWrongFormat()
    {
        $rest = self::prepareServer();
        $res  = '';

        $this
            ->given($ret = self::runServer($rest, 'TrueFn', $res, 'UTF-8', -42))
            ->then()
            ->boolean($ret)
            ->isTrue()
            ->string($res)
            ->isEqualTo('<?xml version="1.0" encoding="UTF-8" ?>' . "\n" . '<rsp status="ok">1</rsp>')
        ;
    }

    public function testServeWithAnotherEncoding()
    {
        $rest = self::prepareServer();
        $res  = '';

        $this
            ->given($ret = self::runServer($rest, 'TrueFn', $res, 'ISO-8859-1'))
            ->then()
            ->boolean($ret)
            ->isTrue()
            ->string($res)
            ->isEqualTo('<?xml version="1.0" encoding="ISO-8859-1" ?>' . "\n" . '<rsp status="ok">1</rsp>')
        ;
    }

    public function testServeWithParam()
    {
        $rest = self::prepareServer();
        $res  = '';

        $this
            ->given($ret = self::runServer($rest, 'TrueFnParam', $res, 'UTF-8', \Dotclear\Helper\RestServer::XML_RESPONSE, 'myparam'))
            ->then()
            ->boolean($ret)
            ->isTrue()
            ->string($res)
            ->isEqualTo('<?xml version="1.0" encoding="UTF-8" ?>' . "\n" . '<rsp status="ok">0</rsp>')
        ;
    }

    public function testServeWithWrongFn()
    {
        $rest = self::prepareServer();
        $res  = '';

        $this
            ->given($ret = self::runServer($rest, 'FalseFn', $res))
            ->then()
            ->boolean($ret)
            ->isFalse()
            ->string($res)
            ->isEqualTo('<?xml version="1.0" encoding="UTF-8" ?>' . "\n" . '<rsp status="failed"><message>Function does not exist</message></rsp>')
        ;
    }

    public function testServeWithErrorFn()
    {
        $rest = self::prepareServer();
        $res  = '';

        $this
            ->given($ret = self::runServer($rest, 'ErrorFn', $res))
            ->then()
            ->boolean($ret)
            ->isFalse()
            ->string($res)
            ->isEqualTo('<?xml version="1.0" encoding="UTF-8" ?>' . "\n" . '<rsp status="failed"><message>Error Processing Request</message></rsp>')
        ;
    }

    public function testServeWithNoFn()
    {
        $rest = self::prepareServer();
        $res  = '';

        $this
            ->given($ret = self::runServer($rest, null, $res))
            ->then()
            ->boolean($ret)
            ->isFalse()
            ->string($res)
            ->isEqualTo('<?xml version="1.0" encoding="UTF-8" ?>' . "\n" . '<rsp status="failed"><message>No function given</message></rsp>')
        ;
    }

    public function testServeWithWrongFnJson()
    {
        $rest = self::prepareServer();
        $res  = '';

        $this
            ->given($ret = self::runServer($rest, 'FalseFn', $res, 'UTF-8', \Dotclear\Helper\RestServer::JSON_RESPONSE))
            ->then()
            ->boolean($ret)
            ->isFalse()
            ->string($res)
            ->isEqualTo('{"success":false,"message":"Function does not exist"}')
        ;
    }

    public function testServeWithErrorFnJson()
    {
        $rest = self::prepareServer();
        $res  = '';

        $this
            ->given($ret = self::runServer($rest, 'ErrorFn', $res, 'UTF-8', \Dotclear\Helper\RestServer::JSON_RESPONSE))
            ->then()
            ->boolean($ret)
            ->isFalse()
            ->string($res)
            ->isEqualTo('{"success":false,"message":"Error Processing Request"}')
        ;
    }

    public function testServeWithNoFnJson()
    {
        $rest = self::prepareServer();
        $res  = '';

        $this
            ->given($ret = self::runServer($rest, null, $res, 'UTF-8', \Dotclear\Helper\RestServer::JSON_RESPONSE))
            ->then()
            ->boolean($ret)
            ->isFalse()
            ->string($res)
            ->isEqualTo('{"success":false,"message":"No function given"}')
        ;
    }
}
