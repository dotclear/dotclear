<?php
/**
 * @brief buildtools, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\buildtools;

use dcMaintenanceTask;
use defaultWidgets;

class BuildtoolsMaintenanceTask extends dcMaintenanceTask
{
    /**
     * Maintenance Tab name
     *
     * @var        string
     */
    protected $tab = 'dev';

    /**
     * Maintenance group name
     *
     * @var        string
     */
    protected $group = 'l10n';

    /**
     * Initializes the task.
     */
    protected function init(): void
    {
        $this->task        = __('Generate fake l10n');
        $this->success     = __('fake l10n file generated.');
        $this->error       = __('Failed to generate fake l10n file.');
        $this->description = __('Generate a php file that contents strings to translate that are not be done with core tools.');
    }

    /**
     * Execute the task.
     *
     * @return     bool
     */
    public function execute(): bool
    {
        if (class_exists('defaultWidgets')) {
            defaultWidgets::init();
        }

        $faker = new l10nFaker();
        $faker->generate_file();

        return true;
    }
}
