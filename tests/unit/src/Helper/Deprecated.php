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

// Test helper

class MyLogger extends \Dotclear\Helper\Deprecated
{
    public static function log(string $title, array $lines): void
    {
        parent::log('MyLogger:' . $title, $line ?? []);
    }
}

/**
 * @tags Deprecated
 */
class Deprecated extends atoum
{
    public function test()
    {
        $this
            ->when(function () {\Dotclear\Helper\Deprecated::set('MyReplacement', '2.6', '2.97');})
            ->error()
                ->withPattern('/^(.*)is deprecated since version 2\.6 and wil be removed in version 2\.97, use MyReplacement as replacement\.(.*)$/')
                ->exists()
        ;
    }

    public function testChild()
    {
        \Dotclear\Helper\Deprecated::setLogger(MyLogger::class);

        $this
            ->when(function () {MyLogger::set('MyReplacement', '2.7', '2.17');})
            ->error()
                ->withPattern('/^(.*)MyLogger:(.*)$/')
                ->exists()
        ;

        $this
            ->when(function () {MyLogger::set('MyReplacement', '2.7', '2.17');})
            ->error()
                ->withPattern('/^(.*)is deprecated since version 2\.7 and wil be removed in version 2\.17, use MyReplacement as replacement\.(.*)$/')
                ->exists()
        ;
    }
}
