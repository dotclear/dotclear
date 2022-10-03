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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

class adminImportExport
{
    /**
     * Initializes the page.
     */
    public static function init()
    {
        $modules = new ArrayObject(['import' => [], 'export' => []]);

        # --BEHAVIOR-- importExportModules
        dcCore::app()->callBehavior('importExportModulesV2', $modules);

        dcCore::app()->admin->type = null;
        if (!empty($_REQUEST['type']) && in_array($_REQUEST['type'], ['export', 'import'])) {
            dcCore::app()->admin->type = $_REQUEST['type'];
        }

        dcCore::app()->admin->modules = $modules;

        dcCore::app()->admin->module = null;
        if (dcCore::app()->admin->type && !empty($_REQUEST['module']) && isset(dcCore::app()->admin->modules[dcCore::app()->admin->type]) && in_array($_REQUEST['module'], dcCore::app()->admin->modules[dcCore::app()->admin->type])) {
            dcCore::app()->admin->module = new $_REQUEST['module'](dcCore::app());
            dcCore::app()->admin->module->init();
        }
    }

    /**
     * Processes the request(s).
     */
    public static function process()
    {
        if (dcCore::app()->admin->type && dcCore::app()->admin->module !== null && !empty($_REQUEST['do'])) {
            try {
                dcCore::app()->admin->module->process($_REQUEST['do']);
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }
    }

    /**
     * Renders the page.
     */
    public static function render()
    {
        $title = __('Import/Export');

        echo
        '<html>' .
        '<head>' .
        '<title>' . $title . '</title>' .
        dcPage::cssModuleLoad('importExport/style.css') .
        dcPage::jsJson('ie_msg', ['please_wait' => __('Please wait...')]) .
        dcPage::jsModuleLoad('importExport/js/script.js') .
        '</head>' .
        '<body>';

        if (dcCore::app()->admin->type && dcCore::app()->admin->module !== null) {
            echo
            dcPage::breadcrumb(
                [
                    __('Plugins')                                        => '',
                    $title                                               => dcCore::app()->admin->getPageURL(),
                    html::escapeHTML(dcCore::app()->admin->module->name) => '',
                ]
            ) .
            dcPage::notices() .
            '<div id="ie-gui">';

            dcCore::app()->admin->module->gui();

            echo
            '</div>';
        } else {
            echo
            dcPage::breadcrumb(
                [
                    __('Plugins') => '',
                    $title        => '',
                ]
            ) .
            dcPage::notices() .

            '<h3>' . __('Import') . '</h3>' .
            self::listImportExportModules(dcCore::app()->admin->modules['import']) .

            '<h3>' . __('Export') . '</h3>' .
            '<p class="info">' . sprintf(
                __('Export functions are in the page %s.'),
                '<a href="' . dcCore::app()->adminurl->get('admin.plugin.maintenance', ['tab' => 'backup']) . '#backup">' .
                __('Maintenance') . '</a>'
            ) . '</p>';
        }

        dcPage::helpBlock('import');

        echo
        '</body></html>';
    }

    protected static function listImportExportModules($modules)
    {
        $res = '';
        foreach ($modules as $id) {
            $o = new $id(dcCore::app());

            $res .= '<dt><a href="' . $o->getURL(true) . '">' . html::escapeHTML($o->name) . '</a></dt>' .
            '<dd>' . html::escapeHTML($o->description) . '</dd>';
        }

        return '<dl class="modules">' . $res . '</dl>';
    }
}

adminImportExport::init();
adminImportExport::process();
adminImportExport::render();
