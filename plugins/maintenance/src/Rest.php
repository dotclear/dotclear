<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\maintenance;

use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief   The module REST service handler.
 * @ingroup maintenance
 */
class Rest
{
    /**
     * Serve method to do step by step task for maintenance (JSON).
     *
     * @param   array<string, mixed>   $get    cleaned $_GET
     * @param   array<string, mixed>   $post   cleaned $_POST
     *
     * @throws  Exception
     *
     * @return  array<string, mixed>
     */
    public static function step(array $get, array $post): array
    {
        if (!isset($post['task'])) {
            throw new Exception('No task ID');
        }
        if (!isset($post['code'])) {
            throw new Exception('No code ID');
        }

        $maintenance = new Maintenance();
        if (!($task = $maintenance->getTask($post['task'])) instanceof MaintenanceTask) {
            throw new Exception('Unknown task ID');
        }

        $task->code((int) $post['code']);
        if (($code = $task->execute()) === true) {
            $maintenance->setLog($task->id());
            $code = 0;
        }

        return [
            'code'  => $code,
            'title' => Html::escapeHTML($task->success()),
        ];
    }

    /**
     * Serve method to count of expired tasks for maintenance (JSON).
     *
     * @return  array<string, mixed>
     */
    public static function countExpired(): array
    {
        // Check expired tasks
        $maintenance = new Maintenance();
        $count       = 0;
        foreach ($maintenance->getTasks() as $t) {
            if ($t->expired() !== false) {
                $count++;
            }
        }

        return [
            'ret' => true,
            'msg' => ($count !== 0 ? sprintf(__('One task to execute', '%s tasks to execute', $count), $count) : ''),
            'nb'  => $count,
        ];
    }
}
