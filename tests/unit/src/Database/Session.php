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
 * @tags SessionDB
 */
class Session extends atoum
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

    public function test()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con     = $this->getConnection($driver, $syntax);
        $session = new \Dotclear\Database\Session($con, 'dc_session', 'ck_session');

        $this
            ->given($session->start())
            ->string(session_name())
            ->isEqualTo('ck_session')
            ->array($session->getCookieParameters('mycookie', -1))
            ->isEqualTo(['ck_session', 'mycookie', -1, '/', '', false])
            ->then($session->destroy())
        ;
    }
}
