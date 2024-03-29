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

class RecordExtend
{
    public static function isEditable(\Dotclear\Database\Record $rs): bool
    {
        return ($rs->index() === 0);
    }
}

/*
 * @tags RecordDB
 */
class Record extends atoum
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

        $record = new \Dotclear\Database\Record($rows, $info);

        $this
            // Initial index
            ->integer($record->index())
            ->isEqualTo(0)

            // First row
            ->boolean($record->fetch())
            ->isTrue()
            ->integer($record->index())
            ->isEqualTo(0)

            // Second row
            ->boolean($record->fetch())
            ->isTrue()
            ->integer($record->index())
            ->isEqualTo(1)

            // Back to beginning
            ->given($record->next())
            ->integer($record->key())
            ->isEqualTo(0)
            ->boolean($record->valid())
            ->isFalse()

            // Rewind to start
            ->given($record->rewind())
            ->integer($record->index())
            ->isEqualTo(0)
            ->boolean($record->valid())
            ->isTrue()

            // Fields
            ->string($record->f('Name'))
            ->isEqualTo('Dotclear')
            ->boolean($record->exists('name'))
            ->isFalse()
            ->variable($record->f('name'))
            ->isNull()

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

            // Various
            ->given($record->rewind())
            ->array($record->row())
            ->isEqualTo([
                'Name' => 'Dotclear',
                'Town' => 'Paris',
                'Age'  => 42,
                'Dotclear',
                'Paris',
                42,
            ])
            ->variable($record->current())
            ->isEqualTo($record)
            ->string($record->Name)
            ->isEqualTo('Dotclear')
            ->boolean(isset($record->Age))
            ->isTrue()

            // Moves
            ->given($record->moveEnd())
            ->integer($record->index())
            ->isEqualTo(1)
            ->boolean($record->isEnd())
            ->isTrue()
            ->boolean($record->isStart())
            ->isFalse()
            ->given($record->moveNext())
            ->integer($record->index())
            ->isEqualTo(1)
            ->boolean($record->isEnd())
            ->isTrue()
            ->boolean($record->isStart())
            ->isFalse()
            ->given($record->moveStart())
            ->integer($record->index())
            ->isEqualTo(0)
            ->boolean($record->isEnd())
            ->isFalse()
            ->boolean($record->isStart())
            ->isTrue()
            ->given($record->moveNext())
            ->integer($record->index())
            ->isEqualTo(1)
            ->given($record->moveNext())
            ->integer($record->index())
            ->isEqualTo(1)
            ->given($record->movePrev())
            ->integer($record->index())
            ->isEqualTo(0)
            ->given($record->movePrev())
            ->integer($record->index())
            ->isEqualTo(0)
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

    public function testToStatic($driver, $syntax)
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

        $result = null;
        $record = new \Dotclear\Database\Record($result, $info);
        $static = $record->toStatic();
        $double = $static->toStatic();

        $this
            // Info
            ->integer($static->count())
            ->isEqualTo(2)
            ->variable($double)
            ->isEqualTo($static)
        ;
    }

    protected function testToStaticDataProvider()
    {
        return [
            // driver, syntax
            ['mysqli', 'mysql'],
            ['mysqlimb4', 'mysql'],
            ['pgsql', 'postgresql'],
            ['sqlite', 'sqlite'],
        ];
    }

    public function testExtend($driver, $syntax)
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

        $record = new \Dotclear\Database\Record($rows, $info);

        $this
            // Initial index
            ->integer($record->index())
            ->isEqualTo(0)

            // First row
            ->boolean($record->fetch())
            ->isTrue()
            ->integer($record->index())
            ->isEqualTo(0)

            // Extend
            ->given($record->extend(\tests\unit\Dotclear\Database\RecordExtend::class))
            ->array($record->extensions())
            ->isEqualTo([
                'isEditable' => [
                    \tests\unit\Dotclear\Database\RecordExtend::class,
                    'isEditable',
                ],
            ])
            ->boolean($record->isEditable())
            ->isTrue()

            // Second row
            ->boolean($record->fetch())
            ->isTrue()
            ->integer($record->index())
            ->isEqualTo(1)
            ->boolean($record->isEditable())
            ->isFalse()

            // Rewind to start
            ->given($record->rewind())
            ->integer($record->index())
            ->isEqualTo(0)
            ->boolean($record->valid())
            ->isTrue()
            ->boolean($record->isEditable())
            ->isTrue()

            // Extend error
            ->given($record->extend('unknown'))
            ->integer(count($record->extensions()))
            ->isEqualTo(1)
            ->when(fn () => $record->unknown())
            ->error()
                ->withMessage('Call to undefined method Record::unknown()')
                ->exists()
        ;
    }

    protected function testExtendDataProvider()
    {
        return [
            // driver, syntax
            ['mysqli', 'mysql'],
            ['mysqlimb4', 'mysql'],
            ['pgsql', 'postgresql'],
            ['sqlite', 'sqlite'],
        ];
    }

    public function testRows($driver, $syntax)
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

        $record = new \Dotclear\Database\Record($rows, $info);

        $this
            // Rows/GetData
            ->integer($record->count())
            ->isEqualTo(2)
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
        ;
    }

    protected function testRowsDataProvider()
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
