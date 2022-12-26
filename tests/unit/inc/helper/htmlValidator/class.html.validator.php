<?php

# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

namespace tests\unit;

require_once __DIR__ . '/../../../bootstrap.php';

require_once CLEARBRICKS_PATH . '/net/class.net.socket.php';
require_once CLEARBRICKS_PATH . '/net.http/class.net.http.php';
require_once CLEARBRICKS_PATH . '/html.validator/class.html.validator.php';

use atoum;

/**
 * html.validator test.
 */
class htmlValidator extends atoum
{
    public function testNetworkError()
    {
        $mockValidator = new \mock\htmlValidator();
        // Always return service unavailable HTTP status code
        $this->calling($mockValidator)->getStatus = 500;

        $doc = $mockValidator->getDocument('<p>Hello</p>');

        $this
            ->exception(function () use ($mockValidator, $doc) {
                $result = $mockValidator->perform($doc);
            })
            ->hasMessage('Status code line invalid.');
    }

    public function testGetDocument()
    {
        $validator = new \htmlValidator();
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
        $validator = new \htmlValidator();
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
            ->variable(\htmlValidator::validate('<p>Hello</p>'))
            ->isEqualTo(true);

        $this
            ->array(\htmlValidator::validate('<p>Hello</b>'))
            ->hasSize(2)
            ->boolean['valid']->isEqualTo(false);
    }
}
