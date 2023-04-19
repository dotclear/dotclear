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

        $class_name = sprintf('\mock\%sConnection', $driver);
        $con        = new $class_name($driver, $controller);

        $this->calling($con)->driver = $driver;
        $this->calling($con)->syntax = $syntax;
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

        $id   = '61af8c3f8fcfa8921814cc9d2db0d87482e1ff76';
        $data = 'sess_user_id|s:5:"bebop";sess_browser_uid|s:40:"b9032c5dc1b4a9bd5df9e2b73e1b7421776cb49b";sess_blog_id|s:7:"default";';

        $this
            ->given($session->start())
            ->and($session->_write($id, $data))
            ->string(session_name())
            ->isEqualTo('ck_session')
            ->array($session->getCookieParameters('mycookie', -1))
            ->isEqualTo(['ck_session', 'mycookie', -1, '/', '', false])
            ->then($session->destroy())
        ;
    }

    public function testTransient()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con     = $this->getConnection($driver, $syntax);
        $session = new \Dotclear\Database\Session($con, 'dc_session', 'ck_session', null, null, true, '2 hours', true);

        $id   = '61af8c3f8fcfa8921814cc9d2db0d87482e1ff76';
        $data = 'sess_user_id|s:5:"bebop";sess_browser_uid|s:40:"b9032c5dc1b4a9bd5df9e2b73e1b7421776cb49b";sess_blog_id|s:7:"default";';

        $this
            ->given($session->setTransientSession(true))
            ->and($session->start())
            ->and($session->_write($id, $data))
            ->string(session_name())
            ->isEqualTo('ck_session')
            ->then($session->destroy())
        ;
    }
}
