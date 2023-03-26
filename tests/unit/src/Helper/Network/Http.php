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

/*
 * @tags Http
 */
class Http extends atoum
{
    private string $testDirectory;

    public function __construct()
    {
        parent::__construct();

        $this->testDirectory = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'fixtures', 'src', 'Helper', 'File']));

        $this
            ->dump($this->testDirectory)
        ;
    }

    public function testGetHost()
    {
        /* Test getHost
         * In CLI mode superglobal variable $_SERVER is not set correctly
         */

        // Normal
        $_SERVER['HTTP_HOST']   = 'localhost';
        $_SERVER['SERVER_PORT'] = 80;
        $this
            ->string(\Dotclear\Helper\Network\Http::getHost())
            ->isEqualTo('http://localhost');

        // On a different port
        $_SERVER['SERVER_PORT'] = 8080;
        $this
            ->string(\Dotclear\Helper\Network\Http::getHost())
            ->isEqualTo('http://localhost:8080');

        // On secure port without enforcing TLS
        $_SERVER['SERVER_PORT'] = 443;
        $this
            ->string(\Dotclear\Helper\Network\Http::getHost())
            ->isEqualTo('http://localhost:443');

        // On secure via $_SERVER
        $_SERVER['HTTPS'] = 'on';
        $this
            ->string(\Dotclear\Helper\Network\Http::getHost())
            ->isEqualTo('https://localhost');

        // On sercure port with enforcing TLS
        $_SERVER['SERVER_PORT']                             = 443;
        \Dotclear\Helper\Network\Http::$https_scheme_on_443 = true;
        $this
            ->string(\Dotclear\Helper\Network\Http::getHost())
            ->isEqualTo('https://localhost');
    }

    public function testGetHostFromURL()
    {
        $this
            ->string(\Dotclear\Helper\Network\Http::getHostFromURL('https://www.dotclear.org/is-good-for-you/'))
            ->isEqualTo('https://www.dotclear.org');

        // Note: An empty string might be confuse
        $this
            ->string(\Dotclear\Helper\Network\Http::getHostFromURL('http:/www.dotclear.org/is-good-for-you/'))
            ->isEqualTo('');
    }

    public function testGetSelfURI()
    {
        $_SERVER['HTTP_HOST']   = 'localhost';
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['REQUEST_URI'] = '/test.html';
        $this
            ->string(\Dotclear\Helper\Network\Http::getSelfURI())
            ->isEqualTo('http://localhost/test.html');

        // It's usually unlikly, but unlikly is not impossible.
        $_SERVER['REQUEST_URI'] = 'test.html';
        $this
            ->string(\Dotclear\Helper\Network\Http::getSelfURI())
            ->isEqualTo('http://localhost/test.html');
    }

    public function testPrepareRedirect()
    {
        $_SERVER['HTTP_HOST']   = 'localhost';
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['REQUEST_URI'] = '/test.html';

        $prepareRedirect = new \ReflectionMethod('\Dotclear\Helper\Network\Http', 'prepareRedirect');
        $prepareRedirect->setAccessible(true);
        $this
            ->string($prepareRedirect->invokeArgs(null, ['http://www.dotclear.org/auth.html']))
            ->isEqualTo('http://www.dotclear.org/auth.html');

        $this
            ->string($prepareRedirect->invokeArgs(null, ['https://www.dotclear.org/auth.html']))
            ->isEqualTo('https://www.dotclear.org/auth.html');

        $this
            ->string($prepareRedirect->invokeArgs(null, ['auth.html']))
            ->isEqualTo('http://localhost/auth.html');

        $this
            ->string($prepareRedirect->invokeArgs(null, ['/admin/auth.html']))
            ->isEqualTo('http://localhost/admin/auth.html');

        $_SERVER['PHP_SELF'] = '/test.php';
        $this
            ->string($prepareRedirect->invokeArgs(null, ['auth.html']))
            ->isEqualTo('http://localhost/auth.html');
    }

    public function testConcatURL()
    {
        $this
            ->string(\Dotclear\Helper\Network\Http::concatURL('http://localhost', 'index.html'))
            ->isEqualTo('http://localhost/index.html');

        $this
            ->string(\Dotclear\Helper\Network\Http::concatURL('http://localhost', 'page/index.html'))
            ->isEqualTo('http://localhost/page/index.html');

        $this
            ->string(\Dotclear\Helper\Network\Http::concatURL('http://localhost', '/page/index.html'))
            ->isEqualTo('http://localhost/page/index.html');

        $this
            ->string(\Dotclear\Helper\Network\Http::concatURL('http://localhost/', 'index.html'))
            ->isEqualTo('http://localhost/index.html');

        $this
            ->string(\Dotclear\Helper\Network\Http::concatURL('http://localhost/', 'page/index.html'))
            ->isEqualTo('http://localhost/page/index.html');

        $this
            ->string(\Dotclear\Helper\Network\Http::concatURL('http://localhost/', '/page/index.html'))
            ->isEqualTo('http://localhost/page/index.html');

        $this
            ->string(\Dotclear\Helper\Network\Http::concatURL('http://localhost/admin', 'index.html'))
            ->isEqualTo('http://localhost/admin/index.html');

        $this
            ->string(\Dotclear\Helper\Network\Http::concatURL('http://localhost/admin', 'page/index.html'))
            ->isEqualTo('http://localhost/admin/page/index.html');

        $this
            ->string(\Dotclear\Helper\Network\Http::concatURL('http://localhost/admin', '/page/index.html'))
            ->isEqualTo('http://localhost/page/index.html');

        $this
            ->string(\Dotclear\Helper\Network\Http::concatURL('http://localhost/admin/', 'index.html'))
            ->isEqualTo('http://localhost/admin/index.html');

        $this
            ->string(\Dotclear\Helper\Network\Http::concatURL('http://localhost/admin/', 'page/index.html'))
            ->isEqualTo('http://localhost/admin/page/index.html');

        $this
            ->string(\Dotclear\Helper\Network\Http::concatURL('http://localhost/admin/', '/page/index.html'))
            ->isEqualTo('http://localhost/page/index.html');
    }

    public function testRealIP()
    {
        $this
            ->variable(\Dotclear\Helper\Network\Http::realIP())
            ->isNull();

        $_SERVER['REMOTE_ADDR'] = '192.168.0.42';
        $this
            ->string(\Dotclear\Helper\Network\Http::realIP())
            ->isEqualTo('192.168.0.42');
    }

    public function testBrowserUID()
    {
        unset($_SERVER['HTTP_USER_AGENT'], $_SERVER['HTTP_ACCEPT_CHARSET']);

        $this
            ->string(\Dotclear\Helper\Network\Http::browserUID('dotclear'))
            ->isEqualTo('d82ae3c43cf5af4d0a8a8bc1f691ee5cc89332fd');

        $_SERVER['HTTP_USER_AGENT'] = 'Dotclear';
        $this
            ->string(\Dotclear\Helper\Network\Http::browserUID('dotclear'))
            ->isEqualTo('ef1c4702c3b684637a95d482e39536a943fef7a1');

        $_SERVER['HTTP_ACCEPT_CHARSET'] = 'ISO-8859-1,utf-8;q=0.7,*;q=0.3';
        $this
            ->string(\Dotclear\Helper\Network\Http::browserUID('dotclear'))
            ->isEqualTo('ce3880093944405b1c217b4e2fba05e93ccc07e4');

        unset($_SERVER['HTTP_USER_AGENT']);
        $this
            ->string(\Dotclear\Helper\Network\Http::browserUID('dotclear'))
            ->isEqualTo('c1bb85ca96d62726648053f97922eee5ceda78e9');
    }

    public function testGetAcceptLanguage()
    {
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $this
            ->string(\Dotclear\Helper\Network\Http::getAcceptLanguage())
            ->isEqualTo('');

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7';
        $this
            ->string(\Dotclear\Helper\Network\Http::getAcceptLanguage())
            ->isEqualTo('fr');
    }

    public function testGetAcceptLanguages()
    {
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $this
            ->array(\Dotclear\Helper\Network\Http::getAcceptLanguages())
            ->isEmpty();

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7';
        $this
            ->array(\Dotclear\Helper\Network\Http::getAcceptLanguages())
            ->string[0]->isEqualTo('fr-fr')
            ->string[1]->isEqualTo('fr')
            ->string[2]->isEqualTo('en-us')
            ->string[3]->isEqualTo('en');
    }

    public function testCache()
    {
        $this
            ->variable(\Dotclear\Helper\Network\Http::cache([]))
            ->isNull();

        \Dotclear\Helper\File\Files::getDirList($this->testDirectory, $arr);
        $fl = [];
        foreach ($arr['files'] as $file) {
            if ($file != '.' && $file != '..') {
                $fl[] = $file;
            }
        }
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = 'Tue, 27 Feb 2004 10:17:09 GMT';
        $this
            ->variable(\Dotclear\Helper\Network\Http::cache($fl))
            ->isNull();
    }

    public function testEtag()
    {
        $_SERVER['HTTP_IF_NONE_MATCH'] = 'W/"67ab43", "54ed21", "7892dd"';
        $this
            ->variable(\Dotclear\Helper\Network\Http::etag())
            ->isNull();

        $this
            ->variable(\Dotclear\Helper\Network\Http::etag('bfc13a64729c4290ef5b2c2730249c88ca92d82d'))
            ->isNull();
    }

    public function testHead()
    {
        $this
            ->variable(\Dotclear\Helper\Network\Http::head(200))
            ->isNull();

        $this
            ->variable(\Dotclear\Helper\Network\Http::head(200, '\\o/'))
            ->isNull();
    }

    public function testTrimRequest()
    {
        $_GET['single']      = 'single';
        $_GET['trim_single'] = ' trim_single ';
        $_GET['multiple']    = ['one ', 'two', ' three', ' four ', [' five ']];

        $_POST['post']       = ' test  ';
        $_REQUEST['request'] = ' test\\\'n\\\'test  ';
        $_COOKIE['cookie']   = ' test  ';

        \Dotclear\Helper\Network\Http::trimRequest();
        $this
            ->array($_GET)
            ->string['single']->isEqualTo('single')
            ->string['trim_single']->isEqualTo('trim_single')
            ->array['multiple']
            ->string[0]->isEqualTo('one')
            ->array['multiple']
            ->string[1]->isEqualTo('two')
            ->array['multiple']
            ->string[2]->isEqualTo('three')
            ->array['multiple']
            ->string[3]->isEqualTo('four')
            ->array['multiple']
            ->array[4]
            ->string[0]->isEqualTo('five')
            ->array($_POST)
            ->string['post']->isEqualTo('test')
            ->array($_REQUEST)
            ->string['request']->isEqualTo('test\\\'n\\\'test')
            ->array($_COOKIE)
            ->string['cookie']->isEqualTo('test');
    }
}
