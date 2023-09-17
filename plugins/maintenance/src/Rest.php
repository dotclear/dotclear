<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\maintenance;

use Dotclear\App;
use Dotclear\Helper\Html\Html;
use Exception;

if (!App::task()->checkContext('BACKEND')) {
    return;
}

/**
 * @brief   The module REST service handler.
 * @ingroup maintenance
 */
class Rest
{
    /**
     * Serve method to do step by step task for maintenance (JSON).
     *
     * @param   array   $get    cleaned $_GET
     * @param   array   $post   cleaned $_POST
     *
     * @throws  Exception
     *
     * @return  array
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
        if (($task = $maintenance->getTask($post['task'])) === null) {
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
     * @return  array
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
            'msg' => ($count ? sprintf(__('One task to execute', '%s tasks to execute', $count), $count) : ''),
            'nb'  => $count,
        ];
    }
}
