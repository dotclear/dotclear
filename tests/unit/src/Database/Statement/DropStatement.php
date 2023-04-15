<?php
/**
 * Unit tests
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

// This statement may broke class mocking system:
// declare(strict_types=1);

namespace tests\unit\Dotclear\Database\Statement;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'bootstrap.php']);

use atoum;
use atoum\atoum\mock\controller;

/*
 * @tags SqlStatement
 */
class DropStatement extends atoum
{
    private function getConnection($driver, $syntax)
    {
        $controller              = new controller();
        $controller->__construct = function () {};

        $class_name = sprintf('\mock\%sConnection', $driver);
        $con        = new $class_name($driver, $controller);

        $this->calling($con)->driver = $driver;
        $this->calling($con)->syntax = $syntax;
        $this->calling($con)
            ->methods(
                function ($method) {
                    if (in_array($method, ['run', 'drop'])) {
                        return true;
                    }
                }
            )
        ;

        return $con;
    }

    public function test()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\DropStatement($con, $syntax);

        $sql
            ->from('MyTable')
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('DROP TABLE MyTable')
        ;
    }

    public function testNoFrom()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\DropStatement($con, $syntax);

        $this
            ->when(
                function () use ($sql) {
                    $sql->statement();
                }
            )
            ->error('SQL DROP TABLE requires a FROM source')->exists()
        ;
    }

    public function testRun()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\DropStatement($con, $syntax);

        $sql
            ->from('MyTable')
        ;

        $this
            ->boolean($sql->run())
            ->isTrue()
        ;
    }
}
