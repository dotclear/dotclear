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

namespace tests\unit\Dotclear\Database;

use atoum;
use atoum\atoum\mock\controller;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'bootstrap.php']);

/*
 * @tags StaticRecordDB
 */
class StaticRecord extends atoum
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
                function ($method) use ($con) {
                    if (in_array($method, ['link'])) {
                        switch ($method) {
                            case 'link':
                                return $con;

                                break;
                        }

                        return true;
                    }
                }
            )
        ;

        return $con;
    }

    public function test($driver, $syntax)
    {
        $con  = $this->getConnection($driver, $syntax);
        $info = [
            'con'  => $con,
            'cols' => 3,
            'rows' => 2,
            'info' => [
                'name' => [
                    'Name',
                    'Town',
                    'Age',
                ],
                'type' => [
                    'string',
                    'string',
                    'int',
                ],
            ],
        ];

        // Sample data
        $rows = [
            [
                'Name' => 'Dotclear',
                'Town' => 'Paris',
                'Age'  => 42,
            ],
            [
                'Name' => 'Wordpress',
                'Town' => 'Chicago',
                'Age'  => 13,
            ],
        ];

        // Mock db_result_seek and db_fetch_assoc
        $valid   = true;
        $pointer = 0;

        $this->calling($con)->db_result_seek = function ($res, $row) use (&$valid, &$pointer) {
            $valid   = ($row >= 0 && $row < 2);
            $pointer = $valid ? $row : 0;

            return $valid;
        };
        $this->calling($con)->db_fetch_assoc = function ($res) use (&$valid, &$pointer, $rows) {
            $ret = $valid ? $rows[$pointer] : false;

            $pointer++;

            $valid   = ($pointer >= 0 && $pointer < 2);
            $pointer = $valid ? $pointer : 0;

            return $ret;
        };

        $record = new \Dotclear\Database\StaticRecord(null, $info);

        $this
            // Info
            ->integer($record->count())
            ->isEqualTo(2)
            ->array($record->columns())
            ->isEqualTo([
                'Name',
                'Town',
                'Age',
            ])
            ->boolean($record->isEmpty())
            ->isFalse()
            ->string($record->Name)
            ->isEqualTo('Dotclear')
            ->boolean($record->exists('Country'))
            ->isFalse()
            ->integer($record->index())
            ->isEqualTo(0)
            ->boolean($record->index(99))
            ->isFalse()
            ->boolean($record->index(1))
            ->isTrue()
            ->array($record->rows())
            ->isEqualTo([
                [
                    'Name' => 'Dotclear',
                    'Town' => 'Paris',
                    'Age'  => 42,
                    'Dotclear',
                    'Paris',
                    42,
                ],
                [
                    'Name' => 'Wordpress',
                    'Town' => 'Chicago',
                    'Age'  => 13,
                    'Wordpress',
                    'Chicago',
                    13,
                ],
            ])
            ->integer($record->Age)
            ->isEqualTo(13)
            ->given($record->set('Age', 14))
            ->integer($record->Age)
            ->isEqualTo(14)
            ->given($record->sort('Age', 'asc'))
            ->and($record->index(0))
            ->string($record->Name)
            ->isEqualTo('Wordpress')
            ->given($record->sort('Age', 'desc'))
            ->and($record->index(0))
            ->string($record->Name)
            ->isEqualTo('Dotclear')
            ->given($record->sort('Name', 'asc'))
            ->and($record->index(0))
            ->string($record->Name)
            ->isEqualTo('Dotclear')
            ->given($record->lexicalSort('Name', 'asc'))
            ->and($record->index(0))
            ->string($record->Name)
            ->isEqualTo('Dotclear')
            ->given($record->lexicalSort('Name', 'desc'))
            ->and($record->index(0))
            ->string($record->Name)
            ->isEqualTo('Wordpress')
            ->given($record->lexicalSort('Age', 'asc'))
            ->and($record->index(0))
            ->string($record->Name)
            ->isEqualTo('Wordpress')
        ;

        // From array

        $record = \Dotclear\Database\StaticRecord::newFromArray($rows);

        $this
            ->integer($record->count())
            ->isEqualTo(2)
            ->boolean($record->isEmpty())
            ->isFalse()
            ->string($record->Name)
            ->isEqualTo('Dotclear')
            ->boolean($record->exists('Country'))
            ->isFalse()
            ->integer($record->index())
            ->array($record->rows())
            ->isEqualTo([
                [
                    'Name' => 'Dotclear',
                    'Town' => 'Paris',
                    'Age'  => 42,
                ],
                [
                    'Name' => 'Wordpress',
                    'Town' => 'Chicago',
                    'Age'  => 13,
                ],
            ])
        ;

        // From null

        $record = \Dotclear\Database\StaticRecord::newFromArray(null);

        $this
            ->integer($record->count())
            ->isEqualTo(0)
            ->boolean($record->isEmpty())
            ->isTrue()
            ->variable($record->Name)
            ->isNull()
            ->boolean($record->exists('Country'))
            ->isFalse()
            ->integer($record->index())
            ->array($record->rows())
            ->isEqualTo([
            ])
        ;
    }

    protected function testDataProvider()
    {
        return [
            // driver, syntax
            ['mysqli', 'mysql'],
            ['mysqlimb4', 'mysql'],
            ['pgsql', 'postgresql'],
            ['sqlite', 'sqlite'],
        ];
    }
}
