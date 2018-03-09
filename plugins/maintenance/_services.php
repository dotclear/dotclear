<?php
/**
 * @brief maintenance, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_CONTEXT_ADMIN')) {return;}

/**
@ingroup PLUGIN_MAINTENANCE
@nosubgrouping
@brief Maintenance plugin rest service class.

Serve maintenance methods via Dotclear's rest API
 */
class dcMaintenanceRest
{
    /**
     * Serve method to do step by step task for maintenance.
     *
     * @param    core    <b>dcCore</b>    dcCore instance
     * @param    get        <b>array</b>    cleaned $_GET
     * @param    post    <b>array</b>    cleaned $_POST
     *
     * @return    <b>xmlTag</b>    XML representation of response
     */
    public static function step($core, $get, $post)
    {
        if (!isset($post['task'])) {
            throw new Exception('No task ID');
        }
        if (!isset($post['code'])) {
            throw new Exception('No code ID');
        }

        $maintenance = new dcMaintenance($core);
        if (($task = $maintenance->getTask($post['task'])) === null) {
            throw new Exception('Unknow task ID');
        }

        $task->code((integer) $post['code']);
        if (($code = $task->execute()) === true) {
            $maintenance->setLog($task->id());
            $code = 0;
        }

        $rsp        = new xmlTag('step');
        $rsp->code  = $code;
        $rsp->title = html::escapeHTML($task->success());

        return $rsp;
    }
}
