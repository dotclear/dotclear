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
if (!defined('DC_RC_PATH')) {
    return;
}

class dcRestServer extends restServer
{
    public $core; ///< dcCore instance

    /**
     * Constructs a new instance.
     *
     * @param      dcCore  $core   The core
     */
    public function __construct(dcCore $core)
    {
        parent::__construct();

        $this->core = &$core;
    }

    /**
     * Rest method call.
     *
     * @param      string  $name   The method name
     * @param      array   $get    The GET parameters copy
     * @param      array   $post   The POST parameters copy
     *
     * @return     mixed    Rest method result
     */
    protected function callFunction($name, $get, $post)
    {
        if (isset($this->functions[$name])) {
            return call_user_func($this->functions[$name], $this->core, $get, $post);
        }
    }
}
