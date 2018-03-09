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

class dcMaintenanceCache extends dcMaintenanceTask
{
    protected $group = 'purge';

    protected function init()
    {
        $this->task    = __('Empty templates cache directory');
        $this->success = __('Templates cache directory emptied.');
        $this->error   = __('Failed to empty templates cache directory.');

        $this->description = __("It may be useful to empty this cache when modifying a theme's .html or .css files (or when updating a theme or plugin). Notice : with some hosters, the templates cache cannot be emptied with this plugin. You may then have to delete the directory <strong>/cbtpl/</strong> directly on the server with your FTP software.");
    }

    public function execute()
    {
        $this->core->emptyTemplatesCache();

        return true;
    }
}
