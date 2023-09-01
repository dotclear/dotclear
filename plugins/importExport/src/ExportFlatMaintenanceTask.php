<?php
/**
 * @brief importExport, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\importExport;

use Dotclear\App;

class ExportFlatMaintenanceTask extends ModuleExportFlat
{
    /**
     * Set redirection URL of bakcup process.
     *
     * Bad hack to change redirection of ModuleExportFlat::process()
     *
     * @param      string  $id     Task ID
     */
    public function setURL(string $id): void
    {
        $this->url = App::backend()->url->get('admin.plugin', ['p' => 'maintenance', 'task' => $id], '&');
    }
}
