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
use Dotclear\App;
use Dotclear\Helper\Html\Form\Capture;
use Dotclear\Helper\Html\Form\Dd;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Dl;
use Dotclear\Helper\Html\Form\Dt;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief   The module manage process.
 * @ingroup importExport
 */
class Manage
{
    use TraitProcess;

    public static function init(): bool
    {
        self::status(My::checkContext(My::MANAGE));

        /**
         * @var ArrayObject<string, array<array-key, class-string>>
         */
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
         * @var null|Module|false
         */
        $module = $_REQUEST['module'] ?? false;
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
                $do = is_string($do = $_REQUEST['do']) ? $do : '';

                /**
                 * @var Module
                 */
                $module = App::backend()->module;
                $module->process($do);
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

        App::backend()->page()->openModule(
            My::name(),
            My::cssLoad('style') .
            App::backend()->page()->jsJson('ie_msg', ['please_wait' => __('Please wait...')]) .
            My::jsLoad('script')
        );

        if (App::backend()->type && App::backend()->module !== null) {
            /**
             * @var Module
             */
            $module = App::backend()->module;

            echo
            App::backend()->page()->breadcrumb(
                [
                    __('Plugins')                   => '',
                    My::name()                      => App::backend()->getPageURL(),
                    Html::escapeHTML($module->name) => '',
                ]
            ) .
            App::backend()->notices()->getNotices();

            echo (new Div('ie-gui'))->items([
                (new Capture($module->gui(...))),
            ])
            ->render();
        } else {
            echo
            App::backend()->page()->breadcrumb(
                [
                    __('Plugins') => '',
                    My::name()    => '',
                ]
            ) .
            App::backend()->notices()->getNotices();

            $list = [];

            /**
             * @var ArrayObject<string, array<array-key, class-string>>
             */
            $modules = App::backend()->modules;

            /**
             * @var array<array-key, class-string>
             */
            $modules_import = $modules['import'];
            foreach ($modules_import as $id) {
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

        App::backend()->page()->helpBlock('import');

        App::backend()->page()->closeModule();
    }
}
