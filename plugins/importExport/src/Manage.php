<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\importExport;

use ArrayObject;
use Exception;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Dd;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Dl;
use Dotclear\Helper\Html\Form\Dt;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;

/**
 * @brief   The module manage process.
 * @ingroup importExport
 */
class Manage extends Process
{
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

        /**
         * @var null|Module
         */
        $module = $_REQUEST['module'] ?? false; // @phpstan-ignore-line
        if (App::backend()->type && $module && isset(App::backend()->modules[App::backend()->type]) && in_array($module, App::backend()->modules[App::backend()->type])) {
            App::backend()->module = new $module();
            App::backend()->module->init();
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
            Notices::getNotices();

            echo (new Div('ie-gui'))->items([
                (new Text(null, App::backend()->module->gui())),
            ])
            ->render();
        } else {
            echo
            Page::breadcrumb(
                [
                    __('Plugins') => '',
                    My::name()    => '',
                ]
            ) .
            Notices::getNotices();

            $list = [];
            foreach (App::backend()->modules['import'] as $id) {
                if (is_subclass_of($id, Module::class)) {
                    $module = new $id();

                    $list[] = (new Dt())->items([(new Link())->href($module->getURL(true))->text($module->name)]);
                    $list[] = (new Dd())->text(Html::escapeHTML($module->description));
                }
            }

            echo (new Set())->items([
                (new Text('h3', __('Import'))),
                ((new Dl())->class('modules')->items($list)),
            ])
            ->render();

            echo (new Set())->items([
                (new Text('h3', __('Export'))),
                (new Para())
                    ->class('info')
                    ->items([
                        (new Text(null, sprintf(
                            __('Export functions are in the page %s.'),
                            (new Link())
                                ->href(App::backend()->url()->get('admin.plugin.maintenance', ['tab' => 'backup']) . '#backup')
                                ->text(__('Maintenance'))
                            ->render(),
                        ))),
                    ]),
            ])
            ->render();
        }

        Page::helpBlock('import');

        Page::closeModule();
    }
}
