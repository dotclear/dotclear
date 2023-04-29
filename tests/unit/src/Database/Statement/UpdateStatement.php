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
class UpdateStatement extends atoum
{
    private function getConnection($driver, $syntax)
    {
        $controller              = new controller();
        $controller->__construct = function () {};

        $class_name = sprintf('\\mock\\Dotclear\\Database\\Driver\\%s\\Handler', ucfirst($driver));
        $con        = new $class_name($controller, $driver);

        $this->calling($con)->driver    = $driver;
        $this->calling($con)->syntax    = $syntax;
        $this->calling($con)->escapeStr = fn ($str) => addslashes((string) $str);
        $this->calling($con)
            ->methods(
                function ($method) {
                    if (in_array($method, ['run', 'insert'])) {
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
        $sql = new \Dotclear\Database\Statement\UpdateStatement($con, $syntax);

        $sql
            ->ref('client')
            ->reference('client')
            ->fields(['prenom', 'nom', 'ville', 'age'])
            ->values(['Rébecca', 'Armand', 'Saint-Didier-des-Bois', 24])
            ->where('id = 2')
            ->cond('AND ' . $sql->isNull('super'))
            ->sql('OR (group = 0)')
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('UPDATE client SET prenom = \'Rébecca\', nom = \'Armand\', ville = \'Saint-Didier-des-Bois\', age = 24 WHERE id = 2 AND super IS NULL OR (group = 0)')
        ;

        $this
            ->string($sql->whereStatement())
            ->isEqualTo('WHERE id = 2 AND super IS NULL OR (group = 0)')
        ;

        $sql
            ->values('Irma', true)
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('UPDATE client SET prenom = \'Irma\' WHERE id = 2 AND super IS NULL OR (group = 0)')
        ;

        $sql
            ->sets('age = 13')
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('UPDATE client SET prenom = \'Irma\', age = 13 WHERE id = 2 AND super IS NULL OR (group = 0)')
        ;
    }

    public function testNoFields()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\UpdateStatement($con, $syntax);

        $sql
            ->ref('client')
            ->sets('age = 13')
            ->cond('AND ' . $sql->isNull('super'))
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('UPDATE client SET age = 13 WHERE TRUE AND super IS NULL')
        ;
    }

    public function testNoWhere()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\UpdateStatement($con, $syntax);

        $sql
            ->ref('client')
            ->reference('client')
            ->fields(['prenom', 'nom', 'ville', 'age'])
            ->values(['Rébecca', 'Armand', 'Saint-Didier-des-Bois', 24])
            ->cond('AND ' . $sql->isNull('super'))
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('UPDATE client SET prenom = \'Rébecca\', nom = \'Armand\', ville = \'Saint-Didier-des-Bois\', age = 24 WHERE TRUE AND super IS NULL')
        ;

        $this
            ->string($sql->whereStatement())
            ->isEqualTo('WHERE TRUE AND super IS NULL')
        ;
    }

    public function testNoFrom()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\UpdateStatement($con, $syntax);

        $this
            ->when(
                function () use ($sql) {
                    $sql->statement();
                }
            )
            ->error('SQL UPDATE requires a FROM source')->exists()
        ;
    }

    public function testRun()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\UpdateStatement($con, $syntax);

        $sql
            ->from('MyTable')
        ;

        $this
            ->boolean($sql->run())
            ->isTrue()
        ;
    }
}
