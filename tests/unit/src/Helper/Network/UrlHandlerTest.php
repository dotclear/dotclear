<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Network;

use Exception;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\TestCase;

class UrlHandlerTest extends TestCase
{
    // Default handler
    public static function defaultHandler(?string $args = null)
    {
        echo 'urlHandler:' . 'default' . ':' . ($args ?? '');
    }

    // Specific handler
    public static function myHandler(?string $args = null)
    {
        echo 'urlHandler:' . 'my' . ':' . ($args ?? '');
    }

    // Specific handler
    public static function altHandler(?string $args = null)
    {
        echo 'urlHandler:' . 'alt' . ':' . ($args ?? '');
    }

    // Exception handler
    public static function exceptionHandler(?string $args = null)
    {
        echo 'urlHandler:' . 'exception' . ':' . ($args ?? '');
    }

    // Error handler, catch exception and output full error message
    public static function catchHandler(?string $args, string $type, Exception $e): bool
    {
        echo 'urlHandler:' . 'catch' . ':' . $type . ':' . ($args ?? '') . ':' . $e->getMessage() . ':' . $e->getCode();

        return true;
    }

    // Test methods

    public function test()
    {
        $url = new \Dotclear\Helper\Network\UrlHandler();

        $typeHandler = function (string $type, ?string $args = null) use ($url) {
            ob_start();
            $url->callHandler($type, $args);
            $output = ob_get_contents();
            ob_end_clean();

            return $output;
        };

        $defaultHandler = function (?string $args = null) use ($url) {
            ob_start();
            $url->callDefaultHandler($args);
            $output = ob_get_contents();
            ob_end_clean();

            return $output;
        };

        $this->expectException(Exception::class);

        $url->registerDefault([$this::class, 'defaultHandler']);

        $this->assertEquals(
            'urlHandler:default:',
            $defaultHandler()
        );

        $url->register('my', '', '.*', [$this::class, 'myHandler']);

        $this->assertEquals(
            'urlHandler:my:',
            $typeHandler('my')
        );

        $url->register('alt', '', '.*', [$this::class, 'altHandler']);

        $this->assertEquals(
            'urlHandler:alt:',
            $typeHandler('alt')
        );

        $this->assertEquals(
            [
                'my',
                'alt',
            ],
            array_keys($url->getTypes())
        );

        $url->register('exception', '', '.*', [$this::class, 'exceptionHandler']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'Error Processing Request'
        );

        $typeHandler('exception');

        $url->registerError([$this::class, 'catchHandler']);

        $this->assertEquals(
            'urlHandler:exception:',
            $typeHandler('exception')
        );

        $url->unregister('my');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'Unknown URL type'
        );

