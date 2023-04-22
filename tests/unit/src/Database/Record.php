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
 * @tags CursorDB
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

        // Mock db_result_seek
        // True if row index valid, else false
        $this->calling($con)->db_result_seek = fn ($res, $row) => $row >= 0 && $row < 2;

        // Mock db_fetch_assoc
        $this->calling($con)->db_fetch_assoc[0] = $rows[0];
        $this->calling($con)->db_fetch_assoc[1] = $rows[1];
        $this->calling($con)->db_fetch_assoc[2] = false;

        $result = null;
        $record = new \Dotclear\Database\Record($result, $info);

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
            ->boolean($record->fetch())
            ->isFalse()
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
}
