<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class HtmlValidatorTest extends TestCase
{
    public function testNetworkError()
    {
        // Create a test stub for HtmlValidator
        $mockValidator = $this->createStub(\Dotclear\Helper\Html\HtmlValidator::class);

        // Configure the test stub
        $mockValidator->method('getStatus')
            ->willReturn(500);
        $mockValidator->method('perform')
            ->willThrowException(new Exception());

        $this->expectException(Exception::class);

        $doc = $mockValidator->getDocument('<p>Hello</p>');
        $mockValidator->perform($doc);

        $this->expectExceptionMessage('Status code line invalid.');
    }

    public function testGetDocument()
    {
        $validator = new \Dotclear\Helper\Html\HtmlValidator();
        $str       = <<<EODTIDY
            <p>Hello</p>
            EODTIDY;
        $doc = <<<EODTIDYV
            <!DOCTYPE html>
            <html>
            <head>
            <title>validation</title>
            </head>
            <body>
            <p>Hello</p>
            </body>
            </html>
            EODTIDYV;

        $this->assertSame(
            $doc,
            $validator->getDocument($str)
        );
    }

    public function testGetErrors()
    {
        $validator = new \Dotclear\Helper\Html\HtmlValidator();
        $str       = <<<EODTIDYE
            <p>Hello</b>
            EODTIDYE;
        $err = <<<EODTIDYF
            <ol><li class="error"><p><strong>Error</strong>: Stray end tag <code>b</code>.</p><p class="location">From line 7, column 9; to line 7, column 12</p><p class="extract"><code>&gt;↩&lt;p&gt;Hello&lt;/b&gt;↩&lt;/bod</code></p></li><li class="info warning"><p><strong>Warning</strong>: Consider adding a <code>lang</code> attribute to the <code>html</code> start tag to declare the language of this document.</p><p class="location">From line 1, column 16; to line 2, column 6</p><p class="extract"><code>TYPE html&gt;↩&lt;html&gt;↩&lt;head</code></p></li></ol>
            EODTIDYF;

        $this->assertEquals(
            '',
            $validator->getErrors()
        );

        $this->assertEquals(
            false,
            $validator->perform($validator->getDocument($str))
        );

        $this->assertEquals(
            $err,
            $validator->getErrors()
        );
    }

    public function testValidate()
    {
        $validate = \Dotclear\Helper\Html\HtmlValidator::validate('<p>Hello</p>');

        $this->assertEquals(
            true,
            $validate['valid']
        );

        $validate = \Dotclear\Helper\Html\HtmlValidator::validate('<p>Hello</b>');

        $this->assertCount(
            2,
            $validate
        );

        $this->assertEquals(
            false,
            $validate['valid']
        );
    }
}
