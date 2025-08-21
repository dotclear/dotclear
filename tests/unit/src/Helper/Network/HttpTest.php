<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Network;

use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\TestCase;

class HttpTest extends TestCase
{
    private string $testDirectory;

    protected function setUp(): void
    {
        $this->testDirectory = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'fixtures', 'src', 'Helper', 'File']));
    }

    #[BackupGlobals(true)]
    public function testGetHost()
    {
        /* Test getHost
         * In CLI mode superglobal variable $_SERVER is not set correctly
         */

        // Normal
        $_SERVER['HTTP_HOST']   = 'localhost';
        $_SERVER['SERVER_PORT'] = 80;

        $this->assertEquals(
            'http://localhost',
            \Dotclear\Helper\Network\Http::getHost()
        );

        // On a different port
        $_SERVER['SERVER_PORT'] = 8080;

        $this->assertEquals(
            'http://localhost:8080',
            \Dotclear\Helper\Network\Http::getHost()
        );

        // On secure port without enforcing TLS
        $_SERVER['SERVER_PORT'] = 443;

        $this->assertEquals(
            'https://localhost',
            \Dotclear\Helper\Network\Http::getHost()
        );

        // On secure via $_SERVER
        $_SERVER['HTTPS'] = 'on';

        $this->assertEquals(
            'https://localhost',
            \Dotclear\Helper\Network\Http::getHost()
        );

        // On sercure port with enforcing TLS
        $_SERVER['SERVER_PORT']                             = 443;
        \Dotclear\Helper\Network\Http::$https_scheme_on_443 = true;

        $this->assertEquals(
            'https://localhost',
            \Dotclear\Helper\Network\Http::getHost()
        );
    }

    public function testGetHostFromURL()
    {
        $this->assertEquals(
            'https://www.dotclear.org',
            \Dotclear\Helper\Network\Http::getHostFromURL('https://www.dotclear.org/is-good-for-you/')
        );

        // Note: An empty string might be confuse
        $this->assertEquals(
            '',
            \Dotclear\Helper\Network\Http::getHostFromURL('http:/www.dotclear.org/is-good-for-you/')
        );
    }

    #[BackupGlobals(true)]
    public function testGetSelfURI()
    {
        $_SERVER['HTTP_HOST']   = 'localhost';
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['REQUEST_URI'] = '/test.html';

        $this->assertEquals(
            'http://localhost/test.html',
            \Dotclear\Helper\Network\Http::getSelfURI()
        );

        // It's usually unlikly, but unlikly is not impossible.
        $_SERVER['REQUEST_URI'] = 'test.html';

        $this->assertEquals(
            'http://localhost/test.html',
            \Dotclear\Helper\Network\Http::getSelfURI()
        );

        // Check with HTTPS
        $_SERVER['HTTPS']       = 'on';
        $_SERVER['HTTP_HOST']   = 'localhost';
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['REQUEST_URI'] = '/test.html';

        $this->assertEquals(
            'https://localhost/test.html',
            \Dotclear\Helper\Network\Http::getSelfURI()
        );

        // Check with HTTPS and port 443
        $_SERVER['HTTPS']       = 'on';
        $_SERVER['HTTP_HOST']   = 'localhost';
        $_SERVER['SERVER_PORT'] = 443;
        $_SERVER['REQUEST_URI'] = '/test.html';

        $this->assertEquals(
            'https://localhost/test.html',
            \Dotclear\Helper\Network\Http::getSelfURI()
        );

        // Force scheme
        \Dotclear\Helper\Network\Http::$https_scheme_on_443 = true;

        $_SERVER['HTTP_HOST']   = 'localhost';
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['REQUEST_URI'] = '/test.html';

        $this->assertEquals(
            'https://localhost/test.html',
            \Dotclear\Helper\Network\Http::getSelfURI()
        );

        $_SERVER['HTTP_HOST']   = 'localhost';
        $_SERVER['SERVER_PORT'] = 443;
        $_SERVER['REQUEST_URI'] = '/test.html';

        $this->assertEquals(
            'https://localhost/test.html',
            \Dotclear\Helper\Network\Http::getSelfURI()
        );
    }

    #[BackupGlobals(true)]
    public function testPrepareRedirect()
    {
        $_SERVER['HTTP_HOST']   = 'localhost';
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['REQUEST_URI'] = '/test.html';

        $prepareRedirect = new \ReflectionMethod(\Dotclear\Helper\Network\Http::class, 'prepareRedirect');
        $prepareRedirect->setAccessible(true);

        $this->assertEquals(
            'http://www.dotclear.org/auth.html',
            $prepareRedirect->invokeArgs(null, ['http://www.dotclear.org/auth.html'])
        );
        $this->assertEquals(
            'https://www.dotclear.org/auth.html',
            $prepareRedirect->invokeArgs(null, ['https://www.dotclear.org/auth.html'])
        );

        $_SERVER['PHP_SELF'] = 'bin';
        $this->assertEquals(
            'http://localhost/auth.html',
            $prepareRedirect->invokeArgs(null, ['auth.html'])
        );
        $this->assertEquals(
            'http://localhost/admin/auth.html',
            $prepareRedirect->invokeArgs(null, ['/admin/auth.html'])
        );

        $_SERVER['PHP_SELF'] = '/test.php';

        $this->assertEquals(
            'http://localhost/auth.html',
            $prepareRedirect->invokeArgs(null, ['auth.html'])
        );
    }

    public function testConcatURL()
    {
        $this->assertEquals(
            'http://localhost/index.html',
            \Dotclear\Helper\Network\Http::concatURL('http://localhost', 'index.html')
        );
        $this->assertEquals(
            'http://localhost/page/index.html',
            \Dotclear\Helper\Network\Http::concatURL('http://localhost', 'page/index.html')
        );
        $this->assertEquals(
            'http://localhost/page/index.html',
            \Dotclear\Helper\Network\Http::concatURL('http://localhost', '/page/index.html')
        );
        $this->assertEquals(
            'http://localhost/index.html',
            \Dotclear\Helper\Network\Http::concatURL('http://localhost/', 'index.html')
        );
        $this->assertEquals(
            'http://localhost/page/index.html',
            \Dotclear\Helper\Network\Http::concatURL('http://localhost/', 'page/index.html')
        );
        $this->assertEquals(
            'http://localhost/page/index.html',
            \Dotclear\Helper\Network\Http::concatURL('http://localhost/', '/page/index.html')
        );
        $this->assertEquals(
            'http://localhost/admin/index.html',
            \Dotclear\Helper\Network\Http::concatURL('http://localhost/admin', 'index.html')
        );
        $this->assertEquals(
            'http://localhost/admin/page/index.html',
            \Dotclear\Helper\Network\Http::concatURL('http://localhost/admin', 'page/index.html')
        );
        $this->assertEquals(
            'http://localhost/page/index.html',
            \Dotclear\Helper\Network\Http::concatURL('http://localhost/admin', '/page/index.html')
        );
        $this->assertEquals(
            'http://localhost/admin/index.html',
            \Dotclear\Helper\Network\Http::concatURL('http://localhost/admin/', 'index.html')
        );
        $this->assertEquals(
            'http://localhost/admin/page/index.html',
            \Dotclear\Helper\Network\Http::concatURL('http://localhost/admin/', 'page/index.html')
        );
        $this->assertEquals(
            'http://localhost/page/index.html',
            \Dotclear\Helper\Network\Http::concatURL('http://localhost/admin/', '/page/index.html')
        );
    }

    #[BackupGlobals(true)]
    public function testRealIP()
    {
        $this->assertNull(
            \Dotclear\Helper\Network\Http::realIP()
        );

        $_SERVER['REMOTE_ADDR'] = '192.168.0.42';

        $this->assertEquals(
            '192.168.0.42',
            \Dotclear\Helper\Network\Http::realIP()
        );
    }

    #[BackupGlobals(true)]
    public function testBrowserUID()
    {
        unset($_SERVER['HTTP_USER_AGENT'], $_SERVER['HTTP_ACCEPT_CHARSET']);

        $this->assertEquals(
            'd82ae3c43cf5af4d0a8a8bc1f691ee5cc89332fd',
            \Dotclear\Helper\Network\Http::browserUID('dotclear')
        );

        $_SERVER['HTTP_USER_AGENT'] = 'Dotclear';

        $this->assertEquals(
            'ef1c4702c3b684637a95d482e39536a943fef7a1',
            \Dotclear\Helper\Network\Http::browserUID('dotclear')
        );

        $_SERVER['HTTP_ACCEPT_CHARSET'] = 'ISO-8859-1,utf-8;q=0.7,*;q=0.3';

        $this->assertEquals(
            'ce3880093944405b1c217b4e2fba05e93ccc07e4',
            \Dotclear\Helper\Network\Http::browserUID('dotclear')
        );

        unset($_SERVER['HTTP_USER_AGENT']);

        $this->assertEquals(
            'c1bb85ca96d62726648053f97922eee5ceda78e9',
            \Dotclear\Helper\Network\Http::browserUID('dotclear')
        );
    }

    #[BackupGlobals(true)]
    public function testGetAcceptLanguage()
    {
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);

        $this->assertEquals(
            '',
            \Dotclear\Helper\Network\Http::getAcceptLanguage()
        );

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7';

        $this->assertEquals(
            'fr',
            \Dotclear\Helper\Network\Http::getAcceptLanguage()
        );
    }

    #[BackupGlobals(true)]
    public function testGetAcceptLanguages()
    {
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);

        $this->assertEmpty(
            \Dotclear\Helper\Network\Http::getAcceptLanguages()
        );

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7';

        $list = \Dotclear\Helper\Network\Http::getAcceptLanguages();

        $this->assertCount(
            4,
            $list
        );
        $this->assertEquals(
            'fr-fr',
            $list[0]
        );
        $this->assertEquals(
            'fr',
            $list[1]
        );
        $this->assertEquals(
            'en-us',
            $list[2]
        );
        $this->assertEquals(
            'en',
            $list[3]
        );
    }

    #[BackupGlobals(true)]
    public function testCache()
    {
        $this->assertNull(
            \Dotclear\Helper\Network\Http::cache([])
        );

        \Dotclear\Helper\File\Files::getDirList($this->testDirectory, $arr);
        $fl = [];
        foreach ($arr['files'] as $file) {
            if ($file != '.' && $file != '..') {
                $fl[] = $file;
            }
        }
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = 'Tue, 27 Feb 2004 10:17:09 GMT';

        $this->assertNull(
            \Dotclear\Helper\Network\Http::cache($fl)
        );
    }

    #[BackupGlobals(true)]
    public function testEtag()
    {
        $_SERVER['HTTP_IF_NONE_MATCH'] = 'W/"67ab43", "54ed21", "7892dd"';

        $this->assertNull(
            \Dotclear\Helper\Network\Http::etag()
        );
        $this->assertNull(
            \Dotclear\Helper\Network\Http::etag('bfc13a64729c4290ef5b2c2730249c88ca92d82d')
        );
    }

    public function testHead()
    {
        $this->assertNull(
            \Dotclear\Helper\Network\Http::head(200)
        );
        $this->assertNull(
            \Dotclear\Helper\Network\Http::head(200, '\\o/')
        );
    }

    #[BackupGlobals(true)]
    public function testTrimRequest()
    {
        $_GET['single']      = 'single';
        $_GET['trim_single'] = ' trim_single ';
        $_GET['multiple']    = ['one ', 'two', ' three', ' four ', [' five ']];

        $_POST['post']       = ' test  ';
        $_REQUEST['request'] = ' test\\\'n\\\'test  ';
        $_COOKIE['cookie']   = ' test  ';

        \Dotclear\Helper\Network\Http::trimRequest();

        $this->assertEquals(
            'single',
            $_GET['single']
        );
        $this->assertEquals(
            'trim_single',
            $_GET['trim_single']
        );
        $this->assertEquals(
            'one',
            $_GET['multiple'][0]
        );
        $this->assertEquals(
            'two',
            $_GET['multiple'][1]
        );
        $this->assertEquals(
            'three',
            $_GET['multiple'][2]
        );
        $this->assertEquals(
            'four',
            $_GET['multiple'][3]
        );
        $this->assertEquals(
            'five',
            $_GET['multiple'][4][0]
        );

        $this->assertEquals(
            'test',
            $_POST['post']
        );

        $this->assertEquals(
            'test\\\'n\\\'test',
            $_REQUEST['request']
        );

        $this->assertEquals(
            'test',
            $_COOKIE['cookie']
        );
    }
}
