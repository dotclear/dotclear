<?php
/**
 * @brief Dotclear REST server extension
 *
 * This class extends restServer to handle dcCore instance in each rest method call.
 * Instance of this class is provided by dcCore $rest.
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

class dcRestServer extends restServer
{
    public $core; ///< dcCore instance

    /**
    Object constructor.

    @param    core        <b>dcCore</b>        dcCore instance
     */
    public function __construct($core)
    {
        parent::__construct();

        $this->core = &$core;
    }

    /**
    Rest method call.

    @param    name        <b>string</b>        Method name
    @param    get        <b>array</b>        GET parameters copy
    @param    post        <b>array</b>        POST parameters copy
    @return    <b>mixed</b>    Rest method result
     */
    protected function callFunction($name, $get, $post)
    {
        if (isset($this->functions[$name])) {
            return call_user_func($this->functions[$name], $this->core, $get, $post);
        }
    }
}
