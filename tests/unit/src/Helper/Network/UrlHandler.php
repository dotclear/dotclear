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

namespace tests\unit\Dotclear\Helper\Network;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'bootstrap.php']);

use atoum;
use Exception;

/*
 * @tags UrlHandler
 */
class UrlHandler extends atoum
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

        throw new Exception('Error Processing Request', 1);
    }

    // Error handler, catch exception and output full error message
    public static function catchHandler(?string $args, string $type, Exception $e): bool
    {
        echo 'urlHandler:' . 'catch' . ':' . $type . ':' . ($args ?? '') . ':' . $e->getMessage() . ':' . $e->getCode();

        return true;
    }

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

        $this
            ->exception(function () use ($url) {
                $url->callDefaultHandler();
            })
            ->hasMessage('Undefined default URL handler')
            ->exception(function () use ($url) {
                $url->callHandler('default');
            })
            ->hasMessage('Unknown URL type')
            ->exception(function () use ($url) {
                $url->callHandler('my');
            })
            ->hasMessage('Unknown URL type')
        ;

        $this
            ->given($url->registerDefault('notCallable'))
            ->exception(function () use ($url) {
                $url->callDefaultHandler();
            })
            ->hasMessage('Unable to call function')
        ;

        $this
            ->given($url->registerDefault([$this::class, 'defaultHandler']))
            ->string($defaultHandler())
            ->isEqualTo('urlHandler:default:')
        ;

        $this
            ->given($url->register('my', '', '.*', [$this::class, 'myHandler']))
            ->string($typeHandler('my'))
            ->isEqualTo('urlHandler:my:')
            ->given($url->register('alt', '', '.*', [$this::class, 'altHandler']))
            ->string($typeHandler('alt'))
            ->isEqualTo('urlHandler:alt:')
            ->array(array_keys($url->getTypes()))
            ->isEqualTo([
                'my',
                'alt',
            ])
        ;

        $this
            ->given($url->register('exception', '', '.*', [$this::class, 'exceptionHandler']))
            ->exception(function () use ($typeHandler) {
                $typeHandler('exception');
            })
            ->hasMessage('Error Processing Request')
            ->then(ob_end_clean())  // Needed as an exception occured as output buffer is still captured
        ;

        $this
            ->given($url->registerError([$this::class, 'catchHandler']))
            ->string($typeHandler('exception'))
            ->isEqualTo('urlHandler:exception:urlHandler:catch:exception::Error Processing Request:1')
        ;

        $this
            ->given($url->unregister('my'))
            ->exception(function () use ($url) {
                $url->callHandler('my');
            })
            ->hasMessage('Unknown URL type')
        ;
    }

    public function testGetBase()
    {
        $url = new \Dotclear\Helper\Network\UrlHandler();
        $url->registerDefault([$this::class, 'defaultHandler']);
        $url->register('lang', '', 'lang', [$this::class, 'myHandler']);
        $url->register('post', 'post', '^post/(.+)$', [$this::class, 'altHandler']);
        $url->registerError([$this::class, 'catchHandler']);

        $this
            ->string($url->getBase('lang'))
            ->isEqualTo('')
            ->string($url->getBase('post'))
            ->isEqualTo('post')
            ->variable($url->getBase('top'))
            ->isEqualTo('')
            ->variable($url->getBase('default'))
            ->isEqualTo('')
        ;
    }

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

        $this
            ->variable($type)
            ->isNull()
            ->variable($args)
            ->isNull()
        ;

        $_SERVER['QUERY_STRING'] = 'lang';
        $_SERVER['REQUEST_URI']  = 'https://example.com/index.php?lang';
        $_GET['lang']            = '';
        $_REQUEST['lang']        = '';

        $_SERVER['URL_REQUEST_PART'] = 'lang';

        $type = '';
        $args = '';

        $url->getArgs($_SERVER['URL_REQUEST_PART'], $type, $args);

        $this
            ->string($type)
            ->isEqualTo('lang')
            ->variable($args)
            ->isNull()
        ;

        $_SERVER['QUERY_STRING'] = 'top=2';
        $_SERVER['REQUEST_URI']  = 'https://example.com/index.php?top=2';
        $_GET['top']             = '2';
        $_REQUEST['top']         = '2';

        $_SERVER['URL_REQUEST_PART'] = 'top=2';

        $type = '';
        $args = '';

        $url->getArgs($_SERVER['URL_REQUEST_PART'], $type, $args);

        $this
            ->string($type)
            ->isEqualTo('')
            ->string($args)
            ->isEqualTo('top=2')
        ;
    }

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

        $this
            ->string($type)
            ->isEqualTo('post')
            ->string($args)
            ->isEqualTo('2023/03/26/mypost&pub=1')
        ;
    }

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

        $this
            ->string($type)
            ->isEqualTo('post')
            ->string($args)
            ->isEqualTo('2023/03/26/mypost&pub=1')
        ;
    }

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

        $this
            ->string($getDocument())
            ->isEqualTo('urlHandler:alt:2023/03/26/mypost')
            ->array($_GET)
            ->isEqualTo([
                'pub' => '1',
            ])
            ->array($_REQUEST)
            ->isEqualTo([
                'pub' => '1',
            ])
        ;
    }

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

        $this
            ->string($getDocument())
            ->isEqualTo('urlHandler:alt:2023/03/26/mypost')
            ->array($_GET)
            ->isEqualTo([
                'pub' => '1',
            ])
            ->array($_REQUEST)
            ->isEqualTo([
                'pub' => '1',
            ])
        ;
    }

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

        $this
            ->string($getDocument())
            ->isEqualTo('urlHandler:default:top')
            ->array($_GET)
            ->isEqualTo([])
            ->array($_REQUEST)
            ->isEqualTo([])
        ;
    }
}