        $url->callHandler('my');
    }

    public function testUnset()
    {
        $url = new \Dotclear\Helper\Network\UrlHandler();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'Undefined default URL handler'
        );

        $url->callDefaultHandler();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'Unknown URL type'
        );

        $url->callHandler('default');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'Unknown URL type'
        );

        $url->callHandler('my');
    }

    public function testUnableToCall()
    {
        $url = new \Dotclear\Helper\Network\UrlHandler();
        $url->registerDefault('notCallable');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'Unable to call function'
        );

        $url->callDefaultHandler();
    }

    public function testGetBase()
    {
        $url = new \Dotclear\Helper\Network\UrlHandler();
        $url->registerDefault([$this::class, 'defaultHandler']);
        $url->register('lang', '', 'lang', [$this::class, 'myHandler']);
        $url->register('post', 'post', '^post/(.+)$', [$this::class, 'altHandler']);
        $url->registerError([$this::class, 'catchHandler']);

        $this->assertEquals(
            '',
            $url->getBase('lang')
        );
        $this->assertEquals(
            'post',
            $url->getBase('post')
        );
        $this->assertEquals(
            '',
            $url->getBase('top')
        );
        $this->assertEquals(
            '',
            $url->getBase('default')
        );
    }

    #[BackupGlobals(true)]
    public function testGetArgs()
    {
        $url = new \Dotclear\Helper\Network\UrlHandler();
        $url->registerDefault([$this::class, 'defaultHandler']);
        $url->register('lang', '', 'lang', [$this::class, 'myHandler']);
        $url->register('post', 'post', '^post/(.+)$', [$this::class, 'altHandler']);
        $url->registerError([$this::class, 'catchHandler']);

        $_SERVER['QUERY_STRING'] = '';
        $_SERVER['REQUEST_URI']  = 'https://example.com/index.php';

        $_SERVER['URL_REQUEST_PART'] = '';

        $type = '';
        $args = '';

        $url->getArgs($_SERVER['URL_REQUEST_PART'], $type, $args);

        $this->assertNull(
            $type
        );
        $this->assertNull(
            $args
        );

        $_SERVER['QUERY_STRING'] = 'lang';
        $_SERVER['REQUEST_URI']  = 'https://example.com/index.php?lang';
        $_GET['lang']            = '';
        $_REQUEST['lang']        = '';

        $_SERVER['URL_REQUEST_PART'] = 'lang';

        $type = '';
        $args = '';

        $url->getArgs($_SERVER['URL_REQUEST_PART'], $type, $args);

        $this->assertEquals(
            'lang',
            $type
        );
        $this->assertNull(
            $args
        );

        $_SERVER['QUERY_STRING'] = 'top=2';
        $_SERVER['REQUEST_URI']  = 'https://example.com/index.php?top=2';
        $_GET['top']             = '2';
        $_REQUEST['top']         = '2';

        $_SERVER['URL_REQUEST_PART'] = 'top=2';

        $type = '';
        $args = '';

        $url->getArgs($_SERVER['URL_REQUEST_PART'], $type, $args);

        $this->assertEquals(
            '',
            $type
        );
        $this->assertEquals(
            'top=2',
            $args
        );
    }

    #[BackupGlobals(true)]
    public function testGetArgsQueryString()
    {
        $url = new \Dotclear\Helper\Network\UrlHandler('query_string');
        $url->registerDefault([$this::class, 'defaultHandler']);
        $url->register('lang', '', '^([a-zA-Z]{2}(?:-[a-z]{2})?(?:/page/[0-9]+)?)$', [$this::class, 'myHandler']);
        $url->register('post', 'post', '^post/(.+)$', [$this::class, 'altHandler']);
        $url->registerError([$this::class, 'catchHandler']);

        $_SERVER['QUERY_STRING'] = 'post/2023/03/26/mypost&pub=1';
        $_SERVER['REQUEST_URI']  = 'https://example.com/index.php?post/2023/03/26/mypost&pub=1';
        $_GET['pub']             = '1';
        $_REQUEST['pub']         = '1';

        $_SERVER['URL_REQUEST_PART'] = 'post/2023/03/26/mypost&pub=1';

        $type = '';
        $args = '';

        $url->getArgs($_SERVER['URL_REQUEST_PART'], $type, $args);

        $this->assertEquals(
            'post',
            $type
        );
        $this->assertEquals(
            '2023/03/26/mypost&pub=1',
            $args
        );
    }

    #[BackupGlobals(true)]
    public function testGetArgsPathInfo()
    {
        $url = new \Dotclear\Helper\Network\UrlHandler('path_info');
        $url->registerDefault([$this::class, 'defaultHandler']);
        $url->register('lang', '', '^([a-zA-Z]{2}(?:-[a-z]{2})?(?:/page/[0-9]+)?)$', [$this::class, 'myHandler']);
        $url->register('post', 'post', '^post/(.+)$', [$this::class, 'altHandler']);
        $url->registerError([$this::class, 'catchHandler']);

        $_SERVER['QUERY_STRING'] = 'post/2023/03/26/mypost&pub=1';
        $_SERVER['REQUEST_URI']  = 'https://example.com/index.php/post/2023/03/26/mypost&pub=1';
        $_SERVER['PATH_INFO']    = 'post/2023/03/26/mypost';
        $_GET['pub']             = '1';
        $_REQUEST['pub']         = '1';

        $_SERVER['URL_REQUEST_PART'] = 'post/2023/03/26/mypost&pub=1';

        $type = '';
        $args = '';

        $url->getArgs($_SERVER['URL_REQUEST_PART'], $type, $args);

        $this->assertEquals(
            'post',
            $type
        );
        $this->assertEquals(
            '2023/03/26/mypost&pub=1',
            $args
        );
    }

    #[BackupGlobals(true)]
    public function testGetDocumentQueryString()
    {
        $url = new \Dotclear\Helper\Network\UrlHandler('query_string');
        $url->registerDefault([$this::class, 'defaultHandler']);
        $url->register('lang', '', '^([a-zA-Z]{2}(?:-[a-z]{2})?(?:/page/[0-9]+)?)$', [$this::class, 'myHandler']);
        $url->register('post', 'post', '^post/(.+)$', [$this::class, 'altHandler']);
        $url->registerError([$this::class, 'catchHandler']);

        $getDocument = function () use ($url) {
            ob_start();
            $url->getDocument();
            $output = ob_get_contents();
            ob_end_clean();

            return $output;
        };

        $_SERVER['QUERY_STRING'] = 'post/2023/03/26/mypost&pub=1';
        $_SERVER['REQUEST_URI']  = 'https://example.com/index.php?post/2023/03/26/mypost&pub=1';
        $_GET['pub']             = '1';
        $_REQUEST['pub']         = '1';

        $this->assertEquals(
            'urlHandler:alt:2023/03/26/mypost',
            $getDocument()
        );
        $this->assertEquals(
            [
                'pub' => '1',
            ],
            $_GET
        );
        $this->assertEquals(
            [
                'pub' => '1',
            ],
            $_REQUEST
        );
    }

    #[BackupGlobals(true)]
    public function testGetDocumentPathInfo()
    {
        $url = new \Dotclear\Helper\Network\UrlHandler('path_info');
        $url->registerDefault([$this::class, 'defaultHandler']);
        $url->register('lang', '', '^([a-zA-Z]{2}(?:-[a-z]{2})?(?:/page/[0-9]+)?)$', [$this::class, 'myHandler']);
        $url->register('post', 'post', '^post/(.+)$', [$this::class, 'altHandler']);
        $url->registerError([$this::class, 'catchHandler']);

        $getDocument = function () use ($url) {
            ob_start();
            $url->getDocument();
            $output = ob_get_contents();
            ob_end_clean();

            return $output;
        };

        $_SERVER['QUERY_STRING'] = 'post/2023/03/26/mypost&pub=1';
        $_SERVER['REQUEST_URI']  = 'https://example.com/index.php?post/2023/03/26/mypost&pub=1';
        $_SERVER['PATH_INFO']    = '/post/2023/03/26/mypost';
        $_GET['pub']             = '1';
        $_REQUEST['pub']         = '1';

        $this->assertEquals(
            'urlHandler:alt:2023/03/26/mypost',
            $getDocument()
        );
        $this->assertEquals(
            [
                'pub' => '1',
            ],
            $_GET
        );
        $this->assertEquals(
            [
                'pub' => '1',
            ],
            $_REQUEST
        );
    }

    #[BackupGlobals(true)]
    public function testGetDocumentDefault()
    {
        $url = new \Dotclear\Helper\Network\UrlHandler('query_string');
        $url->registerDefault([$this::class, 'defaultHandler']);
        $url->register('lang', '', '^([a-zA-Z]{2}(?:-[a-z]{2})?(?:/page/[0-9]+)?)$', [$this::class, 'myHandler']);
        $url->register('post', 'post', '^post/(.+)$', [$this::class, 'altHandler']);
        $url->registerError([$this::class, 'catchHandler']);

        $getDocument = function () use ($url) {
            ob_start();
            $url->getDocument();
            $output = ob_get_contents();
            ob_end_clean();

            return $output;
        };

        $_SERVER['QUERY_STRING'] = 'top';
        $_SERVER['REQUEST_URI']  = 'https://example.com/index.php?top';
        $_GET['top']             = '';
        $_REQUEST['top']         = '';

        $this->assertEquals(
            'urlHandler:default:top',
            $getDocument()
        );
        $this->assertEquals(
            [],
            $_GET
        );
        $this->assertEquals(
            [],
            $_REQUEST
        );

        $this->assertEquals(
            'default',
            $url->getType()
        );
        $this->assertEquals(
            'query_string',
            $url->getMode()
        );

        $url->setType('static');
        $this->assertEquals(
            'static',
            $url->getType()
        );

        $url->setMode('path_info');
        $this->assertEquals(
            'path_info',
            $url->getMode()
        );
    }
}
