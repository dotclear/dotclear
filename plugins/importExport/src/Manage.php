<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\importExport;

use ArrayObject;
use Exception;
use dcCore;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;

/**
 * @brief   The module manage process.
 * @ingroup importExport
 */
class Manage extends Process
{
    /**
     * @todo    Remove old dcCore from ImportExport Manage::init new module parameters
     */
    public static function init(): bool
    {
        self::status(My::checkContext(My::MANAGE));

        $modules = new ArrayObject(['import' => [], 'export' => []]);

        # --BEHAVIOR-- importExportModules -- ArrayObject
        App::behavior()->callBehavior('importExportModulesV2', $modules);

        App::backend()->type = null;
        if (!empty($_REQUEST['type']) && in_array($_REQUEST['type'], ['export', 'import'])) {
            App::backend()->type = $_REQUEST['type'];
        }

        App::backend()->modules = $modules;
        App::backend()->module  = null;

        $module = $_REQUEST['module'] ?? false;
        if (App::backend()->type && $module !== false && isset(App::backend()->modules[App::backend()->type]) && in_array($module, App::backend()->modules[App::backend()->type])) {
            App::backend()->module = new $module(dcCore::app());
            App::backend()->module->init(); // @phpstan-ignore-line
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (App::backend()->type && App::backend()->module !== null && !empty($_REQUEST['do'])) {
            try {
                App::backend()->module->process($_REQUEST['do']);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        Page::openModule(
            My::name(),
            My::cssLoad('style') .
            Page::jsJson('ie_msg', ['please_wait' => __('Please wait...')]) .
            My::jsLoad('script')
        );

        if (App::backend()->type && App::backend()->module !== null) {
            echo
            Page::breadcrumb(
                [
                    __('Plugins')                                  => '',
                    My::name()                                     => App::backend()->getPageURL(),
                    Html::escapeHTML(App::backend()->module->name) => '',
                ]
            ) .
            Notices::getNotices() .
            '<div id="ie-gui">';

            App::backend()->module->gui();

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
            Notices::getNotices() .

            '<h3>' . __('Import') . '</h3>' .
            self::listImportExportModules(App::backend()->modules['import']) .

            '<h3>' . __('Export') . '</h3>' .
            '<p class="info">' . sprintf(
                __('Export functions are in the page %s.'),
                '<a href="' . App::backend()->url->get('admin.plugin.maintenance', ['tab' => 'backup']) . '#backup">' .
                __('Maintenance') . '</a>'
            ) . '</p>';
        }

        Page::helpBlock('import');

        Page::closeModule();
    }

    /**
     * @todo    Remove old dcCore from ImportExport Manage::listImportExportModules new module parameters
     *
     * @param      array<int, mixed>  $modules  The modules
     *
     * @return     string
     */
    protected static function listImportExportModules($modules): string
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
