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
class InsertStatement extends atoum
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
        $sql = new \Dotclear\Database\Statement\InsertStatement($con, $syntax);

        $sql
            ->into('MyTable')
            ->fields(['prenom', 'nom', 'ville', 'age'])
            ->lines([
                ['\'Rébecca\', \'Armand\', \'Saint-Didier-des-Bois\', 24'],
                ['\'Aimée\', \'Hebert\', \'Marigny-le-Châtel\', 36'],
            ])
            ->lines('\'Marielle\', \'Ribeiro\', \'Maillères\', 27')
            ->line('\'Hilaire\', \'Savary\', \'Conie-Molitard\', 58')
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('INSERT INTO MyTable (prenom, nom, ville, age) VALUES (\'Rébecca\', \'Armand\', \'Saint-Didier-des-Bois\', 24), (\'Aimée\', \'Hebert\', \'Marigny-le-Châtel\', 36), (\'Marielle\', \'Ribeiro\', \'Maillères\', 27), (\'Hilaire\', \'Savary\', \'Conie-Molitard\', 58)')
        ;

        $sql
            ->line('\'Hilaire\', \'Savary\', \'Conie-Molitard\', 58', true)
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('INSERT INTO MyTable (prenom, nom, ville, age) VALUES (\'Hilaire\', \'Savary\', \'Conie-Molitard\', 58)')
        ;

        $sql
            ->values([
                '\'Marielle\', \'Ribeiro\', \'Maillères\', 27',
                ['Hilaire', 'Savary', 'Conie-Molitard', 58],
            ], true)
        ;

        $this
            ->string($sql->statement())
            ->isEqualTo('INSERT INTO MyTable (prenom, nom, ville, age) VALUES (\'Marielle\', \'Ribeiro\', \'Maillères\', 27), (\'Hilaire\', \'Savary\', \'Conie-Molitard\', 58)')
        ;
    }

    public function testNoFrom()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\InsertStatement($con, $syntax);

        $this
            ->when(
                function () use ($sql) {
                    $sql->statement();
                }
            )
            ->error('SQL INSERT requires an INTO source')->exists()
        ;
    }

    public function testRun()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\InsertStatement($con, $syntax);

        $sql
            ->from('MyTable')
        ;

        $this
            ->boolean($sql->run())
            ->isTrue()
        ;
    }
}
