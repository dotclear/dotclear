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

use ArrayObject;
use Exception;
use dcCore;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;

class Manage extends Process
{
    public static function init(): bool
    {
        self::status(My::checkContext(My::MANAGE));

        $modules = new ArrayObject(['import' => [], 'export' => []]);

        # --BEHAVIOR-- importExportModules -- ArrayObject
        dcCore::app()->callBehavior('importExportModulesV2', $modules);

        dcCore::app()->admin->type = null;
        if (!empty($_REQUEST['type']) && in_array($_REQUEST['type'], ['export', 'import'])) {
            dcCore::app()->admin->type = $_REQUEST['type'];
        }

        dcCore::app()->admin->modules = $modules;
        dcCore::app()->admin->module  = null;

        $module = $_REQUEST['module'] ?? false;
        if (dcCore::app()->admin->type && $module !== false && isset(dcCore::app()->admin->modules[dcCore::app()->admin->type]) && in_array($module, dcCore::app()->admin->modules[dcCore::app()->admin->type])) {
            dcCore::app()->admin->module = new $module(dcCore::app());
            dcCore::app()->admin->module->init();
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (dcCore::app()->admin->type && dcCore::app()->admin->module !== null && !empty($_REQUEST['do'])) {
            try {
                dcCore::app()->admin->module->process($_REQUEST['do']);
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        Page::openModule(
            My::name(),
            My::cssLoad('style.css') .
            Page::jsJson('ie_msg', ['please_wait' => __('Please wait...')]) .
            My::jsLoad('script.js')
        );

        if (dcCore::app()->admin->type && dcCore::app()->admin->module !== null) {
            echo
            Page::breadcrumb(
                [
                    __('Plugins')                                        => '',
                    My::name()                                           => dcCore::app()->admin->getPageURL(),
                    Html::escapeHTML(dcCore::app()->admin->module->name) => '',
                ]
            ) .
            Page::notices() .
            '<div id="ie-gui">';

            dcCore::app()->admin->module->gui();

            echo
            '</div>';
        } else {
            echo
            Page::breadcrumb(
                [
                    __('Plugins') => '',
                    My::name()    => '',
                ]
            ) .
            Page::notices() .

            '<h3>' . __('Import') . '</h3>' .
            self::listImportExportModules(dcCore::app()->admin->modules['import']) .

            '<h3>' . __('Export') . '</h3>' .
            '<p class="info">' . sprintf(
                __('Export functions are in the page %s.'),
                '<a href="' . dcCore::app()->adminurl->get('admin.plugin.maintenance', ['tab' => 'backup']) . '#backup">' .
                __('Maintenance') . '</a>'
            ) . '</p>';
        }

        Page::helpBlock('import');

        Page::closeModule();
    }

    protected static function listImportExportModules($modules)
    {
        $res = '';
        foreach ($modules as $id) {
            if (is_subclass_of($id, Module::class)) {
                $o = new $id(dcCore::app());

                $res .= '<dt><a href="' . $o->getURL(true) . '">' . Html::escapeHTML($o->name) . '</a></dt>' .
                '<dd>' . Html::escapeHTML($o->description) . '</dd>';
            }
        }

        return '<dl class="modules">' . $res . '</dl>';
    }
}
