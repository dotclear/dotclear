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
class Cursor extends atoum
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
                    if (in_array($method, ['select', 'delete', 'insert', 'update', 'openCursor', 'changes', 'vacuum'])) {
                        switch ($method) {
                            case 'select':
                                return true;

                                break;
                            case 'delete':
                                return true;

                                break;
                            case 'insert':
                                return true;

                                break;
                            case 'update':
                                return true;

                                break;
                            case 'openCursor':
                                return true;

                                break;
                            case 'changes':
                                return 1;

                                break;
                            case 'vacuum':
                                return true;

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
        $con    = $this->getConnection($driver, $syntax);
        $cursor = new \Dotclear\Database\Cursor($con, 'dc_table');

        $this
            ->boolean($cursor->isField('Name'))
            ->isFalse()
            ->then

            ->given($cursor->setField('Name', 'Dotclear'))
            ->string($cursor->Name)
            ->isEqualTo('Dotclear')

            ->given($cursor->Town = 'Paris')
            ->string($cursor->Town)
            ->isEqualTo('Paris')

            ->given($cursor->Age = 42)
            ->integer($cursor->Age)
            ->isEqualTo(42)

            ->given($cursor->unsetField('Town'))
            ->boolean($cursor->isField('Town'))
            ->isFalse()

            ->string($this->normalizeSQL($cursor->getInsert()))
            ->isEqualTo('INSERT INTO dc_table (Name,Age) VALUES (\'Dotclear\',42)')
            ->boolean($cursor->insert())
            ->isTrue()

            ->string($this->normalizeSQL($cursor->getUpdate(' WHERE Name = \'Dotclear\'')))
            ->isEqualTo('UPDATE dc_table SET Name = \'Dotclear\',Age = 42 WHERE Name = \'Dotclear\'')
            ->boolean($cursor->update(' WHERE Name = \'Dotclear\''))
            ->isTrue()

            ->given($cursor->Data = ['AVG(Age)'])
            ->and($cursor->Void = null)
            ->string($this->normalizeSQL($cursor->getInsert()))
            ->isEqualTo('INSERT INTO dc_table (Name,Age,Data,Void) VALUES (\'Dotclear\',42,\'AVG(Age)\',NULL)')

            ->given($cursor->setTable(''))
            ->exception(fn () => $cursor->insert())
            ->hasMessage('No table name.')
            ->exception(fn () => $cursor->update(''))
            ->hasMessage('No table name.')

            ->given($cursor->clean())
            ->boolean($cursor->isField('Name'))
            ->isFalse()
            ->variable($cursor->Unknown)
            ->isNull()
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

    protected function normalizeSQL(string $str, bool $comma = true): string
    {
        if ($comma) {
            $str = str_replace(', ', ',', $str);
        }

        return trim(str_replace(["\n", "\r"], '', $str));
    }
}
