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
declare(strict_types=1);

namespace Dotclear\Plugin\maintenance\Task;

use Dotclear\App;
use Dotclear\Database\AbstractSchema;
use Dotclear\Plugin\maintenance\MaintenanceTask;

class Vacuum extends MaintenanceTask
{
    protected $id = 'dcMaintenanceVacuum';

    /**
     * Task group container
     *
     * @var string
     */
    protected $group = 'optimize';

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

    /**
     * Execute task.
     *
     * @return    bool|int
     *    - FALSE on error,
     *    - TRUE if task is finished
     *    - INT if task required a next step
     */
    public function execute()
    {
        $schema = AbstractSchema::init(App::con());

        foreach ($schema->getTables() as $table) {
            if (str_starts_with($table, (string) App::con()->prefix())) {
                App::con()->vacuum($table);
            }
        }

        return true;
    }
}
