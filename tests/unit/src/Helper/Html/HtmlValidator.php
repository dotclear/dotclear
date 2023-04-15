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

namespace tests\unit\Dotclear\Helper\Html;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'bootstrap.php']);

use atoum;

/**
 * @tags HtmlValidator
 */
class HtmlValidator extends atoum
{
    public function testNetworkError()
    {
        $mockValidator = new \mock\Dotclear\Helper\Html\HtmlValidator();

        // Always return service unavailable HTTP status code
        $this->calling($mockValidator)->getStatus = 500;

        $doc = $mockValidator->getDocument('<p>Hello</p>');

        $this
            ->exception(function () use ($mockValidator, $doc) {
                $mockValidator->perform($doc);
            })
            ->hasMessage('Status code line invalid.');
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

        $this
            ->string($validator->getDocument($str))
            ->isIdenticalTo($doc);
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

        $this
            ->string($validator->getErrors())
            ->isEqualTo('');

        $this
            ->variable($validator->perform($validator->getDocument($str)))
            ->isEqualTo(false);

        $this
            ->string($validator->getErrors())
            ->isIdenticalTo($err);
    }

    public function testValidate()
    {
        $this
            ->variable(\Dotclear\Helper\Html\HtmlValidator::validate('<p>Hello</p>'))
            ->isEqualTo(true);

        $this
            ->array(\Dotclear\Helper\Html\HtmlValidator::validate('<p>Hello</b>'))
            ->hasSize(2)
            ->boolean['valid']->isEqualTo(false);
    }
}
