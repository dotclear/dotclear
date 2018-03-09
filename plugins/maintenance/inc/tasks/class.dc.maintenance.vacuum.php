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

if (!defined('DC_RC_PATH')) {return;}

class dcMaintenanceVacuum extends dcMaintenanceTask
{
    protected $group = 'optimize';

    protected function init()
    {
        $this->name    = __('Optimise database');
        $this->task    = __('optimize tables');
        $this->success = __('Optimization successful.');
        $this->error   = __('Failed to optimize tables.');

        $this->description = __("After numerous delete or update operations on Dotclear's database, it gets fragmented. Optimizing will allow to defragment it. It has no incidence on your data's integrity. It is recommended to optimize before any blog export.");
    }

    public function execute()
    {
        $schema = dbSchema::init($this->core->con);

        foreach ($schema->getTables() as $table) {
            if (strpos($table, $this->core->prefix) === 0) {
                $this->core->con->vacuum($table);
            }
        }

        return true;
    }
}
