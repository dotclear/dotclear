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
class JoinStatement extends atoum
{
    private function getConnection($driver, $syntax)
    {
        $controller              = new controller();
        $controller->__construct = function () {};

        $class_name = sprintf('\mock\%sConnection', $driver);
        $con        = new $class_name($driver, $controller);

        $this->calling($con)->driver = $driver;
        $this->calling($con)->syntax = $syntax;

        return $con;
    }

    public function test()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\JoinStatement($con, $syntax);

        $sql
            ->type('INNER')
            ->from('T')
            ->where($sql->isNull('F'))
            ->cond('OR G = 42')
            ->sql('AND H = 0')
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('INNER JOIN T ON F IS NULL OR G = 42 AND H = 0')
        ;

        $sql
            ->left()
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('LEFT JOIN T ON F IS NULL OR G = 42 AND H = 0')
        ;

        $sql
            ->right()
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('RIGHT JOIN T ON F IS NULL OR G = 42 AND H = 0')
        ;

        $sql
            ->inner()
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('INNER JOIN T ON F IS NULL OR G = 42 AND H = 0')
        ;
    }

    public function testNoFrom()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\JoinStatement($con, $syntax);

        $sql
            ->type('INNER')
            ->where($sql->isNull('F'))
        ;

        $this
            ->when(
                function () use ($sql) {
                    $sql->statement();
                }
            )
            ->error('SQL JOIN requires a FROM source')->exists()
        ;
    }
}
