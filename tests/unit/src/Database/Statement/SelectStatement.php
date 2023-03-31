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
class SelectStatement extends atoum
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
                    if (in_array($method, ['run', 'select'])) {
                        return true;
                    }
                }
            )
        ;

        $this->mockGenerator->generate('\dcRecord', null, 'dcRecord');
        $rc                              = new \mock\dcRecord(null);
        $this->calling($rc)->__construct = function ($record) {};

        return $con;
    }

    // Generic (including tests of some SqlStatement methods too)

    public function test()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);
        $sql
            ->from('MyTable')
            ->from(['MyTableOne', 'MyTableTwo'])
            ->from('MyTableThree, MyTableFour')
            ->columns(['MyFieldOne', 'MyFieldTwo'])
            ->fields(['MyFieldThree', 'MyFieldFour'])
            ->columns('MyFieldFive')
            ->field('MyFieldSix')
            ->where('MyField = 42')
            ->where(['MyFieldOne = 17', 'MyFieldTwo <> 19'])
            ->on('MyOtherField = -1')
            ->cond('AND MyNullField IS NULL')
            ->and('MyAndField <> 0')
            ->or($sql->andGroup(['MyFieldOne = 42', 'MyFieldTwo = -1']))
            ->and($sql->orGroup(['MyFieldOne = -2', 'MyFieldTwo = 43']))
            ->group('MyField')
            ->group(['MyFieldOne', 'MyFieldTwo'])
            ->sql('AND MyAlias IS NULL')
            ->sql(['AND MyAliasTwo IS NULL', 'AND MyAliasThree IS NULL'])
            ->from('MyTableFive', false, true)
            ->from(['MyTableSix', 'MyTableSeven'], false, true)
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('SELECT MyFieldOne, MyFieldTwo, MyFieldThree, MyFieldFour, MyFieldFive, MyFieldSix FROM MyTableSix, MyTableSeven, MyTableFive, MyTable, MyTableOne, MyTableTwo, MyTableThree, MyTableFour WHERE MyField = 42 AND MyFieldOne = 17 AND MyFieldTwo <> 19 AND MyOtherField = -1 AND MyNullField IS NULL AND MyAndField <> 0 OR (MyFieldOne = 42 AND MyFieldTwo = -1) AND (MyFieldOne = -2 OR MyFieldTwo = 43) AND MyAlias IS NULL AND MyAliasTwo IS NULL AND MyAliasThree IS NULL GROUP BY MyField, MyFieldOne, MyFieldTwo')
        ;

        $this
            ->string($sql->inSelect('MyField', (new \Dotclear\Database\Statement\SelectStatement($con, $syntax))->from('MySecondTable')))
            ->isEqualTo('MyField IN (SELECT * FROM MySecondTable)')
        ;
    }

    public function testReset()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->column('F')
            ->column('G', true)
            ->columns(['H', 'I'])
            ->columns(['K', 'L'], true)
            ->from('T')
            ->from('U', true)
            ->where('C = 42')
            ->where('D = 0', true)
            ->cond('AND E = 7')
            ->cond('OR F = -1', true)
            ->sql('OR G IS NULL')
            ->sql('OR H IS NULL', true)
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('SELECT K, L FROM U WHERE D = 0 OR F = -1 OR H IS NULL')
        ;
    }

    public function testIsSame()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->columns(['K', 'L'])
            ->from('U')
            ->where('D = 0')
            ->cond('OR F = -1')
            ->sql('OR (H IS NULL AND I IS NOT NULL)')
        ;

        $this
            ->boolean($sql->isSame($sql->statement(), ' SELECT K  , L FROM U WHERE D = 0 OR F = -1 OR  ( H IS NULL AND  I IS NOT NULL )  '))
            ->isTrue()
        ;
    }

    // Specific SelectStatement tests

    public function testJoin()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->from($sql->as('TableC', 'C'))
            ->join(
                (new \Dotclear\Database\Statement\JoinStatement($con, $syntax))
                    ->from($sql->as('TableP', 'P'))
                    ->on('C.cat_id = P.cat_id')
                    ->and('P.blog_id = ' . $sql->quote('default'))
                    ->statement()
            )
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('SELECT * FROM TableC C JOIN TableP P ON C.cat_id = P.cat_id AND P.blog_id = \'default\'')
        ;

        $sql
            ->join([
                (new \Dotclear\Database\Statement\JoinStatement($con, $syntax))
                    ->from($sql->as('TableP', 'P'))
                    ->on('C.cat_id = P.cat_id')
                    ->and('P.blog_id = ' . $sql->quote('default'))
                    ->statement(),
                (new \Dotclear\Database\Statement\JoinStatement($con, $syntax))
                    ->from($sql->as('TableQ', 'Q'))
                    ->on('C.cat_id = Q.cat_id')
                    ->and('Q.blog_id = ' . $sql->quote('default'))
                    ->statement(),
            ], true)
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('SELECT * FROM TableC C JOIN TableP P ON C.cat_id = P.cat_id AND P.blog_id = \'default\' JOIN TableQ Q ON C.cat_id = Q.cat_id AND Q.blog_id = \'default\'')
        ;
    }

    public function testUnion()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->from($sql->as('TableC', 'C'))
            ->union(
                (new \Dotclear\Database\Statement\SelectStatement($con, $syntax))
                    ->from($sql->as('TableU', 'U'))
                    ->where('user_super = 1')
                    ->statement()
            )
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('SELECT * FROM TableC C UNION SELECT * FROM TableU U WHERE user_super = 1')
        ;

        $sql
            ->from($sql->as('TableC', 'C'))
            ->union([
                (new \Dotclear\Database\Statement\SelectStatement($con, $syntax))
                    ->from($sql->as('TableU', 'U'))
                    ->where('user_super = 1')
                    ->statement(),
                (new \Dotclear\Database\Statement\SelectStatement($con, $syntax))
                    ->from($sql->as('TableV', 'V'))
                    ->where('user_admin = 1')
                    ->statement(),
            ], true)
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('SELECT * FROM TableC C, TableC C UNION SELECT * FROM TableU U WHERE user_super = 1 UNION SELECT * FROM TableV V WHERE user_admin = 1')
        ;
    }

    public function testHaving()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->from('Customers')
            ->fields([
                $sql->count('CustomerID'),
                'Country',
            ])
            ->groupBy('Country')
            ->having($sql->count('CustomerID') . ' > 5')
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('SELECT COUNT(CustomerID), Country FROM Customers GROUP BY Country HAVING COUNT(CustomerID) > 5')
        ;

        $sql
            ->having([
                $sql->count('CustomerID') . ' > 17',
                $sql->max('CustomerID') . ' > 4', // I know, it's not coherent but it's only for testing purpose
            ], true)
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('SELECT COUNT(CustomerID), Country FROM Customers GROUP BY Country HAVING COUNT(CustomerID) > 17 AND MAX(CustomerID) > 4')
        ;
    }

    public function testOrder()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->columns(['blog_id', 'user_id'])
            ->from('TableB')
            ->order('blog_id ASC')
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('SELECT blog_id, user_id FROM TableB ORDER BY blog_id ASC')
        ;

        $sql
            ->order([
                'blog_id DESC',
                'user_id ASC',
            ], true)
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('SELECT blog_id, user_id FROM TableB ORDER BY blog_id DESC, user_id ASC')
        ;
    }

    public function testGroup()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->columns(['blog_id', 'user_id'])
            ->from('TableB')
            ->groupBy('blog_id')
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('SELECT blog_id, user_id FROM TableB GROUP BY blog_id')
        ;

        $sql
            ->group([
                'blog_id',
                'user_id',
            ], true)
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('SELECT blog_id, user_id FROM TableB GROUP BY blog_id, user_id')
        ;
    }

    public function testLimit()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->columns(['blog_id', 'user_id'])
            ->from('TableB')
            ->limit(1)
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('SELECT blog_id, user_id FROM TableB LIMIT 1')
        ;

        $sql
            ->limit([4])
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('SELECT blog_id, user_id FROM TableB LIMIT 4')
        ;

        $sql
            ->limit([10, 5])
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('SELECT blog_id, user_id FROM TableB LIMIT 5 OFFSET 10')
        ;
    }

    public function testOffset()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->columns(['blog_id', 'user_id'])
            ->from('TableB')
            ->offset(10)
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('SELECT blog_id, user_id FROM TableB OFFSET 10')
        ;
    }

    public function testDistinct()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->columns(['blog_id', 'user_id'])
            ->from('TableB')
            ->distinct(true)
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('SELECT DISTINCT blog_id, user_id FROM TableB')
        ;
    }

    public function testNoFrom()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->columns(['blog_id', 'user_id'])
        ;

        $this
            ->when(
                function () use ($sql) {
                    $sql->statement();
                }
            )
            ->error('SQL SELECT requires a FROM source')->exists()
        ;
    }

    public function testCondAndNoWhere()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->columns(['blog_id', 'user_id'])
            ->from('TableB')
            ->cond('AND blog_id IS NULL')
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('SELECT blog_id, user_id FROM TableB WHERE TRUE AND blog_id IS NULL')
        ;
    }

    public function testRun()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->from('MyTable')
            ->where($sql->isNull('MyField'))
        ;

        $this
            ->object($sql->run())
            ->isInstanceOf('\dcRecord')
        ;
    }
}
