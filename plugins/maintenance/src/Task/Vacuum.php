<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\maintenance\Task;

use Dotclear\App;
use Dotclear\Plugin\maintenance\MaintenanceTask;

/**
 * @brief   The table vacuum maintenance task.
 * @ingroup maintenance
 */
class Vacuum extends MaintenanceTask
{
    /**
     * Task ID (class name).
     */
    protected ?string $id = 'dcMaintenanceVacuum';

    /**
     * Task group container.
     */
    protected string $group = 'optimize';

    /**
     * Initialize task object.
     */
    protected function init(): void
    {
        $this->name    = __('Optimise database');
        $this->task    = __('optimize tables');
        $this->success = __('Optimization successful.');
        $this->error   = __('Failed to optimize tables.');

        $this->description = __("After numerous delete or update operations on Dotclear's database, it gets fragmented. Optimizing will allow to defragment it. It has no incidence on your data's integrity. It is recommended to optimize before any blog export.");
    }

    public function execute(): bool|int
    {
        $schema = App::con()->schema();

        foreach ($schema->getTables() as $table) {
            if (str_starts_with($table, App::con()->prefix())) {
                App::con()->vacuum($table);
            }
        }

        return true;
    }
}
